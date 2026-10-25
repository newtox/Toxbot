import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/reply.dart';

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
  Locale.ko: 'neko 이미지를 표시합니다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/neko');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    final imageResponse = (await http.get(httpPackageResult)).bodyBytes;

    await context.respond(MessageBuilder(attachments: [
      AttachmentBuilder(
          data: imageResponse,
          fileName: httpPackageResult.path.toString().substring(1),
          description: httpPackageResult.host.toString())
    ]));
  } catch (e) {
    print('Error in neko command: $e');
    await replyException(context, e);
    return;
  }
});
