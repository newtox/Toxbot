import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final neko =
    ChatCommand('neko', 'Displays a neko image.', localizedDescriptions: {
  Locale.da: 'Viser et neko-billede.',
  Locale.de: 'Zeigt ein Neko-Bild an.',
  Locale.enUs: 'Displays a neko image.',
  Locale.esEs: 'Muestra una imagen de neko.',
  Locale.fr: 'Affiche une image de neko.',
  Locale.ru: 'Отображает изображение неко.',
  Locale.hi: 'एक नेको छवि प्रदर्शित करता है।',
  Locale.zhCn: '显示一个猫娘图片。',
  Locale.ja: 'ネコの画像を表示します。',
  Locale.ko: '네코 이미지를 표시합니다.'
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
