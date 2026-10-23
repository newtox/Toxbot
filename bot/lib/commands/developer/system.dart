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
  'Clear bot cache.',
  localizedDescriptions: {
    Locale.da: 'Ryd bot-cache.',
    Locale.de: 'Bot-Cache leeren.',
    Locale.enUs: 'Clear bot cache.',
    Locale.esEs: 'Borrar caché del bot.',
    Locale.fr: 'Vider le cache du bot.',
    Locale.ru: 'Очистить кэш бота.',
    Locale.hi: 'बॉट कैश साफ़ करें।',
    Locale.zhCn: '清除机器人缓存。',
    Locale.ja: 'ボットのキャッシュをクリアする。',
    Locale.ko: '봇 캐시 지우기.',
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
  'Read out files.',
  localizedDescriptions: {
    Locale.da: 'Læs filer.',
    Locale.de: 'Dateien auslesen.',
    Locale.enUs: 'Read out files.',
    Locale.esEs: 'Leer archivos.',
    Locale.fr: 'Lire des fichiers.',
    Locale.ru: 'Чтение файлов.',
    Locale.hi: 'फ़ाइलें पढ़ें।',
    Locale.zhCn: '读取文件。',
    Locale.ja: 'ファイルを読み取る。',
    Locale.ko: '파일 읽기.',
  },
  (
    ChatContext context,
    @Description('The path to the file.', {
      Locale.da: 'Stien til filen.',
      Locale.de: 'Der Pfad zur Datei.',
      Locale.enUs: 'The path to the file.',
      Locale.esEs: 'La ruta al archivo.',
      Locale.fr: 'Le chemin vers le fichier.',
      Locale.ru: 'Путь к файлу.',
      Locale.hi: 'फ़ाइल का पथ।',
      Locale.zhCn: '文件路径。',
      Locale.ja: 'ファイルへのパス。',
      Locale.ko: '파일 경로.',
    })
    String path,
    @Description('Whether to send the file content as a hastebin link.', {
      Locale.da: 'Om filindholdet skal sendes som en hastebin link.',
      Locale.de: 'Ob der Dateiinhalt als Hastebin-Link gesendet werden soll.',
      Locale.enUs: 'Whether to send the file content as a hastebin link.',
      Locale.esEs:
          'Si se debe enviar el contenido del archivo como un enlace de hastebin.',
      Locale.fr: 'Envoyer le contenu du fichier comme un lien hastebin.',
      Locale.ru: 'Отправить содержимое файла как ссылку на hastebin.',
      Locale.hi: 'क्या फ़ाइल की सामग्री को hastebin लिंक के रूप में भेजना है।',
      Locale.zhCn: '是否将文件内容作为 hastebin 链接发送。',
      Locale.ja: 'ファイル内容を hastebin リンクとして送信するかどうか。',
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
    Locale.de: 'Alle Rollen und deren Berechtigungen auflisten.',
    Locale.enUs: 'List all roles and their permissions.',
    Locale.esEs: 'Listar todos los roles y sus permisos.',
    Locale.fr: 'Lister tous les rôles et leurs permissions.',
    Locale.ru: 'Список всех ролей и их разрешений.',
    Locale.hi: 'सभी भूमिकाएं और उनकी अनुमतियां सूचीबद्ध करें।',
    Locale.zhCn: '列出所有角色及其权限。',
    Locale.ja: '全ての役割とその権限を一覧表示。',
    Locale.ko: '모든 역할과 권한 나열.',
  },
  checks: [GuildCheck.all()],
  (
    ChatContext context,
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output skal sendes som en hastebin link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link gesendet werden soll.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se debe enviar la salida como un enlace de hastebin.',
      Locale.fr: 'Si la sortie doit être envoyée comme un lien hastebin.',
      Locale.ru: 'Отправить вывод как ссылку на hastebin.',
      Locale.hi: 'आउटपुट को hastebin लिंक के रूप में भेजना है या नहीं।',
      Locale.zhCn: '是否将输出作为 hastebin 链接发送。',
      Locale.ja: '出力を hastebin リンクとして送信するかどうか。',
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
  'Display RustDesk IDs from the SQLite database.',
  localizedDescriptions: {
    Locale.da: 'Vis RustDesk-ID\'er fra SQLite-databasen.',
    Locale.de: 'RustDesk-IDs aus der SQLite-Datenbank anzeigen.',
    Locale.enUs: 'Display RustDesk IDs from the SQLite database.',
    Locale.esEs: 'Mostrar IDs de RustDesk de la base de datos SQLite.',
    Locale.fr:
        'Afficher les identifiants RustDesk de la base de données SQLite.',
    Locale.ru: 'Отображение идентификаторов RustDesk из базы данных SQLite.',
    Locale.hi: 'SQLite डेटाबेस से RustDesk आईडी प्रदर्शित करें।',
    Locale.zhCn: '显示 SQLite 数据库中的 RustDesk ID。',
    Locale.ja: 'SQLiteデータベースからRustDesk IDを表示します。',
    Locale.ko: 'SQLite 데이터베이스에서 RustDesk ID를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output skal sendes som en hastebin link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link gesendet werden soll.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se debe enviar la salida como un enlace de hastebin.',
      Locale.fr: 'Si la sortie doit être envoyée comme un lien hastebin.',
      Locale.ru: 'Отправить вывод как ссылку на hastebin.',
      Locale.hi: 'आउटपुट को hastebin लिंक के रूप में भेजना है या नहीं।',
      Locale.zhCn: '是否将输出作为 hastebin 链接发送。',
      Locale.ja: '出力を hastebin リンクとして送信するかどうか。',
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
    Locale.ja: '読み取り専用の SQL クエリを実行する。',
    Locale.ko: '읽기 전용 SQL 쿼리를 실행합니다.',
  },
  (
    ChatContext context,
    @Description('The query, e.g. SELECT id, xp FROM users LIMIT 10.', {
      Locale.da: 'Forespørgslen, f.eks. SELECT id, xp FROM users LIMIT 10.',
      Locale.de: 'Die Abfrage, z. B. SELECT id, xp FROM users LIMIT 10.',
      Locale.enUs: 'The query, e.g. SELECT id, xp FROM users LIMIT 10.',
      Locale.esEs: 'La consulta, p. ej. SELECT id, xp FROM users LIMIT 10.',
      Locale.fr: 'La requête, par ex. SELECT id, xp FROM users LIMIT 10.',
      Locale.ru: 'Запрос, например SELECT id, xp FROM users LIMIT 10.',
      Locale.hi: 'क्वेरी, जैसे SELECT id, xp FROM users LIMIT 10।',
      Locale.zhCn: '查询，例如 SELECT id, xp FROM users LIMIT 10。',
      Locale.ja: 'クエリ（例: SELECT id, xp FROM users LIMIT 10）。',
      Locale.ko: '쿼리, 예: SELECT id, xp FROM users LIMIT 10.',
    })
    String query, [
    @Description('Whether to send the output as a hastebin link.', {
      Locale.da: 'Om output skal sendes som en hastebin link.',
      Locale.de: 'Ob die Ausgabe als Hastebin-Link gesendet werden soll.',
      Locale.enUs: 'Whether to send the output as a hastebin link.',
      Locale.esEs: 'Si se debe enviar la salida como un enlace de hastebin.',
      Locale.fr: 'Si la sortie doit être envoyée comme un lien hastebin.',
      Locale.ru: 'Отправить вывод как ссылку на hastebin.',
      Locale.hi: 'आउटपुट को hastebin लिंक के रूप में भेजना है या नहीं।',
      Locale.zhCn: '是否将输出作为 hastebin 链接发送。',
      Locale.ja: '出力を hastebin リンクとして送信するかどうか。',
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
    Locale.ja: 'Dart コードを実行する。',
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
  'Commands for the developer.',
  localizedDescriptions: {
    Locale.da: 'Kommandoer til udvikleren.',
    Locale.de: 'Befehle für den Entwickler.',
    Locale.enUs: 'Commands for the developer.',
    Locale.esEs: 'Comandos para el desarrollador.',
    Locale.fr: 'Commandes pour le développeur.',
    Locale.ru: 'Команды для разработчика.',
    Locale.hi: 'डेवलपर के लिए कमांड।',
    Locale.zhCn: '开发者的命令。',
    Locale.ja: '開発者向けコマンド。',
    Locale.ko: '개발자용 명령어.',
  },
  checks: [UserCheck.id(ownerId)],
  children: [_clearCache, _skid, _listRoles, _rustdeskIds, _sql, _eval],
);
