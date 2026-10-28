import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/services/levels.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';
import 'package:toxbot/utils/utils.dart';

const _voteCooldown = Duration(hours: 12);

String _timestamp(DateTime time) =>
    '<t:${time.toUtc().millisecondsSinceEpoch ~/ 1000}:R>';

final vote = ChatCommand(
  'vote',
  'Vote for Toxbot on Top.gg and get credits.',
  localizedDescriptions: {
    Locale.da: 'Stem på Toxbot på Top.gg og få credits.',
    Locale.de: 'Auf Top.gg für Toxbot voten und Credits bekommen.',
    Locale.enUs: 'Vote for Toxbot on Top.gg and get credits.',
    Locale.esEs: 'Vota por Toxbot en Top.gg y consigue créditos.',
    Locale.fr: 'Voter pour Toxbot sur Top.gg et gagner des crédits.',
    Locale.ru: 'Проголосовать за Toxbot на Top.gg и получить кредиты.',
    Locale.hi: 'Top.gg पर Toxbot को वोट दें और क्रेडिट पाएँ।',
    Locale.zhCn: '在 Top.gg 上为 Toxbot 投票并获得积分。',
    Locale.ja: 'Top.gg で Toxbot に投票してクレジットをもらいます。',
    Locale.ko: 'Top.gg에서 Toxbot에 투표하고 크레딧을 받습니다.',
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
