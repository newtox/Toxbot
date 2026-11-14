import 'package:nyxx/nyxx.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final bite = ChatCommand('bite', 'Bite someone.', localizedDescriptions: {
  Locale.da: 'Bid nogen.',
  Locale.de: 'Jemanden beißen.',
  Locale.enUs: 'Bite someone.',
  Locale.esEs: 'Morder a alguien.',
  Locale.fr: 'Mordre quelqu\'un.',
  Locale.ru: 'Укусить кого-нибудь.',
  Locale.hi: 'किसी को काटें।',
  Locale.zhCn: '咬某人。',
  Locale.ja: '誰かを噛みます。',
  Locale.ko: '누군가를 깨뭅니다.'
}, (ChatContext context,
    [@Description('The user you want to bite.', {
      Locale.da: 'Brugeren, du vil bide.',
      Locale.de: 'Der Nutzer, den du beißen möchtest.',
      Locale.enUs: 'The user you want to bite.',
      Locale.esEs: 'El usuario al que quieres morder.',
      Locale.fr: 'L\'utilisateur que tu veux mordre.',
      Locale.ru: 'Пользователь, которого хочешь укусить.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप काटना चाहते हैं।',
      Locale.zhCn: '你想咬的用户。',
      Locale.ja: '噛みたいユーザー。',
      Locale.ko: '깨물 사용자.'
    })
    User? user]) async {
  try {
    final imageUrl = await fetchNekos('bite');

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bite_user')),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'bite_user_bitten'))
                .replaceAll('&bitten', escapeMarkdown(user.username))
                .replaceAll('&bite', escapeMarkdown(context.user.username)),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    }
  } catch (e) {
    print('Error in bite command: $e');
    await replyException(context, e);
    return;
  }
});
