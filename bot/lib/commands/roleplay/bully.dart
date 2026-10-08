import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final bully = ChatCommand('bully', 'Bully someone.', localizedDescriptions: {
  Locale.da: 'Drille nogen.',
  Locale.de: 'Mobbe jemanden.',
  Locale.enUs: 'Bully someone.',
  Locale.esEs: 'Intimidar a alguien.',
  Locale.fr: 'Intimider quelqu\'un.',
  Locale.ru: 'Издеваться над кем-то.',
  Locale.hi: 'किसी को तंग करो।',
  Locale.zhCn: '欺负某人。',
  Locale.ja: '誰かをいじめる。',
  Locale.ko: '누군가를 괴롭히다.'
}, (ChatContext context,
    [@Description('The user you want to bully.', {
      Locale.da: 'Den bruger, du vil drille.',
      Locale.de: 'Der Benutzer, den du mobben willst.',
      Locale.enUs: 'The user you want to bully.',
      Locale.esEs: 'El usuario que quieres intimidar.',
      Locale.fr: 'L\'utilisateur que vous voulez intimider.',
      Locale.ru: 'Пользователь, над которым вы хотите издеваться.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप तंग करना चाहते हैं।',
      Locale.zhCn: '你想欺负的用户。',
      Locale.ja: 'いじめたいユーザー。',
      Locale.ko: '괴롭히고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/bully');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bully_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bully_user_bullied'))
                .replaceAll('&bullied', user.username)
                .replaceAll('&bully', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in bully command: $e');
    await context.respond(
        MessageBuilder(embeds: [
          EmbedBuilder(
              color: DiscordColor.parseHexString('#c41111'),
              title: await getString(context.user, 'global_error'),
              description: codeBlock(e.toString(), 'sh'))
        ]),
        level: ResponseLevel.hint);
    return;
  }
});
