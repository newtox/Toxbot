import 'dart:math';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';

final someone =
    ChatCommand('someone', 'Mention a random member.', localizedDescriptions: {
  Locale.da: 'Nævn et tilfældigt medlem.',
  Locale.de: 'Ein zufälliges Mitglied erwähnen.',
  Locale.enUs: 'Mention a random member.',
  Locale.esEs: 'Mencionar a un miembro al azar.',
  Locale.fr: 'Mentionner un membre au hasard.',
  Locale.ru: 'Упомянуть случайного участника.',
  Locale.hi: 'किसी रैंडम सदस्य को मेंशन करें।',
  Locale.zhCn: '随机提及一名成员。',
  Locale.ja: 'ランダムなメンバーをメンションします。',
  Locale.ko: '무작위 멤버를 멘션합니다.'
}, checks: [
  GuildCheck.all()
], (ChatContext context) async {
  final memberList = (await context.guild?.members.list(limit: 1000))
      ?.map((m) => m.get())
      .toList();

  if (memberList != null && memberList.isNotEmpty) {
    final random = Random();
    final randomNumber = random.nextInt(memberList.length);
    final randomMember = await memberList[randomNumber];

    await context.respond(MessageBuilder(content: '<@${randomMember.id}>'));
  }
});
