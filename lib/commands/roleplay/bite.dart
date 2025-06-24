import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final bite = ChatCommand('bite', 'Bite someone.', localizedDescriptions: {
  Locale.da: 'Bid nogen.',
  Locale.de: 'Beiße jemanden.',
  Locale.enUs: 'Bite someone.',
  Locale.esEs: 'Morder a alguien.',
  Locale.fr: 'Mordre quelqu\'un.',
  Locale.ru: 'Укусить кого-то.',
  Locale.hi: 'किसी को काटो।',
  Locale.zhCn: '咬人。',
  Locale.ja: '誰かを噛む。',
  Locale.ko: '누군가를 물다.'
}, (ChatContext context,
    [@Description('The user you want to bite.', {
      Locale.da: 'Den bruger, du vil bide.',
      Locale.de: 'Der Benutzer, den du beißen willst.',
      Locale.enUs: 'The user you want to bite.',
      Locale.esEs: 'El usuario que quieres morder.',
      Locale.fr: 'L\'utilisateur que vous voulez mordre.',
      Locale.ru: 'Пользователь, которого вы хотите укусить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप काटना चाहते हैं।',
      Locale.zhCn: '你想咬的用户。',
      Locale.ja: '噛みたいユーザー。',
      Locale.ko: '물고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/bite');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bite_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bite_user_bitten'))
                .replaceAll('&bitten', user.username)
                .replaceAll('&bite', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in bite command: $e');
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
