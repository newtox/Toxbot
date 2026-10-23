import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/services/generators.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final _password = ChatCommand(
  'password',
  'Generate a secure password.',
  localizedDescriptions: {
    Locale.da: 'Generér en sikker adgangskode.',
    Locale.de: 'Ein sicheres Passwort erzeugen.',
    Locale.enUs: 'Generate a secure password.',
    Locale.esEs: 'Generar una contraseña segura.',
    Locale.fr: 'Générer un mot de passe sécurisé.',
    Locale.ru: 'Сгенерировать надёжный пароль.',
    Locale.hi: 'एक सुरक्षित पासवर्ड बनाएँ।',
    Locale.zhCn: '生成一个安全的密码。',
    Locale.ja: '安全なパスワードを生成する。',
    Locale.ko: '안전한 비밀번호를 생성합니다.',
  },
  (
    ChatContext context, [
    @Description('Length of the password (8-128, default 20).', {
      Locale.da: 'Adgangskodens længde (8-128, standard 20).',
      Locale.de: 'Länge des Passworts (8-128, Standard 20).',
      Locale.enUs: 'Length of the password (8-128, default 20).',
      Locale.esEs: 'Longitud de la contraseña (8-128, por defecto 20).',
      Locale.fr: 'Longueur du mot de passe (8-128, 20 par défaut).',
      Locale.ru: 'Длина пароля (8-128, по умолчанию 20).',
      Locale.hi: 'पासवर्ड की लंबाई (8-128, डिफ़ॉल्ट 20)।',
      Locale.zhCn: '密码长度（8-128，默认 20）。',
      Locale.ja: 'パスワードの長さ（8〜128、既定は 20）。',
      Locale.ko: '비밀번호 길이 (8-128, 기본값 20).',
    })
    @UseConverter(IntConverter(min: 8, max: 128))
    int? length,
    @Description('Include symbols such as ! # % (default yes).', {
      Locale.da: 'Medtag symboler som ! # % (standard ja).',
      Locale.de: 'Sonderzeichen wie ! # % verwenden (Standard ja).',
      Locale.enUs: 'Include symbols such as ! # % (default yes).',
      Locale.esEs: 'Incluir símbolos como ! # % (por defecto sí).',
      Locale.fr: 'Inclure des symboles comme ! # % (oui par défaut).',
      Locale.ru: 'Использовать символы вроде ! # % (по умолчанию да).',
      Locale.hi: '! # % जैसे चिह्न शामिल करें (डिफ़ॉल्ट हाँ)।',
      Locale.zhCn: '包含 ! # % 等符号（默认是）。',
      Locale.ja: '! # % などの記号を含める（既定はオン）。',
      Locale.ko: '! # % 같은 기호 포함 (기본값 예).',
    })
    bool? symbols,
  ]) async {
    await context.respond(
      MessageBuilder(embeds: [
        EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: await getString(context.user, 'generate_password_title'),
          description: codeBlock(
              generatePassword(length ?? 20, symbols: symbols ?? true), ''),
        ),
      ]),
      level: ResponseLevel.hint,
    );
  },
);

