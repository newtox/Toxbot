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
    Locale.fr: 'Générer un mot de passe sûr.',
    Locale.ru: 'Сгенерировать надёжный пароль.',
    Locale.hi: 'एक सुरक्षित पासवर्ड बनाएँ।',
    Locale.zhCn: '生成一个安全的密码。',
    Locale.ja: '安全なパスワードを生成します。',
    Locale.ko: '안전한 비밀번호를 생성합니다.',
    Locale.es419: 'Generar una contraseña segura.',
    Locale.ptBr: 'Gerar uma senha segura.',
    Locale.tr: 'Güvenli bir şifre oluştur.',
    Locale.pl: 'Wygeneruj bezpieczne hasło.',
    Locale.it: 'Genera una password sicura.',
    Locale.uk: 'Згенерувати надійний пароль.',
    Locale.vi: 'Tạo một mật khẩu an toàn.',
    Locale.th: 'สร้างรหัสผ่านที่ปลอดภัย',
    Locale.zhTw: '產生安全的密碼。',
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
      Locale.ja: 'パスワードの長さ（8-128、既定 20）。',
      Locale.ko: '비밀번호 길이 (8-128, 기본값 20).',
      Locale.es419: 'Longitud de la contraseña (8-128, por defecto 20).',
      Locale.ptBr: 'Tamanho da senha (8-128, padrão 20).',
      Locale.tr: 'Şifre uzunluğu (8-128, varsayılan 20).',
      Locale.pl: 'Długość hasła (8-128, domyślnie 20).',
      Locale.it: 'Lunghezza della password (8-128, predefinita 20).',
      Locale.uk: 'Довжина пароля (8-128, типово 20).',
      Locale.vi: 'Độ dài mật khẩu (8-128, mặc định 20).',
      Locale.th: 'ความยาวรหัสผ่าน (8-128 ค่าเริ่มต้น 20)',
      Locale.zhTw: '密碼長度（8-128，預設 20）。',
    })
    @UseConverter(IntConverter(min: 8, max: 128))
    int? length,
    @Description('Whether to use symbols such as ! # % (default yes).', {
      Locale.da: 'Om der skal bruges tegn som ! # % (standard ja).',
      Locale.de: 'Ob Sonderzeichen wie ! # % vorkommen (Standard ja).',
      Locale.enUs: 'Whether to use symbols such as ! # % (default yes).',
      Locale.esEs: 'Si se usan símbolos como ! # % (por defecto sí).',
      Locale.fr: 'Utiliser des symboles comme ! # % (oui par défaut).',
      Locale.ru: 'Использовать символы вроде ! # % (по умолчанию да).',
      Locale.hi: '! # % जैसे चिह्न शामिल करें या नहीं (डिफ़ॉल्ट हाँ)।',
      Locale.zhCn: '是否使用 ! # % 等符号（默认是）。',
      Locale.ja: '! # % などの記号を使うか（既定ははい）。',
      Locale.ko: '! # % 같은 기호 사용 여부 (기본값 예).',
      Locale.es419: 'Si se usan símbolos como ! # % (por defecto sí).',
      Locale.ptBr: 'Se deve usar símbolos como ! # % (padrão sim).',
      Locale.tr: '! # % gibi semboller kullanılsın mı (varsayılan evet).',
      Locale.pl: 'Czy używać symboli takich jak ! # % (domyślnie tak).',
      Locale.it: 'Se usare simboli come ! # % (predefinito sì).',
      Locale.uk: 'Чи використовувати символи на кшталт ! # % (типово так).',
      Locale.vi: 'Có dùng ký hiệu như ! # % không (mặc định có).',
      Locale.th: 'ใช้สัญลักษณ์อย่าง ! # % หรือไม่ (ค่าเริ่มต้นคือใช้)',
      Locale.zhTw: '是否使用 ! # % 等符號（預設是）。',
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
    Locale.zhCn: '以十六进制生成随机字节，类似 openssl rand -hex。',
    Locale.ja: 'openssl rand -hex のようにランダムなバイトを 16 進数で生成します。',
    Locale.ko: 'openssl rand -hex처럼 무작위 바이트를 16진수로 생성합니다.',
    Locale.es419: 'Generar bytes aleatorios en hex, como openssl rand -hex.',
    Locale.ptBr: 'Gerar bytes aleatórios em hex, como openssl rand -hex.',
    Locale.tr: 'openssl rand -hex gibi rastgele baytları hex olarak oluştur.',
    Locale.pl: 'Wygeneruj losowe bajty jako hex, jak openssl rand -hex.',
    Locale.it: 'Genera byte casuali in hex, come openssl rand -hex.',
    Locale.uk: 'Згенерувати випадкові байти в hex, як openssl rand -hex.',
    Locale.vi: 'Tạo byte ngẫu nhiên dạng hex, như openssl rand -hex.',
    Locale.th: 'สร้างไบต์สุ่มเป็น hex เหมือน openssl rand -hex',
    Locale.zhTw: '產生 hex 格式的隨機位元組，像 openssl rand -hex。',
  },
  (
    ChatContext context, [
    @Description('Number of bytes (1-64, default 32).', {
      Locale.da: 'Antal bytes (1-64, standard 32).',
      Locale.de: 'Anzahl der Bytes (1-64, Standard 32).',
      Locale.enUs: 'Number of bytes (1-64, default 32).',
      Locale.esEs: 'Número de bytes (1-64, por defecto 32).',
      Locale.fr: 'Nombre d\'octets (1-64, 32 par défaut).',
      Locale.ru: 'Количество байтов (1-64, по умолчанию 32).',
      Locale.hi: 'बाइट की संख्या (1-64, डिफ़ॉल्ट 32)।',
      Locale.zhCn: '字节数（1-64，默认 32）。',
      Locale.ja: 'バイト数（1-64、既定 32）。',
      Locale.ko: '바이트 수 (1-64, 기본값 32).',
      Locale.es419: 'Número de bytes (1-64, por defecto 32).',
      Locale.ptBr: 'Número de bytes (1-64, padrão 32).',
      Locale.tr: 'Bayt sayısı (1-64, varsayılan 32).',
      Locale.pl: 'Liczba bajtów (1-64, domyślnie 32).',
      Locale.it: 'Numero di byte (1-64, predefinito 32).',
      Locale.uk: 'Кількість байтів (1-64, типово 32).',
      Locale.vi: 'Số byte (1-64, mặc định 32).',
      Locale.th: 'จำนวนไบต์ (1-64 ค่าเริ่มต้น 32)',
      Locale.zhTw: '位元組數（1-64，預設 32）。',
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
    Locale.ja: 'ランダムな色を生成します。',
    Locale.ko: '무작위 색상을 생성합니다.',
    Locale.es419: 'Generar un color aleatorio.',
    Locale.ptBr: 'Gerar uma cor aleatória.',
    Locale.tr: 'Rastgele bir renk oluştur.',
    Locale.pl: 'Wygeneruj losowy kolor.',
    Locale.it: 'Genera un colore casuale.',
    Locale.uk: 'Згенерувати випадковий колір.',
    Locale.vi: 'Tạo một màu ngẫu nhiên.',
    Locale.th: 'สร้างสีแบบสุ่ม',
    Locale.zhTw: '產生隨機顏色。',
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
    Locale.hi: 'पासवर्ड, रैंडम हेक्स वैल्यू और रंग बनाएँ।',
    Locale.zhCn: '生成密码、随机十六进制值和颜色。',
    Locale.ja: 'パスワード、ランダムな 16 進値、色を生成します。',
    Locale.ko: '비밀번호, 무작위 16진수 값, 색상을 생성합니다.',
    Locale.es419: 'Generar contraseñas, valores hex aleatorios y colores.',
    Locale.ptBr: 'Gerar senhas, valores hex aleatórios e cores.',
    Locale.tr: 'Şifreler, rastgele hex değerleri ve renkler oluştur.',
    Locale.pl: 'Generuj hasła, losowe wartości hex i kolory.',
    Locale.it: 'Genera password, valori hex casuali e colori.',
    Locale.uk: 'Генерувати паролі, випадкові hex-значення й кольори.',
    Locale.vi: 'Tạo mật khẩu, giá trị hex ngẫu nhiên và màu sắc.',
    Locale.th: 'สร้างรหัสผ่าน ค่า hex สุ่ม และสี',
    Locale.zhTw: '產生密碼、隨機 hex 值和顏色。',
  },
  children: [_password, _hex, _color],
);
