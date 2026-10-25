import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final handhold =
    ChatCommand('handhold', 'Hold someone\'s hand.', localizedDescriptions: {
  Locale.da: 'Hold nogen i hånden.',
  Locale.de: 'Mit jemandem Händchen halten.',
  Locale.enUs: 'Hold someone\'s hand.',
  Locale.esEs: 'Tomar la mano de alguien.',
  Locale.fr: 'Tenir la main de quelqu\'un.',
  Locale.ru: 'Взять кого-нибудь за руку.',
  Locale.hi: 'किसी का हाथ पकड़ें।',
  Locale.zhCn: '牵某人的手。',
  Locale.ja: '誰かと手をつなぎます。',
  Locale.ko: '누군가의 손을 잡습니다.'
}, (ChatContext context,
        [@Description('The user whose hand you want to hold.', {
          Locale.da: 'Brugeren, du vil holde i hånden.',
          Locale.de: 'Der Nutzer, mit dem du Händchen halten möchtest.',
          Locale.enUs: 'The user whose hand you want to hold.',
          Locale.esEs: 'El usuario al que quieres tomar de la mano.',
          Locale.fr: 'L\'utilisateur dont tu veux tenir la main.',
          Locale.ru: 'Пользователь, которого хочешь взять за руку.',
          Locale.hi: 'वह उपयोगकर्ता जिसका हाथ आप पकड़ना चाहते हैं।',
          Locale.zhCn: '你想牵手的用户。',
          Locale.ja: '手をつなぎたいユーザー。',
          Locale.ko: '손을 잡을 사용자.'
        })
        User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/handhold');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'handhold_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'handhold_user_held'))
                .replaceAll('&held', user.username)
                .replaceAll('&hold', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in handhold command: $e');
    await replyException(context, e);
    return;
  }
});
