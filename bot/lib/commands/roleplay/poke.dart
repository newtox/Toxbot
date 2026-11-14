import 'package:nyxx/nyxx.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final poke = ChatCommand('poke', 'Poke someone.', localizedDescriptions: {
  Locale.da: 'Prik til nogen.',
  Locale.de: 'Jemanden anstupsen.',
  Locale.enUs: 'Poke someone.',
  Locale.esEs: 'Dar un toque a alguien.',
  Locale.fr: 'Taquiner quelqu\'un du doigt.',
  Locale.ru: 'Ткнуть кого-нибудь.',
  Locale.hi: 'किसी को कोंचें।',
  Locale.zhCn: '戳某人。',
  Locale.ja: '誰かをつつきます。',
  Locale.ko: '누군가를 콕 찌릅니다.'
}, (ChatContext context,
    [@Description('The user you want to poke.', {
      Locale.da: 'Brugeren, du vil prikke til.',
      Locale.de: 'Der Nutzer, den du anstupsen möchtest.',
      Locale.enUs: 'The user you want to poke.',
      Locale.esEs: 'El usuario al que quieres dar un toque.',
      Locale.fr: 'L\'utilisateur que tu veux taquiner du doigt.',
      Locale.ru: 'Пользователь, которого хочешь ткнуть.',
      Locale.hi: 'वह उपयोगकर्ता जिसे आप कोंचना चाहते हैं।',
      Locale.zhCn: '你想戳的用户。',
      Locale.ja: 'つつきたいユーザー。',
      Locale.ko: '콕 찌를 사용자.'
    })
    User? user]) async {
  try {
    final imageUrl = await fetchNekos('poke');

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'poke_user')),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'poke_user_poked'))
                .replaceAll('&poked', escapeMarkdown(user.username))
                .replaceAll('&poke', escapeMarkdown(context.user.username)),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    }
  } catch (e) {
    print('Error in poke command: $e');
    await replyException(context, e);
    return;
  }
});
