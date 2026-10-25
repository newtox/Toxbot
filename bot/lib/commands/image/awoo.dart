import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/reply.dart';

final awoo =
    ChatCommand('awoo', 'Show an awoo picture.', localizedDescriptions: {
  Locale.da: 'Vis et awoo-billede.',
  Locale.de: 'Ein Awoo-Bild anzeigen.',
  Locale.enUs: 'Show an awoo picture.',
  Locale.esEs: 'Mostrar una imagen de awoo.',
  Locale.fr: 'Afficher une image d\'awoo.',
  Locale.ru: 'Показать картинку awoo.',
  Locale.hi: 'एक awoo तस्वीर दिखाएँ।',
  Locale.zhCn: '显示一张 awoo 图片。',
  Locale.ja: 'awoo の画像を表示します。',
  Locale.ko: 'awoo 이미지를 표시합니다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/awoo');
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
    print('Error in awoo command: $e');
    await replyException(context, e);
    return;
  }
});
