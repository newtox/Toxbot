import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final nom = ChatCommand('nom', 'Nom someone.', localizedDescriptions: {
  Locale.da: 'Gnask på nogen.',
  Locale.de: 'Jemanden anknabbern.',
  Locale.enUs: 'Nom someone.',
  Locale.esEs: 'Mordisquear a alguien.',
  Locale.fr: 'Grignoter quelqu\'un.',
  Locale.ru: 'Покусать кого-нибудь.',
  Locale.hi: 'किसी को कुतरें।',
  Locale.zhCn: '啃某人。',
  Locale.ja: '誰かをかじります。',
  Locale.ko: '누군가를 냠냠합니다.'
}, (ChatContext context,
    [@Description('The user you want to nom.', {
      Locale.da: 'Brugeren, du vil gnaske på.',
      Locale.de: 'Der Nutzer, den du anknabbern möchtest.',
      Locale.enUs: 'The user you want to nom.',
      Locale.esEs: 'El usuario al que quieres mordisquear.',
      Locale.fr: 'L\'utilisateur que tu veux grignoter.',
      Locale.ru: 'Пользователь, которого хочешь покусать.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप कुतरना चाहते हैं।',
      Locale.zhCn: '你想啃的用户。',
      Locale.ja: 'かじりたいユーザー。',
      Locale.ko: '냠냠할 사용자.'
    })
    User? user]) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/nom');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'nom_user')),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'nom_user_nommed'))
                .replaceAll('&nommed', user.username)
                .replaceAll('&nom', context.user.username),
            image: EmbedImageBuilder(url: httpPackageResult))
      ]));
    }
  } catch (e) {
    print('Error in nom command: $e');
    await replyException(context, e);
    return;
  }
});
