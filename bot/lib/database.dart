import 'dart:convert';
import 'dart:io';

import 'package:dotenv/dotenv.dart';
import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:yaml/yaml.dart';

var env = DotEnv(includePlatformEnvironment: true)..load();

final Map<String, bool> cached = {};

Future<Guild?> checkForDatabase(Guild guild) async {
  // ignore: collection_methods_unrelated_type
  if (cached.containsKey(guild.id)) {
    return null;
  }

  final connection = await getMySqlConnection();

  try {
    var guildId = guild.id.toString();

    var results = await connection
        .query('SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;', [guildId]);

    if (results.isNotEmpty) {
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

    final yamlFile = File('${Directory.current.path}/lib/utils/languages.yaml');
    final yamlContent = loadYaml(await yamlFile.readAsString());

    return yamlContent[language][dbString] ?? '';
  } catch (e) {
    print('Error in getString: $e');
  } finally {
    await connection.close();
  }

  return '';
}

Future<void> clearOldGuilds(NyxxGateway client) async {
  final connection = await getMySqlConnection();

  try {
    final results = await connection.query('SELECT * FROM `guilds`;', []);

    for (final row in results) {
      final String guildIdAsString = row['id'].toString();
      int? guildIdAsInt = int.tryParse(guildIdAsString);

      if (guildIdAsInt == null) {
        print('Failed to parse guild ID: $guildIdAsString');
        continue;
      }

      try {
        await client.guilds.fetch(Snowflake(guildIdAsInt));
      } catch (e) {
        await connection
            .query('DELETE FROM `guilds` WHERE `id` = ?;', [guildIdAsString]);
      } finally {
        await connection.close();
      }
    }
  } catch (e) {
    print('Error in clearOldGuilds: $e');
  } finally {
    await connection.close();
  }
}

Future<void> clearOldUsers(NyxxGateway client) async {
  final connection = await getMySqlConnection();

  try {
    final results = await connection.query('SELECT * FROM `users`;', []);

    for (final row in results) {
      final String userIdAsString = row['id'].toString();
      int? userIdAsInt = int.tryParse(userIdAsString);

      if (userIdAsInt == null) {
        print('Failed to parse user ID: $userIdAsString');
        continue;
      }

      try {
        await client.users.fetch(Snowflake(userIdAsInt));
      } catch (e) {
        await connection
            .query('DELETE FROM `users` WHERE `id` = ?;', [userIdAsString]);
      } finally {
        await connection.close();
      }
    }
  } catch (e) {
    print('Error in clearOldUsers: $e');
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