final _hex = ChatCommand(
  'hex',
  'Generate random bytes as hex, like openssl rand -hex.',
  localizedDescriptions: {
    Locale.da: 'Generér tilfældige bytes som hex, som openssl rand -hex.',
    Locale.de: 'Zufällige Bytes als Hex erzeugen, wie openssl rand -hex.',
    Locale.enUs: 'Generate random bytes as hex, like openssl rand -hex.',
    Locale.esEs: 'Generar bytes aleatorios en hex, como openssl rand -hex.',
    Locale.fr: 'Générer des octets aléatoires en hex, comme openssl rand -hex.',
    Locale.ru: 'Сгенерировать случайные байты в hex, как openssl rand -hex.',
    Locale.hi: 'openssl rand -hex की तरह रैंडम बाइट हेक्स में बनाएँ।',
    Locale.zhCn: '像 openssl rand -hex 一样生成十六进制随机字节。',
    Locale.ja: 'openssl rand -hex のようにランダムなバイトを16進数で生成する。',
    Locale.ko: 'openssl rand -hex처럼 무작위 바이트를 16진수로 생성합니다.',
  },
  (
    ChatContext context, [
    @Description('Number of bytes (1-64, default 32).', {
      Locale.da: 'Antal bytes (1-64, standard 32).',
      Locale.de: 'Anzahl der Bytes (1-64, Standard 32).',
      Locale.enUs: 'Number of bytes (1-64, default 32).',
      Locale.esEs: 'Número de bytes (1-64, por defecto 32).',
      Locale.fr: 'Nombre d\'octets (1-64, 32 par défaut).',
      Locale.ru: 'Количество байт (1-64, по умолчанию 32).',
      Locale.hi: 'बाइट की संख्या (1-64, डिफ़ॉल्ट 32)।',
      Locale.zhCn: '字节数（1-64，默认 32）。',
      Locale.ja: 'バイト数（1〜64、既定は 32）。',
      Locale.ko: '바이트 수 (1-64, 기본값 32).',
    })
    @UseConverter(IntConverter(min: 1, max: 64))
    int? bytes,
  ]) async {
    final count = bytes ?? 32;
    await context.respond(
      MessageBuilder(embeds: [
        EmbedBuilder(
          color: await getUserColorFromDatabase(context.user.id),
          title: await localized(
              context.user, 'generate_hex_title', {'&bytes': '$count'}),
          description: codeBlock(generateHex(count), ''),
        ),
      ]),
    );
  },
);

final _color = ChatCommand(
  'color',
  'Generate a random color.',
  localizedDescriptions: {
    Locale.da: 'Generér en tilfældig farve.',
    Locale.de: 'Eine zufällige Farbe erzeugen.',
    Locale.enUs: 'Generate a random color.',
    Locale.esEs: 'Generar un color aleatorio.',
    Locale.fr: 'Générer une couleur aléatoire.',
    Locale.ru: 'Сгенерировать случайный цвет.',
    Locale.hi: 'एक रैंडम रंग बनाएँ।',
    Locale.zhCn: '生成一个随机颜色。',
    Locale.ja: 'ランダムな色を生成する。',
    Locale.ko: '무작위 색상을 생성합니다.',
  },
  (ChatContext context) async {
    final color = randomColor();
    final hex = colorHex(color);
    final (r, g, b) = colorRgb(color);
    final (h, s, l) = colorHsl(color);

    await context.respond(
      MessageBuilder(
        attachments: [
          AttachmentBuilder(data: colorPng(color), fileName: 'color.png')
        ],
        embeds: [
          EmbedBuilder(
            color: DiscordColor(color),
            title: await getString(context.user, 'generate_color_title'),
            description: await localized(
                context.user, 'generate_color_tip', {'&hex': hex}),
            thumbnail:
                EmbedThumbnailBuilder(url: Uri.parse('attachment://color.png')),
            fields: [
              EmbedFieldBuilder(name: 'Hex', value: '`$hex`', isInline: true),
              EmbedFieldBuilder(
                  name: 'RGB', value: '`$r, $g, $b`', isInline: true),
              EmbedFieldBuilder(
                  name: 'HSL', value: '`$h°, $s%, $l%`', isInline: true),
            ],
          ),
        ],
      ),
    );
  },
);

final generate = ChatGroup(
  'generate',
  'Generate passwords, random hex values and colors.',
  localizedDescriptions: {
    Locale.da: 'Generér adgangskoder, tilfældige hex-værdier og farver.',
    Locale.de: 'Passwörter, zufällige Hex-Werte und Farben erzeugen.',
    Locale.enUs: 'Generate passwords, random hex values and colors.',
    Locale.esEs: 'Generar contraseñas, valores hex aleatorios y colores.',
    Locale.fr:
        'Générer des mots de passe, des valeurs hex aléatoires et des couleurs.',
    Locale.ru: 'Генерировать пароли, случайные hex-значения и цвета.',
    Locale.hi: 'पासवर्ड, रैंडम हेक्स मान और रंग बनाएँ।',
    Locale.zhCn: '生成密码、随机十六进制值和颜色。',
    Locale.ja: 'パスワード、ランダムな16進数、色を生成する。',
    Locale.ko: '비밀번호, 무작위 16진수 값과 색상을 생성합니다.',
  },
  children: [_password, _hex, _color],
);
