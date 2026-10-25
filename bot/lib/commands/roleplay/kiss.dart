import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final kiss = ChatCommand('kiss', 'Kiss someone.', localizedDescriptions: {
  Locale.da: 'Kys nogen.',
  Locale.de: 'Jemanden küssen.',
  Locale.enUs: 'Kiss someone.',
  Locale.esEs: 'Besar a alguien.',
  Locale.fr: 'Embrasser quelqu\'un.',
  Locale.ru: 'Поцеловать кого-нибудь.',
  Locale.hi: 'किसी को चूमें।',
  Locale.zhCn: '亲吻某人。',
  Locale.ja: '誰かにキスします。',
  Locale.ko: '누군가에게 키스합니다.'
}, (ChatContext context,
    [@Description('The user you want to kiss.', {
      Locale.da: 'Brugeren, du vil kysse.',
      Locale.de: 'Der Nutzer, den du küssen möchtest.',
      Locale.enUs: 'The user you want to kiss.',
      Locale.esEs: 'El usuario al que quieres besar.',
      Locale.fr: 'L\'utilisateur que tu veux embrasser.',
      Locale.ru: 'Пользователь, которого хочешь поцеловать.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप चूमना चाहते हैं।',
      Locale.zhCn: '你想亲吻的用户。',
      Locale.ja: 'キスしたいユーザー。',
      Locale.ko: '키스할 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/kiss');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kiss_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kiss_user_kissed'))
                .replaceAll('&kissed', user.username)
                .replaceAll('&kiss', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in kiss command: $e');
    await replyException(context, e);
    return;
  }
});
