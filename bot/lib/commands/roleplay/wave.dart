import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final wave = ChatCommand('wave', 'Wave.', localizedDescriptions: {
  Locale.da: 'Vink.',
  Locale.de: 'Winken.',
  Locale.enUs: 'Wave.',
  Locale.esEs: 'Saludar con la mano.',
  Locale.fr: 'Faire coucou.',
  Locale.ru: 'Помахать рукой.',
  Locale.hi: 'हाथ हिलाएँ।',
  Locale.zhCn: '挥手。',
  Locale.ja: '手を振ります。',
  Locale.ko: '손을 흔듭니다.'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('wave');

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'wave_user')),
          image: EmbedImageBuilder(url: imageUrl))
    ]));
  } catch (e) {
    print('Error in wave command: $e');
    await replyException(context, e);
    return;
  }
});
