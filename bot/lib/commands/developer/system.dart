import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:sqlite3/sqlite3.dart';
import 'package:toxbot/services/developer.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/reply.dart';

final _clearCache = ChatCommand(
  'clear-cache',
  'Clear the bot cache.',
  localizedDescriptions: {
    Locale.da: 'Ryd botcachen.',
    Locale.de: 'Den Bot-Cache leeren.',
    Locale.enUs: 'Clear the bot cache.',
    Locale.esEs: 'Vaciar la caché del bot.',
    Locale.fr: 'Vider le cache du bot.',
    Locale.ru: 'Очистить кэш бота.',
    Locale.hi: 'बॉट कैश साफ़ करें।',
    Locale.zhCn: '清空机器人缓存。',
    Locale.ja: 'ボットのキャッシュを消去します。',
    Locale.ko: '봇 캐시를 비웁니다.',
  },
  (ChatContext context) async {
    context.client.channels.cache.clear();
    context.client.users.cache.clear();

    await context.respond(
      MessageBuilder(content: 'Cache cleared successfully!'),
      level: ResponseLevel.hint,
    );
  },
);

final _skid = ChatCommand(
  'skid',
  'Read a file.',
  localizedDescriptions: {
    Locale.da: 'Læs en fil.',
    Locale.de: 'Eine Datei auslesen.',
    Locale.enUs: 'Read a file.',
    Locale.esEs: 'Leer un archivo.',
    Locale.fr: 'Lire un fichier.',
    Locale.ru: 'Прочитать файл.',
    Locale.hi: 'कोई फ़ाइल पढ़ें।',
    Locale.zhCn: '读取一个文件。',
    Locale.ja: 'ファイルを読み込みます。',
    Locale.ko: '파일을 읽습니다.',
  },
  (
    ChatContext context,
    @Description('The path to the file.', {
      Locale.da: 'Stien til filen.',
      Locale.de: 'Der Pfad zur Datei.',
      Locale.enUs: 'The path to the file.',
      Locale.esEs: 'La ruta del archivo.',
      Locale.fr: 'Le chemin du fichier.',
      Locale.ru: 'Путь к файлу.',
      Locale.hi: 'फ़ाइल का पाथ।',
      Locale.zhCn: '文件路径。',
      Locale.ja: 'ファイルのパス。',
      Locale.ko: '파일 경로.',
    })
    String path,
    @Description('Whether to send the file content as a hastebin link.', {
      Locale.da: 'Om filindholdet sendes som hastebin-link.',
      Locale.de: 'Ob der Dateiinhalt als Hastebin-Link geschickt wird.',
      Locale.enUs: 'Whether to send the file content as a hastebin link.',
      Locale.esEs:
          'Si se envía el contenido del archivo como enlace de hastebin.',
      Locale.fr: 'Envoyer le contenu du fichier comme lien hastebin.',
      Locale.ru: 'Отправить содержимое файла ссылкой на hastebin.',
      Locale.hi: 'फ़ाइल की सामग्री hastebin लिंक के रूप में भेजें या नहीं।',
      Locale.zhCn: '是否以 hastebin 链接发送文件内容。',
      Locale.ja: 'ファイルの内容を hastebin のリンクで送るか。',
      Locale.ko: '파일 내용을 hastebin 링크로 보낼지 여부.',
    })
    bool hastebin,
  ) async {
    try {
      await respondWithOutput(context, await File(path).readAsString(),
          language: 'dart', hastebin: hastebin);
    } catch (e) {
      await replyException(context, e);
    }
  },
);

