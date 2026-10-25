import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

final color = ChatCommand('color', 'Set the color of your embeds.',
    localizedDescriptions: {
      Locale.da: 'Indstil farven på dine embeds.',
      Locale.de: 'Die Farbe deiner Embeds festlegen.',
      Locale.enUs: 'Set the color of your embeds.',
      Locale.esEs: 'Establecer el color de tus embeds.',
      Locale.fr: 'Définir la couleur de tes embeds.',
      Locale.ru: 'Задать цвет своих эмбедов.',
      Locale.hi: 'अपने एम्बेड का रंग सेट करें।',
      Locale.zhCn: '设置你的嵌入消息颜色。',
      Locale.ja: '埋め込みの色を設定します。',
      Locale.ko: '임베드 색상을 설정합니다.'
    }, (ChatContext context,
        @Description('The color as hex code, e.g. #7289da.', {
          Locale.da: 'Farven som hex-kode, f.eks. #7289da.',
          Locale.de: 'Die Farbe als Hex-Code, z. B. #7289da.',
          Locale.enUs: 'The color as hex code, e.g. #7289da.',
          Locale.esEs: 'El color en código hex, p. ej. #7289da.',
          Locale.fr: 'La couleur en code hex, ex. #7289da.',
          Locale.ru: 'Цвет в hex-коде, например #7289da.',
          Locale.hi: 'हेक्स कोड में रंग, जैसे #7289da।',
          Locale.zhCn: '十六进制颜色代码，例如 #7289da。',
          Locale.ja: '16 進数のカラーコード（例: #7289da）。',
          Locale.ko: '16진수 색상 코드, 예: #7289da.'
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
