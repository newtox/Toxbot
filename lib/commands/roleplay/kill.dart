import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final kill = ChatCommand('kill', 'Kill someone.', localizedDescriptions: {
  Locale.da: 'Dræb nogen.',
  Locale.de: 'Töte jemanden.',
  Locale.enUs: 'Kill someone.',
  Locale.esEs: 'Matar a alguien.',
  Locale.fr: 'Tuer quelqu\'un.',
  Locale.ru: 'Убить кого-то.',
  Locale.hi: 'किसी को मारो।',
  Locale.zhCn: '杀死某人。',
  Locale.ja: '誰かを殺す。',
  Locale.ko: '누군가를 죽이다.'
}, (ChatContext context,
    [@Description('The user you want to kill.', {
      Locale.da: 'Den bruger, du vil dræbe.',
      Locale.de: 'Der Benutzer, den du töten willst.',
      Locale.enUs: 'The user you want to kill.',
      Locale.esEs: 'El usuario que quieres matar.',
      Locale.fr: 'L\'utilisateur que vous voulez tuer.',
      Locale.ru: 'Пользователь, которого вы хотите убить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप मारना चाहते हैं।',
      Locale.zhCn: '你想杀死的用户。',
      Locale.ja: '殺したいユーザー。',
      Locale.ko: '죽이고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/kill');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user_killed'))
                .replaceAll('&killed', user.username)
                .replaceAll('&kill', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in kill command: $e');
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
