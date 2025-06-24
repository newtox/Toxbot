import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final slap = ChatCommand('slap', 'Slap someone.', localizedDescriptions: {
  Locale.da: 'Giv nogen en lussing.',
  Locale.de: 'Ohrfeige jemanden.',
  Locale.enUs: 'Slap someone.',
  Locale.esEs: 'Abofetear a alguien.',
  Locale.fr: 'Gifler quelqu\'un.',
  Locale.ru: 'Дать пощёчину кому-то.',
  Locale.hi: 'किसी को थप्पड़ मारो।',
  Locale.zhCn: '打某人耳光。',
  Locale.ja: '誰かを平手打ちする。',
  Locale.ko: '누군가를 때리다.'
}, (ChatContext context,
    [@Description('The user you want to slap.', {
      Locale.da: 'Den bruger, du vil give en lussing.',
      Locale.de: 'Der Benutzer, den du ohrfeigen willst.',
      Locale.enUs: 'The user you want to slap.',
      Locale.esEs: 'El usuario que quieres abofetear.',
      Locale.fr: 'L\'utilisateur que vous voulez gifler.',
      Locale.ru: 'Пользователь, которому вы хотите дать пощёчину.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप थप्पड़ मारना चाहते हैं।',
      Locale.zhCn: '你想打耳光的用户。',
      Locale.ja: '平手打ちしたいユーザー。',
      Locale.ko: '때리고 싶은 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/slap');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'slap_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'slap_user_slapped'))
                .replaceAll('&slapped', user.username)
                .replaceAll('&slap', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in slap command: $e');
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
