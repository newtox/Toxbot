import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:shiori/utils/nekos.dart';
import 'package:shiori/utils/reply.dart';

final neko =
    ChatCommand('neko', 'Show a neko picture.', localizedDescriptions: {
  Locale.da: 'Vis et neko-billede.',
  Locale.de: 'Ein Neko-Bild anzeigen.',
  Locale.enUs: 'Show a neko picture.',
  Locale.esEs: 'Mostrar una imagen de neko.',
  Locale.fr: 'Afficher une image de neko.',
  Locale.ru: 'Показать картинку неко.',
  Locale.hi: 'एक neko तस्वीर दिखाएँ।',
  Locale.zhCn: '显示一张猫娘图片。',
  Locale.ja: 'ネコの画像を表示します。',
  Locale.ko: 'neko 이미지를 표시합니다.',
  Locale.es419: 'Mostrar una imagen de neko.',
  Locale.ptBr: 'Mostrar uma imagem de neko.',
  Locale.tr: 'Bir neko resmi göster.',
  Locale.pl: 'Pokaż obrazek neko.',
  Locale.it: 'Mostra un\'immagine neko.',
  Locale.uk: 'Показати картинку неко.',
  Locale.vi: 'Hiện một ảnh neko.',
  Locale.th: 'แสดงรูปเนโกะ',
  Locale.zhTw: '顯示貓娘圖片。'
}, (ChatContext context) async {
  try {
    final imageUrl = await fetchNekos('neko');

    final imageResponse = await downloadNekos(imageUrl);

    await context.respond(MessageBuilder(attachments: [
      AttachmentBuilder(
          data: imageResponse,
          fileName: imageUrl.pathSegments.last,
          description: imageUrl.host.toString())
    ]));
  } catch (e) {
    print('Error in neko command: $e');
    await replyException(context, e);
    return;
  }
});
