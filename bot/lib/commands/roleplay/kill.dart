import 'package:nyxx/nyxx.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
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
  Locale.ko: '누군가를 처치합니다.',
  Locale.es419: 'Matar a alguien.',
  Locale.ptBr: 'Acabar com alguém.',
  Locale.tr: 'Birini ortadan kaldır.',
  Locale.pl: 'Wykończ kogoś.',
  Locale.it: 'Fai fuori qualcuno.'
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
      Locale.ko: '처치할 사용자.',
      Locale.es419: 'El usuario al que quieres matar.',
      Locale.ptBr: 'O usuário com quem você quer acabar.',
      Locale.tr: 'Ortadan kaldırmak istediğin kullanıcı.',
      Locale.pl: 'Użytkownik, którego chcesz wykończyć.',
      Locale.it: 'L\'utente che vuoi fare fuori.'
    })
    User? user]) async {
  try {
    final imageUrl = await fetchNekos('shoot');

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user')),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'kill_user_killed'))
                .replaceAll('&killed', escapeMarkdown(user.username))
                .replaceAll('&kill', escapeMarkdown(context.user.username)),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    }
  } catch (e) {
    print('Error in kill command: $e');
    await replyException(context, e);
    return;
  }
});
