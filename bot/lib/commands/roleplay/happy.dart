import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final happy = ChatCommand('happy', 'Be happy.', localizedDescriptions: {
  Locale.da: 'Vær glad.',
  Locale.de: 'Glücklich sein.',
  Locale.enUs: 'Be happy.',
  Locale.esEs: 'Estar feliz.',
  Locale.fr: 'Être heureux.',
  Locale.ru: 'Радоваться.',
  Locale.hi: 'खुश हों।',
  Locale.zhCn: '开心。',
  Locale.ja: '喜びます。',
  Locale.ko: '기뻐합니다.'
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
    await replyException(context, e);
    return;
  }
});
