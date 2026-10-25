import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final bonk = ChatCommand('bonk', 'Bonk someone.', localizedDescriptions: {
  Locale.da: 'Bonk nogen.',
  Locale.de: 'Jemanden bonken.',
  Locale.enUs: 'Bonk someone.',
  Locale.esEs: 'Darle un bonk a alguien.',
  Locale.fr: 'Bonker quelqu\'un.',
  Locale.ru: 'Бонкнуть кого-нибудь.',
  Locale.hi: 'किसी को बोंक करें।',
  Locale.zhCn: '敲某人的头。',
  Locale.ja: '誰かをボンクします。',
  Locale.ko: '누군가를 봉크합니다.'
}, (ChatContext context,
    [@Description('The user you want to bonk.', {
      Locale.da: 'Brugeren, du vil bonke.',
      Locale.de: 'Der Nutzer, den du bonken möchtest.',
      Locale.enUs: 'The user you want to bonk.',
      Locale.esEs: 'El usuario al que quieres dar un bonk.',
      Locale.fr: 'L\'utilisateur que tu veux bonker.',
      Locale.ru: 'Пользователь, которого хочешь бонкнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप बोंक करना चाहते हैं।',
      Locale.zhCn: '你想敲头的用户。',
      Locale.ja: 'ボンクしたいユーザー。',
      Locale.ko: '봉크할 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/bonk');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bonk_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bonk_user_bonked'))
                .replaceAll('&bonked', user.username)
                .replaceAll('&bonk', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in bonk command: $e');
    await replyException(context, e);
    return;
  }
});
