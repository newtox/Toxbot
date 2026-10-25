import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final lick = ChatCommand('lick', 'Lick someone.', localizedDescriptions: {
  Locale.da: 'Slik nogen.',
  Locale.de: 'Jemanden ablecken.',
  Locale.enUs: 'Lick someone.',
  Locale.esEs: 'Lamer a alguien.',
  Locale.fr: 'Lécher quelqu\'un.',
  Locale.ru: 'Лизнуть кого-нибудь.',
  Locale.hi: 'किसी को चाटें।',
  Locale.zhCn: '舔某人。',
  Locale.ja: '誰かをなめます。',
  Locale.ko: '누군가를 핥습니다.'
}, (ChatContext context,
    [@Description('The user you want to lick.', {
      Locale.da: 'Brugeren, du vil slikke.',
      Locale.de: 'Der Nutzer, den du ablecken möchtest.',
      Locale.enUs: 'The user you want to lick.',
      Locale.esEs: 'El usuario al que quieres lamer.',
      Locale.fr: 'L\'utilisateur que tu veux lécher.',
      Locale.ru: 'Пользователь, которого хочешь лизнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप चाटना चाहते हैं।',
      Locale.zhCn: '你想舔的用户。',
      Locale.ja: 'なめたいユーザー。',
      Locale.ko: '핥을 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/lick');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'lick_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'lick_user_licked'))
                .replaceAll('&licked', user.username)
                .replaceAll('&lick', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in lick command: $e');
    await replyException(context, e);
    return;
  }
});
