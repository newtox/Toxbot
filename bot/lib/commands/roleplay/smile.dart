import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final smile = ChatCommand('smile', 'Smile.', localizedDescriptions: {
  Locale.da: 'Smil.',
  Locale.de: 'Lächeln.',
  Locale.enUs: 'Smile.',
  Locale.esEs: 'Sonreír.',
  Locale.fr: 'Sourire.',
  Locale.ru: 'Улыбнуться.',
  Locale.hi: 'मुस्कुराएँ।',
  Locale.zhCn: '微笑。',
  Locale.ja: '笑顔になります。',
  Locale.ko: '미소 짓습니다.'
}, (ChatContext context) async {
  try {
    final httpPackageUrl = Uri.https('waifu.pics', 'api/sfw/smile');
    final httpPackageInfo = await http.get(httpPackageUrl);

    final httpPackageResponse =
        jsonDecode(utf8.decode(httpPackageInfo.bodyBytes)) as Map;
    final httpPackageResult = Uri.parse(httpPackageResponse['url'] as String);

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: (await getString(context.user, 'smile_user')),
          image: EmbedImageBuilder(url: httpPackageResult))
    ]));
  } catch (e) {
    print('Error in smile command: $e');
    await replyException(context, e);
    return;
  }
});
