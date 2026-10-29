import 'dart:math';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/services/levels.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const _minBet = 10;

const _maxBet = 10000;

const _cooldown = Duration(seconds: 5);

const _revealDelay = Duration(milliseconds: 700);

const _spinning = '🔄';

const _symbols = [
  ('🍒', 25, 8),
  ('🍋', 22, 10),
  ('🍇', 20, 12),
  ('🔔', 15, 25),
  ('⭐', 10, 50),
  ('💎', 5, 100),
  ('7️⃣', 3, 500),
];

const _jackpot = '7️⃣';

final _random = Random.secure();

final Map<Snowflake, DateTime> _lastSpin = {};

String _roll() {
  final total = _symbols.fold<int>(0, (sum, symbol) => sum + symbol.$2);
  var pick = _random.nextInt(total);
  for (final symbol in _symbols) {
    if (pick < symbol.$2) return symbol.$1;
    pick -= symbol.$2;
  }
  return _symbols.first.$1;
}

int _multiplier(String symbol) =>
    _symbols.firstWhere((entry) => entry.$1 == symbol).$3;

int _payout(List<String> reels, int bet) {
  if (reels.toSet().length == 1) return bet * _multiplier(reels.first);
  if (reels.toSet().length == 2) return bet;
  return 0;
}

String _machine(List<String> reels) => '**[ ${reels.join(' | ')} ]**';

Future<void> _showPaytable(ChatContext context) async {
  final user = context.user;
  final balance = await withConnection(
      (connection) => economy.getCredits(connection, user.id.toString()));
  final lines = [
    for (final symbol in _symbols.reversed)
      '${symbol.$1}${symbol.$1}${symbol.$1}  **${symbol.$3}×**',
    await getString(user, 'slots_paytable_two'),
    '',
    await localized(user, 'slots_paytable_bet', {
      '&min': formatCredits(_minBet),
      '&max': formatCredits(_maxBet),
    }),
    await localized(user, 'slots_balance', {'&amount': formatCredits(balance)}),
  ];
  await context.respond(MessageBuilder(embeds: [
    EmbedBuilder(
      color: await getUserColorFromDatabase(user.id),
      title: await getString(user, 'slots_paytable_title'),
      description: lines.join('\n'),
    ),
  ]));
}

final slots = ChatCommand(
  'slots',
  'Play the slot machine with your credits.',
  localizedDescriptions: {
    Locale.de: 'Mit deinen Credits am Spielautomaten spielen.',
    Locale.enUs: 'Play the slot machine with your credits.',
    Locale.da: 'Spil på enarmet tyveknægt med dine credits.',
    Locale.esEs: 'Juega a la tragaperras con tus créditos.',
    Locale.fr: 'Jouer à la machine à sous avec tes crédits.',
    Locale.ru: 'Сыграть в игровой автомат на свои кредиты.',
    Locale.hi: 'अपने क्रेडिट से स्लॉट मशीन खेलें।',
    Locale.zhCn: '用你的积分玩老虎机。',
    Locale.ja: 'クレジットでスロットマシンを遊びます。',
    Locale.ko: '크레딧으로 슬롯머신을 돌립니다.',
  },
  (
    ChatContext context, [
    @Description(
        'How many credits you bet. Leave it empty to see the payout table.', {
      Locale.de:
          'Wie viele Credits du setzt. Leer lassen für die Gewinntabelle.',
      Locale.enUs:
          'How many credits you bet. Leave it empty to see the payout table.',
      Locale.da:
          'Hvor mange credits du satser. Lad feltet stå tomt for gevinsttabellen.',
      Locale.esEs:
          'Cuántos créditos apuestas. Déjalo vacío para ver la tabla de premios.',
      Locale.fr:
          'Combien de crédits tu mises. Laisse vide pour voir la table des gains.',
      Locale.ru:
          'Сколько кредитов ты ставишь. Оставь пустым, чтобы увидеть таблицу выигрышей.',
      Locale.hi:
          'आप कितने क्रेडिट दाँव पर लगाते हैं। इनाम तालिका के लिए खाली छोड़ें।',
      Locale.zhCn: '下注的积分数量。留空可查看赔率表。',
      Locale.ja: '賭けるクレジット。空欄にすると配当表を表示します。',
      Locale.ko: '걸 크레딧 수. 비워 두면 배당표를 표시합니다.',
    })
    @UseConverter(IntConverter(min: _minBet, max: _maxBet))
    int? bet,
  ]) async {
    final user = context.user;
    try {
      if (bet == null) {
        await _showPaytable(context);
        return;
      }

      final now = DateTime.now().toUtc();
      final last = _lastSpin[user.id];
      if (last != null && now.difference(last) < _cooldown) {
        final next = last.add(_cooldown);
        await replyError(context, 'slots_cooldown', replace: {
          '&time': '<t:${next.millisecondsSinceEpoch ~/ 1000 + 1}:R>',
        });
        return;
      }
      _lastSpin[user.id] = now;
      _lastSpin.removeWhere((_, time) => now.difference(time) > _cooldown);

      final reels = [_roll(), _roll(), _roll()];
      final payout = _payout(reels, bet);
      final balance = await withConnection((connection) =>
          economy.settleSlots(connection, user.id.toString(), bet, payout));

      if (balance == null) {
        _lastSpin.remove(user.id);
        final credits = await withConnection(
            (connection) => economy.getCredits(connection, user.id.toString()));
        await replyError(context, 'slots_not_enough',
            replace: {'&amount': formatCredits(credits)});
        return;
      }

      final color = await getUserColorFromDatabase(user.id);
      final title = await getString(user, 'slots_title');
      final footer = await localized(user, 'slots_footer', {
        '&bet': formatCredits(bet),
        '&amount': formatCredits(balance),
      });

      final String result;
      if (payout > bet) {
        result = await localized(
            user, reels.first == _jackpot ? 'slots_jackpot' : 'slots_win', {
          '&win': formatCredits(payout),
          '&multiplier': '${payout ~/ bet}×',
        });
      } else if (payout == bet) {
        result = await getString(user, 'slots_push');
      } else {
        result = await localized(user, 'slots_lose', {
          '&bet': formatCredits(bet),
        });
      }

      EmbedBuilder frame(List<String> shown, String text, DiscordColor? tint) =>
          EmbedBuilder(
            color: tint,
            title: title,
            description: '${_machine(shown)}\n\n$text',
            footer: EmbedFooterBuilder(text: footer),
          );

      final finalColor =
          payout > bet ? DiscordColor.parseHexString(successColor) : color;

      if (context is! InteractionChatContext) {
        await context.respond(
            MessageBuilder(embeds: [frame(reels, result, finalColor)]));
        return;
      }

      final spinning = await getString(user, 'slots_spinning');
      await context.respond(MessageBuilder(embeds: [
        frame([_spinning, _spinning, _spinning], spinning, color),
      ]));
      for (var shown = 1; shown <= reels.length; shown++) {
        await Future.delayed(_revealDelay);
        final visible = [
          ...reels.take(shown),
          for (var i = shown; i < reels.length; i++) _spinning,
        ];
        final done = shown == reels.length;
        await context.interaction.updateOriginalResponse(MessageUpdateBuilder(
          embeds: [
            frame(visible, done ? result : spinning, done ? finalColor : color),
          ],
        ));
      }
    } catch (e) {
      await replyCommandError(context, 'levels_error', 'slots', e);
    }
  },
);
