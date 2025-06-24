import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final cuddle = ChatCommand('cuddle', 'Cuddle someone.', localizedDescriptions: {
  Locale.da: 'Putte med nogen.',
  Locale.de: 'Kuschel jemanden.',
  Locale.enUs: 'Cuddle someone.',
  Locale.esEs: 'Abrazar a alguien.',
  Locale.fr: 'Câliner quelqu\'un.',
  Locale.ru: 'Обнимать кого-то.',
  Locale.hi: 'किसी को गले लगाओ।',
  Locale.zhCn: '拥抱某人。',
  Locale.ja: '誰かと抱き合う。',
  Locale.ko: '누군가를 껴안다.'
}, (ChatContext context,
    [@Description('The user you want to cuddle.', {
      Locale.da: 'Den bruger, du vil putte med.',
      Locale.de: 'Der Benutzer, den du kuscheln willst.',
      Locale.enUs: 'The user you want to cuddle.',
      Locale.esEs: 'El usuario que quieres abrazar.',
      Locale.fr: 'L\'utilisateur que vous voulez câliner.',
      Locale.ru: 'Пользователь, которого вы хотите обнять.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप गले लगाना चाहते हैं।',
      Locale.zhCn: '你想拥抱的用户。',
      Locale.ja: '抱き合いたいユーザー。',
      Locale.ko: '껴안고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/cuddle');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user_cuddled'))
                .replaceAll('&cuddled', user.username)
                .replaceAll('&cuddle', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in cuddle command: $e');
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
