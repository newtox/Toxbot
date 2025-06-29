import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final poke = ChatCommand('poke', 'Poke someone.', localizedDescriptions: {
  Locale.da: 'Prik til nogen.',
  Locale.de: 'Stupse jemanden an.',
  Locale.enUs: 'Poke someone.',
  Locale.esEs: 'Tocar a alguien.',
  Locale.fr: 'Pousser quelqu\'un.',
  Locale.ru: 'Тыкнуть в кого-то.',
  Locale.hi: 'किसी को चुटकी काटो।',
  Locale.zhCn: '戳某人。',
  Locale.ja: '誰かをつつく。',
  Locale.ko: '누군가를 찌르다.'
}, (ChatContext context,
    [@Description('The user you want to poke.', {
      Locale.da: 'Den bruger, du vil prikke til.',
      Locale.de: 'Der Benutzer, den du anstupsen willst.',
      Locale.enUs: 'The user you want to poke.',
      Locale.esEs: 'El usuario que quieres tocar.',
      Locale.fr: 'L\'utilisateur que vous voulez pousser.',
      Locale.ru: 'Пользователь, в которого вы хотите тыкнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप चुटकी काटना चाहते हैं।',
      Locale.zhCn: '你想戳的用户。',
      Locale.ja: 'つつきたいユーザー。',
      Locale.ko: '찌르고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/poke');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'poke_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'poke_user_poked'))
                .replaceAll('&poked', user.username)
                .replaceAll('&poke', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in poke command: $e');
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
