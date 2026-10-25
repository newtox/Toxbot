import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final bully = ChatCommand('bully', 'Bully someone.', localizedDescriptions: {
  Locale.da: 'Drill nogen.',
  Locale.de: 'Jemanden ärgern.',
  Locale.enUs: 'Bully someone.',
  Locale.esEs: 'Molestar a alguien.',
  Locale.fr: 'Embêter quelqu\'un.',
  Locale.ru: 'Задирать кого-нибудь.',
  Locale.hi: 'किसी को परेशान करें।',
  Locale.zhCn: '欺负某人。',
  Locale.ja: '誰かをからかいます。',
  Locale.ko: '누군가를 괴롭힙니다.'
}, (ChatContext context,
    [@Description('The user you want to bully.', {
      Locale.da: 'Brugeren, du vil drille.',
      Locale.de: 'Der Nutzer, den du ärgern möchtest.',
      Locale.enUs: 'The user you want to bully.',
      Locale.esEs: 'El usuario al que quieres molestar.',
      Locale.fr: 'L\'utilisateur que tu veux embêter.',
      Locale.ru: 'Пользователь, которого хочешь задирать.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप परेशान करना चाहते हैं।',
      Locale.zhCn: '你想欺负的用户。',
      Locale.ja: 'からかいたいユーザー。',
      Locale.ko: '괴롭힐 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/bully');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bully_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bully_user_bullied'))
                .replaceAll('&bullied', user.username)
                .replaceAll('&bully', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in bully command: $e');
    await replyException(context, e);
    return;
  }
});
