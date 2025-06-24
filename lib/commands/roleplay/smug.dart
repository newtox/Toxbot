import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final smug = ChatCommand('smug', 'Be smug.', localizedDescriptions: {
  Locale.da: 'Vær selvtilfreds.',
  Locale.de: 'Selbstgefällig sein.',
  Locale.enUs: 'Be smug.',
  Locale.esEs: 'Ser presumido.',
  Locale.fr: 'Être suffisant.',
  Locale.ru: 'Быть самодовольным.',
  Locale.hi: 'घमंडी बनो।',
  Locale.zhCn: '得意.',
  Locale.ja: '得意げにする.',
  Locale.ko: '잘난 체하다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/smug');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'smug_user')),
          image: EmbedImageBuilder(url: httpPackageResult))
    ]));
  } catch (e) {
    print('Error in smug command: $e');
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
