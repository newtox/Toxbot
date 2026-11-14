import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final cry = ChatCommand('cry', 'Cry.', localizedDescriptions: {
  Locale.da: 'Græd.',
  Locale.de: 'Weinen.',
  Locale.enUs: 'Cry.',
  Locale.esEs: 'Llorar.',
  Locale.fr: 'Pleurer.',
  Locale.ru: 'Плакать.',
  Locale.hi: 'रोएँ।',
  Locale.zhCn: '哭泣。',
  Locale.ja: '泣きます。',
  Locale.ko: '웁니다.'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('cry');

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'cry_user')),
          image: EmbedImageBuilder(url: imageUrl))
    ]));
  } catch (e) {
    print('Error in cry command: $e');
    await replyException(context, e);
    return;
  }
});
