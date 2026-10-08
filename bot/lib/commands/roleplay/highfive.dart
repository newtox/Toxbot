import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final highfive =
    ChatCommand('highfive', 'High five someone.', localizedDescriptions: {
  Locale.da: 'Giv high five til nogen.',
  Locale.de: 'Gib jemanden ein High Five.',
  Locale.enUs: 'High five someone.',
  Locale.esEs: 'Chocar los cinco con alguien.',
  Locale.fr: 'Faire un high five à quelqu\'un.',
  Locale.ru: 'Дать пять кому-то.',
  Locale.hi: 'किसी को हाई फाइव करो।',
  Locale.zhCn: '与某人击掌。',
  Locale.ja: '誰かとハイタッチする。',
  Locale.ko: '누군가와 하이파이브하다.'
}, (ChatContext context,
        [@Description('The user you want to high five.', {
          Locale.da: 'Den bruger, du vil give high five til.',
          Locale.de: 'Der Benutzer, dem du ein High Five geben willst.',
          Locale.enUs: 'The user you want to high five.',
          Locale.esEs: 'El usuario con quien quieres chocar los cinco.',
          Locale.fr: 'L\'utilisateur à qui vous voulez faire un high five.',
          Locale.ru: 'Пользователь, которому вы хотите дать пять.',
          Locale.hi: 'वह उपयोगकर्ता जिसे आप हाई फाइव करना चाहते हैं।',
          Locale.zhCn: '你想击掌的用户。',
          Locale.ja: 'ハイタッチしたいユーザー。',
          Locale.ko: '하이파이브하고 싶은 사용자.'
        })
        User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/highfive');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'highfive_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'highfive_user_fived'))
                .replaceAll('&fived', user.username)
                .replaceAll('&five', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in highfive command: $e');
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
