import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final cringe = ChatCommand('cringe', 'Cringe.', localizedDescriptions: {
  Locale.da: 'Krympe.',
  Locale.de: 'Zucke zusammen.',
  Locale.enUs: 'Cringe.',
  Locale.esEs: 'Estremecerse.',
  Locale.fr: 'Frémir.',
  Locale.ru: 'Съёживаться.',
  Locale.hi: 'सिहरना.',
  Locale.zhCn: '畏缩.',
  Locale.ja: '身震いする.',
  Locale.ko: '움츠리다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/cringe');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'cringe_user')),
          image: EmbedImageBuilder(url: httpPackageResult))
    ]));
  } catch (e) {
    print('Error in cringe command: $e');
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
