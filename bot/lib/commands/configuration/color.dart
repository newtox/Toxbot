import 'package:dotenv/dotenv.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/reply.dart';

var env = DotEnv(includePlatformEnvironment: true)..load();

final color = ChatCommand('color', 'Configure your color for embeds.',
    localizedDescriptions: {
      Locale.da: 'Konfigurér din farve til embeds.',
      Locale.de: 'Konfiguriere deine Farbe für Embeds.',
      Locale.enUs: 'Configure your color for embeds.',
      Locale.esEs: 'Configura tu color para incrustaciones.',
      Locale.fr: 'Configurez votre couleur pour les intégrations.',
      Locale.ru: 'Настройте цвет для встроенных элементов.',
      Locale.hi: 'एम्बेड के लिए अपना रंग कॉन्फ़िगर करें।',
      Locale.zhCn: '配置嵌入内容的颜色。',
      Locale.ja: '埋め込み用の色を設定してください。',
      Locale.ko: '임베드의 색상을 구성하세요.'
    }, (ChatContext context,
        @Description('The color for your embeds.', {
          Locale.da: 'Farven til dine embeds.',
          Locale.de: 'Die Farbe für deine Embeds.',
          Locale.enUs: 'The color for your embeds.',
          Locale.esEs: 'El color para tus incrustaciones.',
          Locale.fr: 'La couleur pour tes intégrations.',
          Locale.ru: 'Цвет для твоих встроенных элементов.',
          Locale.hi: 'तुम्हारे एम्बेड का रंग।',
          Locale.zhCn: '你嵌入内容的颜色。',
          Locale.ja: 'あなたの埋め込みの色。',
          Locale.ko: '네 임베드의 색상.'
        })
        String color) async {
  final connection = await getMySqlConnection();

  try {
    final matchesHex = RegExp(r'^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$');
    if (!matchesHex.hasMatch(color)) {
      await replyError(context, 'color_invalid_format');
      return;
    }

    await connection.query(
        'UPDATE `users` SET `color` = ? WHERE `id` = ? LIMIT 1;',
        [color, context.user.id.toString()]);
    await replySuccess(context, 'color_updated');
    return;
  } catch (e) {
    print('Error in color command: $e');
    await replyError(context, 'color_error',
        details: codeBlock(e.toString(), 'sh'));
    return;
  } finally {
    await connection.close();
  }
});
