import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final dance = ChatCommand('dance', 'Dance.', localizedDescriptions: {
  Locale.da: 'Dans.',
  Locale.de: 'Tanzen.',
  Locale.enUs: 'Dance.',
  Locale.esEs: 'Bailar.',
  Locale.fr: 'Danser.',
  Locale.ru: 'Танцевать.',
  Locale.hi: 'नाचें।',
  Locale.zhCn: '跳舞。',
  Locale.ja: '踊ります。',
  Locale.ko: '춤을 춥니다.'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('dance');

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'dance_user')),
          image: EmbedImageBuilder(url: imageUrl))
    ]));
  } catch (e) {
    print('Error in dance command: $e');
    await replyException(context, e);
    return;
  }
});
