import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final kill = ChatCommand('kill', 'Kill someone.', localizedDescriptions: {
  Locale.da: 'Dræb nogen.',
  Locale.de: 'Jemanden töten.',
  Locale.enUs: 'Kill someone.',
  Locale.esEs: 'Matar a alguien.',
  Locale.fr: 'Tuer quelqu\'un.',
  Locale.ru: 'Убить кого-нибудь.',
  Locale.hi: 'किसी को मारें।',
  Locale.zhCn: '干掉某人。',
  Locale.ja: '誰かを倒します。',
  Locale.ko: '누군가를 처치합니다.'
}, (ChatContext context,
    [@Description('The user you want to kill.', {
      Locale.da: 'Brugeren, du vil dræbe.',
      Locale.de: 'Der Nutzer, den du töten möchtest.',
      Locale.enUs: 'The user you want to kill.',
      Locale.esEs: 'El usuario al que quieres matar.',
      Locale.fr: 'L\'utilisateur que tu veux tuer.',
      Locale.ru: 'Пользователь, которого хочешь убить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप मारना चाहते हैं।',
      Locale.zhCn: '你想干掉的用户。',
      Locale.ja: '倒したいユーザー。',
      Locale.ko: '처치할 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/kill');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user_killed'))
                .replaceAll('&killed', user.username)
                .replaceAll('&kill', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in kill command: $e');
    await replyException(context, e);
    return;
  }
});
