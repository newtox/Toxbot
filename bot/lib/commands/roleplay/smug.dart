import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final smug = ChatCommand('smug', 'Look smug.', localizedDescriptions: {
  Locale.da: 'Se selvtilfreds ud.',
  Locale.de: 'Selbstgefällig gucken.',
  Locale.enUs: 'Look smug.',
  Locale.esEs: 'Poner cara de suficiencia.',
  Locale.fr: 'Prendre un air suffisant.',
  Locale.ru: 'Самодовольно ухмыльнуться.',
  Locale.hi: 'घमंडी अंदाज़ में मुस्कुराएँ।',
  Locale.zhCn: '得意一笑。',
  Locale.ja: 'ドヤ顔をします。',
  Locale.ko: '의기양양한 표정을 짓습니다.',
  Locale.es419: 'Poner cara de suficiencia.',
  Locale.ptBr: 'Fazer cara de convencido.',
  Locale.tr: 'Kendinden emin görün.',
  Locale.pl: 'Zrób zadowoloną minę.',
  Locale.it: 'Fai la faccia compiaciuta.',
  Locale.uk: 'Мати самовдоволений вигляд.',
  Locale.vi: 'Tỏ vẻ đắc ý.',
  Locale.th: 'ทำหน้าภูมิใจ',
  Locale.zhTw: '一臉得意。'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('smug');

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'smug_user')),
          image: EmbedImageBuilder(url: imageUrl))
    ]));
  } catch (e) {
    print('Error in smug command: $e');
    await replyException(context, e);
    return;
  }
});
