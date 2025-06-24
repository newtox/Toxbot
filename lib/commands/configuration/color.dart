import 'package:dotenv/dotenv.dart';
import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

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
      await context.respond(
          MessageBuilder(embeds: [
            EmbedBuilder(
                color: DiscordColor.parseHexString('#c41111'),
                title: await getString(context.user, 'global_error'),
                description:
                    await getString(context.user, 'color_invalid_format'))
          ]),
          level: ResponseLevel.hint);
      return;
    }

    await connection.query(
        'UPDATE `users` SET `color` = ? WHERE `id` = ? LIMIT 1;',
        [color, context.user.id.toString()]);
    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: DiscordColor.parseHexString('#6dbe33'),
          description: await getString(context.user, 'color_updated'))
    ]));
    return;
  } catch (e) {
    print('Error in color command: $e');
    await context.respond(
        MessageBuilder(embeds: [
          EmbedBuilder(
              color: DiscordColor.parseHexString('#c41111'),
              title: await getString(context.user, 'global_error'),
              description: await getString(context.user, 'color_error') +
                  codeBlock(e.toString(), 'sh'))
        ]),
        level: ResponseLevel.hint);
    return;
  } finally {
    await connection.close();
  }
});
