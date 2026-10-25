import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final slap = ChatCommand('slap', 'Slap someone.', localizedDescriptions: {
  Locale.da: 'Giv nogen en lussing.',
  Locale.de: 'Jemanden ohrfeigen.',
  Locale.enUs: 'Slap someone.',
  Locale.esEs: 'Abofetear a alguien.',
  Locale.fr: 'Gifler quelqu\'un.',
  Locale.ru: 'Дать кому-нибудь пощёчину.',
  Locale.hi: 'किसी को थप्पड़ मारें।',
  Locale.zhCn: '扇某人耳光。',
  Locale.ja: '誰かをビンタします。',
  Locale.ko: '누군가의 뺨을 때립니다.'
}, (ChatContext context,
    [@Description('The user you want to slap.', {
      Locale.da: 'Brugeren, du vil give en lussing.',
      Locale.de: 'Der Nutzer, den du ohrfeigen möchtest.',
      Locale.enUs: 'The user you want to slap.',
      Locale.esEs: 'El usuario al que quieres abofetear.',
      Locale.fr: 'L\'utilisateur que tu veux gifler.',
      Locale.ru: 'Пользователь, которому хочешь дать пощёчину.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप थप्पड़ मारना चाहते हैं।',
      Locale.zhCn: '你想扇耳光的用户。',
      Locale.ja: 'ビンタしたいユーザー。',
      Locale.ko: '뺨을 때릴 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/slap');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'slap_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'slap_user_slapped'))
                .replaceAll('&slapped', user.username)
                .replaceAll('&slap', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in slap command: $e');
    await replyException(context, e);
    return;
  }
});
