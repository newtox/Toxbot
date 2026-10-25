import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final cuddle = ChatCommand('cuddle', 'Cuddle someone.', localizedDescriptions: {
  Locale.da: 'Kæl for nogen.',
  Locale.de: 'Mit jemandem kuscheln.',
  Locale.enUs: 'Cuddle someone.',
  Locale.esEs: 'Acurrucarse con alguien.',
  Locale.fr: 'Câliner quelqu\'un.',
  Locale.ru: 'Прижаться к кому-нибудь.',
  Locale.hi: 'किसी से लिपटें।',
  Locale.zhCn: '依偎某人。',
  Locale.ja: '誰かに寄り添います。',
  Locale.ko: '누군가와 꼭 붙어 있습니다.'
}, (ChatContext context,
    [@Description('The user you want to cuddle.', {
      Locale.da: 'Brugeren, du vil kæle for.',
      Locale.de: 'Der Nutzer, mit dem du kuscheln möchtest.',
      Locale.enUs: 'The user you want to cuddle.',
      Locale.esEs: 'El usuario con el que quieres acurrucarte.',
      Locale.fr: 'L\'utilisateur que tu veux câliner.',
      Locale.ru: 'Пользователь, к которому хочешь прижаться.',
      Locale.hi: 'वह उपयोगकर्ता जिससे आप लिपटना चाहते हैं।',
      Locale.zhCn: '你想依偎的用户。',
      Locale.ja: '寄り添いたいユーザー。',
      Locale.ko: '꼭 붙어 있을 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/cuddle');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user_cuddled'))
                .replaceAll('&cuddled', user.username)
                .replaceAll('&cuddle', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in cuddle command: $e');
    await replyException(context, e);
    return;
  }
});
