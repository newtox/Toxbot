import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
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
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/waifu');
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
    print('Error in waifu command: $e');
    await replyException(context, e);
    return;
  }
});
