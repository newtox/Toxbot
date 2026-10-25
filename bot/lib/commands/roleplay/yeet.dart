import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final yeet = ChatCommand('yeet', 'Yeet someone.', localizedDescriptions: {
  Locale.da: 'Kast nogen væk.',
  Locale.de: 'Jemanden wegschleudern.',
  Locale.enUs: 'Yeet someone.',
  Locale.esEs: 'Lanzar a alguien por los aires.',
  Locale.fr: 'Balancer quelqu\'un.',
  Locale.ru: 'Швырнуть кого-нибудь.',
  Locale.hi: 'किसी को दूर उछालें।',
  Locale.zhCn: '把某人扔飞。',
  Locale.ja: '誰かを投げ飛ばします。',
  Locale.ko: '누군가를 던져 버립니다.'
}, (ChatContext context,
    [@Description('The user you want to yeet.', {
      Locale.da: 'Brugeren, du vil kaste væk.',
      Locale.de: 'Der Nutzer, den du wegschleudern möchtest.',
      Locale.enUs: 'The user you want to yeet.',
      Locale.esEs: 'El usuario al que quieres lanzar por los aires.',
      Locale.fr: 'L\'utilisateur que tu veux balancer.',
      Locale.ru: 'Пользователь, которого хочешь швырнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप दूर उछालना चाहते हैं।',
      Locale.zhCn: '你想扔飞的用户。',
      Locale.ja: '投げ飛ばしたいユーザー。',
      Locale.ko: '던져 버릴 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/yeet');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'yeet_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'yeet_user_yeeted'))
                .replaceAll('&yeeted', user.username)
                .replaceAll('&yeet', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in yeet command: $e');
    await replyException(context, e);
    return;
  }
});
