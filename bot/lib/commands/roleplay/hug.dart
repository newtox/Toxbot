import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final hug = ChatCommand('hug', 'Hug someone.', localizedDescriptions: {
  Locale.da: 'Kram nogen.',
  Locale.de: 'Jemanden umarmen.',
  Locale.enUs: 'Hug someone.',
  Locale.esEs: 'Abrazar a alguien.',
  Locale.fr: 'Faire un câlin à quelqu\'un.',
  Locale.ru: 'Обнять кого-нибудь.',
  Locale.hi: 'किसी को गले लगाएँ।',
  Locale.zhCn: '拥抱某人。',
  Locale.ja: '誰かをハグします。',
  Locale.ko: '누군가를 안아 줍니다.'
}, (ChatContext context,
    [@Description('The user you want to hug.', {
      Locale.da: 'Brugeren, du vil kramme.',
      Locale.de: 'Der Nutzer, den du umarmen möchtest.',
      Locale.enUs: 'The user you want to hug.',
      Locale.esEs: 'El usuario al que quieres abrazar.',
      Locale.fr: 'L\'utilisateur à qui tu veux faire un câlin.',
      Locale.ru: 'Пользователь, которого хочешь обнять.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप गले लगाना चाहते हैं।',
      Locale.zhCn: '你想拥抱的用户。',
      Locale.ja: 'ハグしたいユーザー。',
      Locale.ko: '안아 줄 사용자.'
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
    await replyException(context, e);
    return;
  }
});
