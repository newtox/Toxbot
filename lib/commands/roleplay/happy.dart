import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';

final happy = ChatCommand('happy', 'Be happy.', localizedDescriptions: {
  Locale.da: 'Vær glad.',
  Locale.de: 'Sei glücklich.',
  Locale.enUs: 'Be happy.',
  Locale.esEs: 'Estar feliz.',
  Locale.fr: 'Être heureux.',
  Locale.ru: 'Быть счастливым.',
  Locale.hi: 'खुश होना.',
  Locale.zhCn: '开心.',
  Locale.ja: '幸せになる.',
  Locale.ko: '행복하다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/happy');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'happy_user')),
          image: EmbedImageBuilder(url: httpPackageResult))
    ]));
  } catch (e) {
    print('Error in happy command: $e');
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
