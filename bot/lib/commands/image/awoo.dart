import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';

final awoo =
    ChatCommand('awoo', 'Displays an awoo image.', localizedDescriptions: {
  Locale.da: 'Viser et awoo-billede.',
  Locale.de: 'Zeigt ein Awoo-Bild an.',
  Locale.enUs: 'Displays an awoo image.',
  Locale.esEs: 'Muestra una imagen de awoo.',
  Locale.fr: 'Affiche une image d\'awoo.',
  Locale.ru: 'Отображает изображение аwoo.',
  Locale.hi: 'एक आओ छवि प्रदर्शित करता है।',
  Locale.zhCn: '显示一个 Awoo 图像。',
  Locale.ja: 'アウーの画像を表示します。',
  Locale.ko: '아우 이미지를 표시합니다.'
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
    await context.respond(
        MessageBuilder(embeds: [
          EmbedBuilder(
              color: DiscordColor.parseHexString('#c41111'),
              title: await getString(context.user, 'global_error'),
              description: codeBlock(e.toString(), 'sh'))
        ]),
        level: ResponseLevel.hint);
    return;
  }
});
