import 'dart:convert';
import 'dart:io';

import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/utils/config.dart';
import 'package:yaml/yaml.dart';

final Map<String, bool> cached = {};

Future<String> _translation(String language, String key) async {
  final yamlFile = File('${Directory.current.path}/lib/utils/languages.yaml');
  final yamlContent = loadYaml(await yamlFile.readAsString());

  return yamlContent[language]?[key] ?? yamlContent['en_us'][key] ?? '';
}

Future<Guild?> checkForDatabase(Guild guild) async {
  final guildId = guild.id.toString();
  if (cached.containsKey(guildId)) {
    return null;
  }

  final connection = await getMySqlConnection();

  try {
    final results = await connection
        .query('SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;', [guildId]);

    if (results.isNotEmpty) {
      cached[guildId] = true;
      return null;
    }

    await connection.query(
        'INSERT INTO `guilds` (`id`, `welcome_channel`, `bye_channel`, `welcome_msg`, `bye_msg`, `autorole`, `notify`, `stream`) VALUES (?, NULL, NULL, NULL, NULL, NULL, NULL, NULL);',
        [guildId]);

    cached[guildId] = true;
  } catch (e) {
    print('Error in checkForDatabase: $e');
  } finally {
    await connection.close();
  }
  return null;
}

Future<String> getString(User user, String dbString) async {
  final connection = await getMySqlConnection();

  try {
    final userId = user.id.toString();

    final results = await connection
        .query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1;', [userId]);

    String language;

    if (results.isEmpty) {
      if (user.isBot) return '';

      language = 'en_us';
      await connection.query(
          'INSERT INTO `users` (`id`, `language`, `color`) VALUES (?, ?, ?);',
          [userId, language, '#7289da']);
    } else {
      final blobData = results.first['language'];
      List<int> languageBytes =
          blobData is Blob ? blobData.toBytes() : blobData;
      language = utf8.decode(languageBytes);
    }

    return await _translation(language, dbString);
  } catch (e) {
    print('Error in getString: $e');
  } finally {
    await connection.close();
  }

  return '';
}

Future<String> getGuildString(String guildId, String key) async {
  try {
    final language = await withConnection((connection) async {
      final rows = await connection.query(
          'SELECT `language` FROM `guilds` WHERE `id` = ? LIMIT 1;', [guildId]);
      return rows.isEmpty ? null : textOrNull(rows.first['language']);
    });
    return await _translation(
        language == null || language.isEmpty ? 'en_us' : language, key);
  } catch (e) {
    print('Error in getGuildString: $e');
    return _translation('en_us', key);
  }
}

Future<void> clearOldGuilds(NyxxGateway client) async {
  final connection = await getMySqlConnection();

  try {
    final results = await connection.query('SELECT `id` FROM `guilds`;', []);

    for (final row in results) {
      final String guildIdAsString = row['id'].toString();
      int? guildIdAsInt = int.tryParse(guildIdAsString);

      if (guildIdAsInt == null) {
        print('Failed to parse guild ID: $guildIdAsString');
        continue;
      }

      try {
        await client.guilds.fetch(Snowflake(guildIdAsInt));
      } on HttpResponseError catch (e) {
        if (e.statusCode == 403 || e.statusCode == 404) {
          await connection
              .query('DELETE FROM `guilds` WHERE `id` = ?;', [guildIdAsString]);
          cached.remove(guildIdAsString);
          try {
            await connection.query(
                'DELETE FROM `ticket_messages` WHERE `ticket_id` IN (SELECT `id` FROM `tickets` WHERE `guild` = ?);',
                [guildIdAsString]);
          } catch (_) {}
          for (final table in [
            'stream_subscriptions',
            'mod_cases',
            'mod_actions',
            'warn_rules',
            'verifications',
            'tickets',
            'ticket_types',
            'unban_appeals',
          ]) {
            try {
              await connection.query(
                  'DELETE FROM `$table` WHERE `guild` = ?;', [guildIdAsString]);
            } catch (_) {}
          }
        }
      }
    }
  } catch (e) {
    print('Error in clearOldGuilds: $e');
  } finally {
    await connection.close();
  }
}

Future<MySqlConnection> getMySqlConnection() async {
  return MySqlConnection.connect(ConnectionSettings(
      host: env['db_host']!,
      user: env['db_user'],
      password: env['db_password'],
      db: env['db_name'],
      timeout: const Duration(seconds: 30)));
}

Future<T> withConnection<T>(
  Future<T> Function(MySqlConnection connection) action,
) async {
  final connection = await getMySqlConnection();
  try {
    return await action(connection);
  } finally {
    await connection.close();
  }
}

String textValue(Object? value) => switch (value) {
      Blob blob => utf8.decode(blob.toBytes()),
      null => '',
      _ => value.toString(),
    };

String? textOrNull(Object? value) => value == null ? null : textValue(value);