final _listRoles = ChatCommand(
  'listroles',
  'List all roles and their permissions.',
  localizedDescriptions: {
    Locale.da: 'Vis alle roller og deres tilladelser.',
    Locale.de: 'Alle Rollen und ihre Berechtigungen auflisten.',
    Locale.enUs: 'List all roles and their permissions.',
    Locale.esEs: 'Listar todos los roles y sus permisos.',
    Locale.fr: 'Lister tous les rôles et leurs permissions.',
    Locale.ru: 'Показать все роли и их права.',
    Locale.hi: 'सभी रोल और उनकी अनुमतियाँ दिखाएँ।',
    Locale.zhCn: '列出所有身份组及其权限。',
    Locale.ja: 'すべてのロールと権限を一覧表示します。',
    Locale.ko: '모든 역할과 권한을 나열합니다.',
  },
  checks: [GuildCheck.all()],
  (
    ChatContext context,
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output sendes som hastebin-link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link geschickt wird.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se envía la salida como enlace de hastebin.',
      Locale.fr: 'Envoyer la sortie comme lien hastebin.',
      Locale.ru: 'Отправить вывод ссылкой на hastebin.',
      Locale.hi: 'आउटपुट hastebin लिंक के रूप में भेजें या नहीं।',
      Locale.zhCn: '是否以 hastebin 链接发送输出。',
      Locale.ja: '出力を hastebin のリンクで送るか。',
      Locale.ko: '출력을 hastebin 링크로 보낼지 여부.',
    })
    bool hastebin,
  ) async {
    try {
      final content = StringBuffer();
      for (final role in context.guild!.roles.cache.values) {
        content.writeln('📑 ${role.name}');
        content.writeln('Permissions:');
        for (final permission in role.permissions.toList()) {
          content.writeln('✅ $permission');
        }
        content.writeln();
      }

      await respondWithOutput(context, content.toString(),
          language: 'dart', hastebin: hastebin);
    } catch (e) {
      await replyException(context, e);
    }
  },
);

final _rustdeskIds = ChatCommand(
  'rustdesk-ids',
  'Show the RustDesk IDs from the SQLite database.',
  localizedDescriptions: {
    Locale.da: 'Vis RustDesk-ID\'erne fra SQLite-databasen.',
    Locale.de: 'Die RustDesk-IDs aus der SQLite-Datenbank anzeigen.',
    Locale.enUs: 'Show the RustDesk IDs from the SQLite database.',
    Locale.esEs: 'Mostrar los ID de RustDesk de la base de datos SQLite.',
    Locale.fr: 'Afficher les ID RustDesk de la base de données SQLite.',
    Locale.ru: 'Показать ID RustDesk из базы SQLite.',
    Locale.hi: 'SQLite डेटाबेस से RustDesk ID दिखाएँ।',
    Locale.zhCn: '显示 SQLite 数据库中的 RustDesk ID。',
    Locale.ja: 'SQLite データベースの RustDesk ID を表示します。',
    Locale.ko: 'SQLite 데이터베이스의 RustDesk ID를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output sendes som hastebin-link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link geschickt wird.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se envía la salida como enlace de hastebin.',
      Locale.fr: 'Envoyer la sortie comme lien hastebin.',
      Locale.ru: 'Отправить вывод ссылкой на hastebin.',
      Locale.hi: 'आउटपुट hastebin लिंक के रूप में भेजें या नहीं।',
      Locale.zhCn: '是否以 hastebin 链接发送输出。',
      Locale.ja: '出力を hastebin のリンクで送るか。',
      Locale.ko: '출력을 hastebin 링크로 보낼지 여부.',
    })
    bool hastebin,
  ) async {
    try {
      const dbPath = '/data/rustdesk/db_v2.sqlite3';
      final dbFile = File(dbPath);

      if (!dbFile.existsSync()) {
        await replyErrorText(
            context, 'SQLite file not found at the predefined path: $dbPath');
        return;
      }

      final db = sqlite3.open(dbFile.path);

      try {
        final result = db.select(
            'SELECT hex(guid) as guid, id, hex(uuid) as uuid, hex(pk) as pk, created_at, hex(user) as user, status, note, info FROM peer;');

        final content = StringBuffer('# RustDesk IDs\n\n');

        if (result.isEmpty) {
          content.write('No RustDesk IDs found in the database.');
        } else {
          final columns = result.first.keys;
          content.writeln(columns.join(' | '));
          content.writeln(columns.map((_) => '---').join(' | '));
          for (final row in result) {
            content.writeln(row.values
                .map((value) => value?.toString() ?? 'NULL')
                .join(' | '));
          }
        }

        await respondWithOutput(context, content.toString(),
            language: 'markdown', hastebin: hastebin);
      } finally {
        db.close();
      }
    } catch (e) {
      await replyException(context, e);
    }
  },
);

