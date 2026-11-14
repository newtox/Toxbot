import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:shiori/services/economy.dart' as economy;
import 'package:shiori/services/levels.dart';
import 'package:shiori/utils/config.dart';
import 'package:shiori/utils/database.dart';
import 'package:shiori/utils/functions.dart';
import 'package:shiori/utils/reply.dart';
import 'package:shiori/utils/utils.dart';

const _voteCooldown = Duration(hours: 12);

String _timestamp(DateTime time) =>
    '<t:${time.toUtc().millisecondsSinceEpoch ~/ 1000}:R>';

final vote = ChatCommand(
  'vote',
  'Vote for Shiori on Top.gg and get credits.',
  localizedDescriptions: {
    Locale.da: 'Stem på Shiori på Top.gg og få credits.',
    Locale.de: 'Auf Top.gg für Shiori voten und Credits bekommen.',
    Locale.enUs: 'Vote for Shiori on Top.gg and get credits.',
    Locale.esEs: 'Vota por Shiori en Top.gg y consigue créditos.',
    Locale.fr: 'Voter pour Shiori sur Top.gg et gagner des crédits.',
    Locale.ru: 'Проголосовать за Shiori на Top.gg и получить кредиты.',
    Locale.hi: 'Top.gg पर Shiori को वोट दें और क्रेडिट पाएँ।',
    Locale.zhCn: '在 Top.gg 上为 Shiori 投票并获得积分。',
    Locale.ja: 'Top.gg で Shiori に投票してクレジットをもらいます。',
    Locale.ko: 'Top.gg에서 Shiori에 투표하고 크레딧을 받습니다.',
    Locale.es419: 'Vota por Shiori en Top.gg y consigue créditos.',
    Locale.ptBr: 'Votar na Shiori no Top.gg e ganhar créditos.',
    Locale.tr: 'Top.gg\'de Shiori\'a oy ver ve kredi kazan.',
    Locale.pl: 'Zagłosuj na Shiori na Top.gg i zdobądź kredyty.',
    Locale.it: 'Vota Shiori su Top.gg e ottieni crediti.',
    Locale.uk: 'Проголосувати за Shiori на Top.gg і отримати кредити.',
    Locale.vi: 'Bình chọn cho Shiori trên Top.gg và nhận credit.',
    Locale.th: 'โหวตให้ Shiori บน Top.gg และรับเครดิต',
    Locale.zhTw: '在 Top.gg 為 Shiori 投票並獲得點數。',
  },
  (ChatContext context) async {
    final user = context.user;
    DateTime? last;
    try {
      last = await withConnection(
          (connection) => economy.lastVote(connection, user.id.toString()));
    } catch (_) {
      last = null;
    }

    final lines = [
      await localized(
          user, 'vote_text', {'&amount': formatCredits(voteReward)}),
    ];
    final next = last?.add(_voteCooldown);
    if (last != null && next!.isAfter(DateTime.now().toUtc())) {
      lines.add(await localized(user, 'vote_wait',
          {'&last': _timestamp(last), '&next': _timestamp(next)}));
    } else {
      lines.add(await getString(user, 'vote_ready'));
    }

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
        color: await getUserColorFromDatabase(user.id),
        title: await getString(user, 'vote_title'),
        description: lines.join('\n\n'),
      ),
    ], components: [
      ActionRowBuilder(components: [
        ButtonBuilder.link(
            label: await getString(user, 'vote_button'),
            url: voteUrl(context.client.user.id)),
      ]),
    ]));
  },
);
