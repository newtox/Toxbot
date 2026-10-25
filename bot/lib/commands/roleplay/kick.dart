import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final kick = ChatCommand('kick', 'Kick someone.', localizedDescriptions: {
  Locale.da: 'Spark nogen.',
  Locale.de: 'Jemanden treten.',
  Locale.enUs: 'Kick someone.',
  Locale.esEs: 'Patear a alguien.',
  Locale.fr: 'Donner un coup de pied à quelqu\'un.',
  Locale.ru: 'Пнуть кого-нибудь.',
  Locale.hi: 'किसी को लात मारें।',
  Locale.zhCn: '踢某人。',
  Locale.ja: '誰かを蹴ります。',
  Locale.ko: '누군가를 찹니다.'
}, (ChatContext context,
    [@Description('The user you want to kick.', {
      Locale.da: 'Brugeren, du vil sparke.',
      Locale.de: 'Der Nutzer, den du treten möchtest.',
      Locale.enUs: 'The user you want to kick.',
      Locale.esEs: 'El usuario al que quieres patear.',
      Locale.fr: 'L\'utilisateur à qui tu veux donner un coup de pied.',
      Locale.ru: 'Пользователь, которого хочешь пнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप लात मारना चाहते हैं।',
      Locale.zhCn: '你想踢的用户。',
      Locale.ja: '蹴りたいユーザー。',
      Locale.ko: '찰 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/kick');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kick_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kick_user_kicked'))
                .replaceAll('&kicked', user.username)
                .replaceAll('&kick', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in kick command: $e');
    await replyException(context, e);
    return;
  }
});
