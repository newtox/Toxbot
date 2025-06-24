import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final bonk = ChatCommand('bonk', 'Bonk someone.', localizedDescriptions: {
  Locale.da: 'Bonk nogen.',
  Locale.de: 'Bonke jemanden.',
  Locale.enUs: 'Bonk someone.',
  Locale.esEs: 'Golpear a alguien.',
  Locale.fr: 'Frapper quelqu\'un.',
  Locale.ru: 'Стукнуть кого-то.',
  Locale.hi: 'किसी को बोंक करो।',
  Locale.zhCn: '敲打某人。',
  Locale.ja: '誰かを叩く。',
  Locale.ko: '누군가를 때리다.'
}, (ChatContext context,
    [@Description('The user you want to bonk.', {
      Locale.da: 'Den bruger, du vil bonke.',
      Locale.de: 'Der Benutzer, den du bonken willst.',
      Locale.enUs: 'The user you want to bonk.',
      Locale.esEs: 'El usuario que quieres golpear.',
      Locale.fr: 'L\'utilisateur que vous voulez frapper.',
      Locale.ru: 'Пользователь, которого вы хотите стукнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप बोंक करना चाहते हैं।',
      Locale.zhCn: '你想敲打的用户。',
      Locale.ja: '叩きたいユーザー。',
      Locale.ko: '때리고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/bonk');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bonk_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bonk_user_bonked'))
                .replaceAll('&bonked', user.username)
                .replaceAll('&bonk', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in bonk command: $e');
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
