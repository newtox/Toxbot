import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final hug = ChatCommand('hug', 'Hug someone.', localizedDescriptions: {
  Locale.da: 'Giv nogen et kram.',
  Locale.de: 'Umarm jemanden.',
  Locale.enUs: 'Hug someone.',
  Locale.esEs: 'Abraza a alguien.',
  Locale.fr: 'Embrasse quelqu\'un.',
  Locale.ru: 'Обними кого-нибудь.',
  Locale.hi: 'किसी को गले लगाओ।',
  Locale.zhCn: '拥抱某人。',
  Locale.ja: '誰かを抱きしめる。',
  Locale.ko: '누군가를 안아주세요.'
}, (ChatContext context,
    [@Description('The user you want to hug.', {
      Locale.da: 'Den bruger, du vil kramme.',
      Locale.de: 'Der Benutzer, den du umarmen möchtest.',
      Locale.enUs: 'The user you want to hug.',
      Locale.esEs: 'El usuario que deseas abrazar.',
      Locale.fr: 'L\'utilisateur que vous voulez embrasser.',
      Locale.ru: 'Пользователь, которого вы хотите обнять.',
      Locale.hi: 'जिसे आप गले लगाना चाहते हैं।',
      Locale.zhCn: '你想拥抱的用户。',
      Locale.ja: '抱きしめたいユーザー。',
      Locale.ko: '포옹하고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/hug');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'hug_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'hug_user_hugged'))
                .replaceAll('&hugged', user.username)
                .replaceAll('&hug', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in hug command: $e');
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
