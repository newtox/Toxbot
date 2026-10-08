import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final lick = ChatCommand('lick', 'Lick someone.', localizedDescriptions: {
  Locale.da: 'Slik nogen.',
  Locale.de: 'Lecke jemanden ab.',
  Locale.enUs: 'Lick someone.',
  Locale.esEs: 'Lamer a alguien.',
  Locale.fr: 'Lécher quelqu\'un.',
  Locale.ru: 'Лизнуть кого-то.',
  Locale.hi: 'किसी को चाटो।',
  Locale.zhCn: '舔某人。',
  Locale.ja: '誰かを舐める。',
  Locale.ko: '누군가를 핥다.'
}, (ChatContext context,
    [@Description('The user you want to lick.', {
      Locale.da: 'Den bruger, du vil slikke.',
      Locale.de: 'Der Benutzer, den du ablecken willst.',
      Locale.enUs: 'The user you want to lick.',
      Locale.esEs: 'El usuario que quieres lamer.',
      Locale.fr: 'L\'utilisateur que vous voulez lécher.',
      Locale.ru: 'Пользователь, которого вы хотите лизнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप चाटना चाहते हैं।',
      Locale.zhCn: '你想舔的用户。',
      Locale.ja: '舐めたいユーザー。',
      Locale.ko: '핥고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/lick');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'lick_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'lick_user_licked'))
                .replaceAll('&licked', user.username)
                .replaceAll('&lick', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in lick command: $e');
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
