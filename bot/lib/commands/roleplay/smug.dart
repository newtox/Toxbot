import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final smug = ChatCommand('smug', 'Look smug.', localizedDescriptions: {
  Locale.da: 'Se selvtilfreds ud.',
  Locale.de: 'Selbstgefällig gucken.',
  Locale.enUs: 'Look smug.',
  Locale.esEs: 'Poner cara de suficiencia.',
  Locale.fr: 'Prendre un air suffisant.',
  Locale.ru: 'Самодовольно ухмыльнуться.',
  Locale.hi: 'घमंडी अंदाज़ में मुस्कुराएँ।',
  Locale.zhCn: '得意一笑。',
  Locale.ja: 'ドヤ顔をします。',
  Locale.ko: '의기양양한 표정을 짓습니다.'
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
    await replyException(context, e);
    return;
  }
});