final _sql = ChatCommand(
  'sql',
  'Run a read-only SQL query.',
  localizedDescriptions: {
    Locale.da: 'Kør en skrivebeskyttet SQL-forespørgsel.',
    Locale.de: 'Eine SQL-Abfrage nur lesend ausführen.',
    Locale.enUs: 'Run a read-only SQL query.',
    Locale.esEs: 'Ejecutar una consulta SQL de solo lectura.',
    Locale.fr: 'Exécuter une requête SQL en lecture seule.',
    Locale.ru: 'Выполнить SQL-запрос только для чтения.',
    Locale.hi: 'केवल पढ़ने वाली SQL क्वेरी चलाएँ।',
    Locale.zhCn: '运行只读 SQL 查询。',
    Locale.ja: '読み取り専用の SQL クエリを実行します。',
    Locale.ko: '읽기 전용 SQL 쿼리를 실행합니다.',
  },
  (
    ChatContext context,
    @Description('The query, e.g. SELECT id, xp FROM users LIMIT 10.', {
      Locale.da: 'Forespørgslen, f.eks. SELECT id, xp FROM users LIMIT 10.',
      Locale.de: 'Die Abfrage, z. B. SELECT id, xp FROM users LIMIT 10.',
      Locale.enUs: 'The query, e.g. SELECT id, xp FROM users LIMIT 10.',
      Locale.esEs: 'La consulta, p. ej. SELECT id, xp FROM users LIMIT 10.',
      Locale.fr: 'La requête, ex. SELECT id, xp FROM users LIMIT 10.',
      Locale.ru: 'Запрос, например SELECT id, xp FROM users LIMIT 10.',
      Locale.hi: 'क्वेरी, जैसे SELECT id, xp FROM users LIMIT 10।',
      Locale.zhCn: '查询语句，例如 SELECT id, xp FROM users LIMIT 10。',
      Locale.ja: 'クエリ（例: SELECT id, xp FROM users LIMIT 10）。',
      Locale.ko: '쿼리, 예: SELECT id, xp FROM users LIMIT 10.',
    })
    String query, [
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output sendes som hastebin-link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link geschickt wird.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se envía la salida como enlace de hastebin.',
      Locale.fr: 'Envoyer la sortie comme lien hastebin.',
      Locale.ru: 'Отправить вывод ссылкой на hastebin.',
      Locale.hi: 'आउटपुट hastebin लिंक के रूप में भेजें या नहीं।',
      Locale.zhCn: '是否以 hastebin 链接发送输出。',
      Locale.ja: '出力を hastebin のリンクで送るか。',
      Locale.ko: '출력을 hastebin 링크로 보낼지 여부.',
    })
    bool? hastebin,
  ]) async {
    try {
      await respondWithOutput(context, await runReadOnlyQuery(query),
          hastebin: hastebin ?? false);
    } catch (e) {
      await replyErrorText(context, codeBlock(e.toString(), 'sh'));
    }
  },
);

final _eval = ChatCommand(
  'eval',
  'Run Dart code.',
  localizedDescriptions: {
    Locale.da: 'Kør Dart-kode.',
    Locale.de: 'Dart-Code ausführen.',
    Locale.enUs: 'Run Dart code.',
    Locale.esEs: 'Ejecutar código Dart.',
    Locale.fr: 'Exécuter du code Dart.',
    Locale.ru: 'Выполнить код на Dart.',
    Locale.hi: 'Dart कोड चलाएँ।',
    Locale.zhCn: '运行 Dart 代码。',
    Locale.ja: 'Dart コードを実行します。',
    Locale.ko: 'Dart 코드를 실행합니다.',
  },
  (ChatContext context) async {
    final modal = await (context as InteractionChatContext).getModal(
      title: 'Eval',
      components: [
        TextInputBuilder(
          customId: 'code',
          style: TextInputStyle.paragraph,
          label: 'Dart',
          maxLength: 4000,
          isRequired: true,
        ),
      ],
    );
    await modal.acknowledge(level: ResponseLevel.hint);

    final result = await evaluate(modal['code'] ?? '');
    await respondWithOutput(modal, result.format(), language: 'dart');
  },
);

final system = ChatGroup(
  'system',
  'Tools for the developer.',
  localizedDescriptions: {
    Locale.da: 'Værktøjer til udvikleren.',
    Locale.de: 'Werkzeuge für den Entwickler.',
    Locale.enUs: 'Tools for the developer.',
    Locale.esEs: 'Herramientas para el desarrollador.',
    Locale.fr: 'Outils pour le développeur.',
    Locale.ru: 'Инструменты для разработчика.',
    Locale.hi: 'डेवलपर के लिए टूल।',
    Locale.zhCn: '开发者工具。',
    Locale.ja: '開発者向けのツールです。',
    Locale.ko: '개발자용 도구입니다.',
  },
  checks: [UserCheck.id(ownerId)],
  children: [_clearCache, _skid, _listRoles, _rustdeskIds, _sql, _eval],
);
