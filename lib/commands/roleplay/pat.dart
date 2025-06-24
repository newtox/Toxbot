import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final pat = ChatCommand('pat', 'Pat someone.', localizedDescriptions: {
  Locale.da: 'Klap nogen.',
  Locale.de: 'Streichel jemanden.',
  Locale.enUs: 'Pat someone.',
  Locale.esEs: 'Acariciar a alguien.',
  Locale.fr: 'Caresser quelqu\'un.',
  Locale.ru: 'Погладить кого-то.',
  Locale.hi: 'किसी को थपथपाओ।',
  Locale.zhCn: '拍拍某人。',
  Locale.ja: '誰かを撫でる。',
  Locale.ko: '누군가를 쓰다듬다.'
}, (ChatContext context,
    [@Description('The user you want to pat.', {
      Locale.da: 'Den bruger, du vil klappe.',
      Locale.de: 'Der Benutzer, den du streicheln willst.',
      Locale.enUs: 'The user you want to pat.',
      Locale.esEs: 'El usuario que quieres acariciar.',
      Locale.fr: 'L\'utilisateur que vous voulez caresser.',
      Locale.ru: 'Пользователь, которого вы хотите погладить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप थपथपाना चाहते हैं।',
      Locale.zhCn: '你想拍拍的用户。',
      Locale.ja: '撫でたいユーザー。',
      Locale.ko: '쓰다듬고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/pat');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'pat_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'pat_user_patted'))
                .replaceAll('&patted', user.username)
                .replaceAll('&pat', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in pat command: $e');
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
