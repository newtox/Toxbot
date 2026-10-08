import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final handhold =
    ChatCommand('handhold', 'Hold someone\'s hand.', localizedDescriptions: {
  Locale.da: 'Hold i hånd med nogen.',
  Locale.de: 'Halte jemandes Hand.',
  Locale.enUs: 'Hold someone\'s hand.',
  Locale.esEs: 'Tomar la mano de alguien.',
  Locale.fr: 'Tenir la main de quelqu\'un.',
  Locale.ru: 'Держать кого-то за руку.',
  Locale.hi: 'किसी का हाथ पकड़ो।',
  Locale.zhCn: '牵某人的手。',
  Locale.ja: '誰かと手をつなぐ。',
  Locale.ko: '누군가의 손을 잡다.'
}, (ChatContext context,
        [@Description('The user whose hand you want to hold.', {
          Locale.da: 'Den bruger, hvis hånd du vil holde.',
          Locale.de: 'Der Benutzer, dessen Hand du halten willst.',
          Locale.enUs: 'The user whose hand you want to hold.',
          Locale.esEs: 'El usuario cuya mano quieres tomar.',
          Locale.fr: 'L\'utilisateur dont vous voulez tenir la main.',
          Locale.ru: 'Пользователь, чью руку вы хотите держать.',
          Locale.hi: 'वह उपयोगकर्ता जिसका हाथ आप पकड़ना चाहते हैं।',
          Locale.zhCn: '你想牵手的用户。',
          Locale.ja: '手をつなぎたいユーザー。',
          Locale.ko: '손을 잡고 싶은 사용자.'
        })
        User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/handhold');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'handhold_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'handhold_user_held'))
                .replaceAll('&held', user.username)
                .replaceAll('&hold', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in handhold command: $e');
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
