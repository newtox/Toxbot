import 'package:nyxx/nyxx.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
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
    final imageUrl = await fetchNekos('kiss');

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kiss_user')),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kiss_user_kissed'))
                .replaceAll('&kissed', escapeMarkdown(user.username))
                .replaceAll('&kiss', escapeMarkdown(context.user.username)),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    }
  } catch (e) {
    print('Error in kiss command: $e');
    await replyException(context, e);
    return;
  }
});
