import 'package:nyxx/nyxx.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final cuddle = ChatCommand('cuddle', 'Cuddle someone.', localizedDescriptions: {
  Locale.da: 'Kæl for nogen.',
  Locale.de: 'Mit jemandem kuscheln.',
  Locale.enUs: 'Cuddle someone.',
  Locale.esEs: 'Acurrucarse con alguien.',
  Locale.fr: 'Câliner quelqu\'un.',
  Locale.ru: 'Прижаться к кому-нибудь.',
  Locale.hi: 'किसी से लिपटें।',
  Locale.zhCn: '依偎某人。',
  Locale.ja: '誰かに寄り添います。',
  Locale.ko: '누군가와 꼭 붙어 있습니다.',
  Locale.es419: 'Acurrucarse con alguien.',
  Locale.ptBr: 'Fazer carinho em alguém.',
  Locale.tr: 'Birine sarıl ve sokul.',
  Locale.pl: 'Przytul się do kogoś.',
  Locale.it: 'Coccola qualcuno.',
  Locale.uk: 'Пригорнутися до когось.',
  Locale.vi: 'Rúc vào ai đó.',
  Locale.th: 'ซุกกอดใครสักคน',
  Locale.zhTw: '依偎著某人。'
}, (ChatContext context,
    [@Description('The user you want to cuddle.', {
      Locale.da: 'Brugeren, du vil kæle for.',
      Locale.de: 'Der Nutzer, mit dem du kuscheln möchtest.',
      Locale.enUs: 'The user you want to cuddle.',
      Locale.esEs: 'El usuario con el que quieres acurrucarte.',
      Locale.fr: 'L\'utilisateur que tu veux câliner.',
      Locale.ru: 'Пользователь, к которому хочешь прижаться.',
      Locale.hi: 'वह उपयोगकर्ता जिससे आप लिपटना चाहते हैं।',
      Locale.zhCn: '你想依偎的用户。',
      Locale.ja: '寄り添いたいユーザー。',
      Locale.ko: '꼭 붙어 있을 사용자.',
      Locale.es419: 'El usuario con el que quieres acurrucarte.',
      Locale.ptBr: 'O usuário em quem você quer fazer carinho.',
      Locale.tr: 'Sokulmak istediğin kullanıcı.',
      Locale.pl: 'Użytkownik, do którego chcesz się przytulić.',
      Locale.it: 'L\'utente che vuoi coccolare.',
      Locale.uk: 'Користувач, до якого хочеш пригорнутися.',
      Locale.vi: 'Người bạn muốn rúc vào.',
      Locale.th: 'ผู้ใช้ที่อยากซุกกอด',
      Locale.zhTw: '你想依偎的使用者。'
    })
    User? user]) async {
  try {
    final imageUrl = await fetchNekos('cuddle');

    if (user == null || user.id == context.user.id) {
      await context.respond(MessageBuilder(embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user')),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    } else {
      await context.respond(MessageBuilder(content: '<@${user.id}>', embeds: [
        EmbedBuilder(
            color: await getUserColorFromDatabase(context.user.id),
            title: (await getString(context.user, 'cuddle_user_cuddled'))
                .replaceAll('&cuddled', escapeMarkdown(user.username))
                .replaceAll('&cuddle', escapeMarkdown(context.user.username)),
            image: EmbedImageBuilder(url: imageUrl))
      ]));
    }
  } catch (e) {
    print('Error in cuddle command: $e');
    await replyException(context, e);
    return;
  }
});
