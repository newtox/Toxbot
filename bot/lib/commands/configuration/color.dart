import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:shiori/utils/database.dart';
import 'package:shiori/utils/reply.dart';

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
      Locale.ko: '임베드 색상을 설정합니다.',
      Locale.es419: 'Establecer el color de tus embeds.',
      Locale.ptBr: 'Definir a cor dos seus embeds.',
      Locale.tr: 'Embed\'lerinin rengini ayarla.',
      Locale.pl: 'Ustaw kolor swoich embedów.',
      Locale.it: 'Imposta il colore dei tuoi embed.',
      Locale.uk: 'Встановити колір своїх ембедів.',
      Locale.vi: 'Đặt màu cho embed của bạn.',
      Locale.th: 'ตั้งสีของ embed ของคุณ',
      Locale.zhTw: '設定你的嵌入訊息顏色。'
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
          Locale.ko: '16진수 색상 코드, 예: #7289da.',
          Locale.es419: 'El color en código hex, p. ej. #7289da.',
          Locale.ptBr: 'A cor como código hex, por exemplo #7289da.',
          Locale.tr: 'Hex kodu olarak renk, örneğin #7289da.',
          Locale.pl: 'Kolor jako kod hex, np. #7289da.',
          Locale.it: 'Il colore come codice hex, ad es. #7289da.',
          Locale.uk: 'Колір як hex-код, наприклад #7289da.',
          Locale.vi: 'Màu dạng mã hex, ví dụ #7289da.',
          Locale.th: 'สีในรูปแบบรหัส hex เช่น #7289da',
          Locale.zhTw: 'hex 色碼，例如 #7289da。'
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
    await replySuccess(context, 'color_updated', level: ResponseLevel.hint);
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
