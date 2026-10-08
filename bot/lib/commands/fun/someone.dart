import 'dart:math';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';

final someone =
    ChatCommand('someone', 'Mention a random user.', localizedDescriptions: {
  Locale.da: 'Nævn en tilfældig bruger.',
  Locale.de: 'Einen zufälligen Benutzer erwähnen.',
  Locale.enUs: 'Mention a random user.',
  Locale.esEs: 'Mencionar a un usuario aleatorio.',
  Locale.fr: 'Mentionner un utilisateur au hasard.',
  Locale.ru: 'Упомянуть случайного пользователя.',
  Locale.hi: 'किसी यादृच्छिक उपयोगकर्ता का उल्लेख करें',
  Locale.zhCn: '提及一个随机用户。',
  Locale.ja: 'ランダムなユーザーをメンションする。',
  Locale.ko: '무작위 사용자 언급.'
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
