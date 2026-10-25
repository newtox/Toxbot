import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final pat = ChatCommand('pat', 'Pat someone.', localizedDescriptions: {
  Locale.da: 'Ae nogen.',
  Locale.de: 'Jemanden streicheln.',
  Locale.enUs: 'Pat someone.',
  Locale.esEs: 'Acariciar a alguien.',
  Locale.fr: 'Caresser quelqu\'un.',
  Locale.ru: 'Погладить кого-нибудь.',
  Locale.hi: 'किसी को थपथपाएँ।',
  Locale.zhCn: '摸摸某人。',
  Locale.ja: '誰かをなでます。',
  Locale.ko: '누군가를 쓰다듬습니다.'
}, (ChatContext context,
    [@Description('The user you want to pat.', {
      Locale.da: 'Brugeren, du vil ae.',
      Locale.de: 'Der Nutzer, den du streicheln möchtest.',
      Locale.enUs: 'The user you want to pat.',
      Locale.esEs: 'El usuario al que quieres acariciar.',
      Locale.fr: 'L\'utilisateur que tu veux caresser.',
      Locale.ru: 'Пользователь, которого хочешь погладить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप थपथपाना चाहते हैं।',
      Locale.zhCn: '你想摸摸的用户。',
      Locale.ja: 'なでたいユーザー。',
      Locale.ko: '쓰다듬을 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/pat');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'pat_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'pat_user_patted'))
                .replaceAll('&patted', user.username)
                .replaceAll('&pat', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in pat command: $e');
    await replyException(context, e);
    return;
  }
});
