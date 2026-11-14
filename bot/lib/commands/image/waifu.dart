import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/nekos.dart';
import 'package:toxbot/utils/reply.dart';

final waifu =
    ChatCommand('waifu', 'Show a waifu picture.', localizedDescriptions: {
  Locale.da: 'Vis et waifu-billede.',
  Locale.de: 'Ein Waifu-Bild anzeigen.',
  Locale.enUs: 'Show a waifu picture.',
  Locale.esEs: 'Mostrar una imagen de waifu.',
  Locale.fr: 'Afficher une image de waifu.',
  Locale.ru: 'Показать картинку вайфу.',
  Locale.hi: 'एक waifu तस्वीर दिखाएँ।',
  Locale.zhCn: '显示一张老婆图片。',
  Locale.ja: '嫁の画像を表示します。',
  Locale.ko: 'waifu 이미지를 표시합니다.'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('waifu');

    final imageResponse = await downloadNekos(imageUrl);

    await context.respond(MessageBuilder(attachments: [
      AttachmentBuilder(
          data: imageResponse,
          fileName: imageUrl.pathSegments.last,
          description: imageUrl.host.toString())
    ]));
  } catch (e) {
    print('Error in waifu command: $e');
    await replyException(context, e);
    return;
  }
});
