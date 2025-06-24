import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final yeet = ChatCommand('yeet', 'Yeet someone.', localizedDescriptions: {
  Locale.da: 'Kast nogen.',
  Locale.de: 'Schleudere jemanden.',
  Locale.enUs: 'Yeet someone.',
  Locale.esEs: 'Lanzar a alguien.',
  Locale.fr: 'Balancer quelqu\'un.',
  Locale.ru: 'Швырнуть кого-то.',
  Locale.hi: 'किसी को फेंको।',
  Locale.zhCn: '扔掉某人。',
  Locale.ja: '誰かを投げ飛ばす。',
  Locale.ko: '누군가를 던지다.'
}, (ChatContext context,
    [@Description('The user you want to yeet.', {
      Locale.da: 'Den bruger, du vil kaste.',
      Locale.de: 'Der Benutzer, den du wegschleudern willst.',
      Locale.enUs: 'The user you want to yeet.',
      Locale.esEs: 'El usuario que quieres lanzar.',
      Locale.fr: 'L\'utilisateur que vous voulez balancer.',
      Locale.ru: 'Пользователь, которого вы хотите швырнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप फेंकना चाहते हैं।',
      Locale.zhCn: '你想扔掉的用户。',
      Locale.ja: '投げ飛ばしたいユーザー。',
      Locale.ko: '던지고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/yeet');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'yeet_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'yeet_user_yeeted'))
                .replaceAll('&yeeted', user.username)
                .replaceAll('&yeet', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in yeet command: $e');
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
