import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final nom = ChatCommand('nom', 'Nom someone.', localizedDescriptions: {
  Locale.da: 'Spis nogen.',
  Locale.de: 'Knabbere jemanden an.',
  Locale.enUs: 'Nom someone.',
  Locale.esEs: 'Mordisquear a alguien.',
  Locale.fr: 'Grignoter quelqu\'un.',
  Locale.ru: 'Кусать кого-то.',
  Locale.hi: 'किसी को खाओ।',
  Locale.zhCn: '啃咬某人。',
  Locale.ja: '誰かを食べる。',
  Locale.ko: '누군가를 먹다.'
}, (ChatContext context,
    [@Description('The user you want to nom.', {
      Locale.da: 'Den bruger, du vil spise.',
      Locale.de: 'Der Benutzer, den du anknabbern willst.',
      Locale.enUs: 'The user you want to nom.',
      Locale.esEs: 'El usuario que quieres mordisquear.',
      Locale.fr: 'L\'utilisateur que vous voulez grignoter.',
      Locale.ru: 'Пользователь, которого вы хотите укусить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप खाना चाहते हैं।',
      Locale.zhCn: '你想啃咬的用户。',
      Locale.ja: '食べたいユーザー。',
      Locale.ko: '먹고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/nom');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'nom_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'nom_user_nommed'))
                .replaceAll('&nommed', user.username)
                .replaceAll('&nom', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in nom command: $e');
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
