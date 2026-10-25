import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final highfive =
    ChatCommand('highfive', 'High five someone.', localizedDescriptions: {
  Locale.da: 'Giv nogen en high five.',
  Locale.de: 'Jemandem ein High Five geben.',
  Locale.enUs: 'High five someone.',
  Locale.esEs: 'Chocar los cinco con alguien.',
  Locale.fr: 'Taper dans la main de quelqu\'un.',
  Locale.ru: 'Дать кому-нибудь пять.',
  Locale.hi: 'किसी को हाई फ़ाइव दें।',
  Locale.zhCn: '和某人击掌。',
  Locale.ja: '誰かとハイタッチします。',
  Locale.ko: '누군가와 하이파이브합니다.'
}, (ChatContext context,
        [@Description('The user you want to high five.', {
          Locale.da: 'Brugeren, du vil give en high five.',
          Locale.de: 'Der Nutzer, dem du ein High Five geben möchtest.',
          Locale.enUs: 'The user you want to high five.',
          Locale.esEs: 'El usuario con el que quieres chocar los cinco.',
          Locale.fr: 'L\'utilisateur avec qui tu veux taper dans la main.',
          Locale.ru: 'Пользователь, которому хочешь дать пять.',
          Locale.hi: 'वह उपयोगकर्ता जिसे आप हाई फ़ाइव देना चाहते हैं।',
          Locale.zhCn: '你想击掌的用户。',
          Locale.ja: 'ハイタッチしたいユーザー。',
          Locale.ko: '하이파이브할 사용자.'
        })
        User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/highfive');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'highfive_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'highfive_user_fived'))
                .replaceAll('&fived', user.username)
                .replaceAll('&five', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in highfive command: $e');
    await replyException(context, e);
    return;
  }
});
