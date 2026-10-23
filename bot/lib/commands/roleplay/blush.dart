import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final blush = ChatCommand('blush', 'Blush.', localizedDescriptions: {
  Locale.da: 'Rødme.',
  Locale.de: 'Erröte.',
  Locale.enUs: 'Blush.',
  Locale.esEs: 'Sonrojarse.',
  Locale.fr: 'Rougir.',
  Locale.ru: 'Краснеть.',
  Locale.hi: 'शर्माना.',
  Locale.zhCn: '脸红.',
  Locale.ja: '赤面する.',
  Locale.ko: '얼굴이 빨개지다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/blush');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'blush_user')),
          image: EmbedImageBuilder(url: httpPackageResult))
    ]));
  } catch (e) {
    print('Error in blush command: $e');
    await replyException(context, e);
    return;
  }
});
