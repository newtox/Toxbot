import 'dart:async';
import 'dart:io';
import 'dart:math';

import 'package:dotenv/dotenv.dart';
import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:path/path.dart' as path;

var env = DotEnv(includePlatformEnvironment: true)..load();

Future<String> getClientStatus(Member member, NyxxGateway client) async {
  return '';
}

Future<DiscordColor> getHighestRoleColor(Member member) async {
  DiscordColor color = DiscordColor.parseHexString('#7289da');

  if (member.roles.isNotEmpty) {
    var highestRole = await member.roles.first.get();

    color = highestRole.color;
  }

  return color;
}

Future<DiscordColor?> getUserColorFromDatabase(Snowflake userId) async {
  final connection = await getMySqlConnection();

  try {
    final results = await connection.query(
      'SELECT `color` FROM `users` WHERE `id` = ? LIMIT 1;',
      [userId.toString()],
    );

    if (results.isNotEmpty) {
      return DiscordColor.parseHexString(results.first['color']);
    } else {
      return null;
    }
  } catch (e) {
    print('An error occurred: $e');
    return null;
  } finally {
    await connection.close();
  }
}

Future<String> getRoles(dynamic type) async {
  if (!(type is Member || type is Guild)) {
    throw ArgumentError('The type must be either Member or Guild.');
  }

  List<PartialRole> partialRoles;

  if (type is Member) {
    partialRoles = type.roles.toList();
  } else if (type is Guild) {
    partialRoles = type.roleList;
  } else {
    throw StateError('Unexpected type encountered.');
  }

  List<Role> roles =
      await Future.wait(partialRoles.map((partial) => partial.get()));

  roles.sort((a, b) => b.position.compareTo(a.position));

  return roles.take(40).map((role) => '<@&${role.id}>').join(', ');
}

String generateCommands(String category) {
  String commandsListed = '';

  final directory =
      Directory('${Directory.current.path}/lib/commands/$category');
  final group = directory.listSync();

  for (final file in group) {
    if (file is File) {
      final commandName = path.basenameWithoutExtension(file.path);
      commandsListed += ' `$commandName`,';
    }
  }

  if (commandsListed.isNotEmpty) {
    commandsListed = commandsListed.substring(0, commandsListed.length - 1);
  }

  return commandsListed;
}

void randomStatus(ReadyEvent event) {
  final List<Map<String, dynamic>> options = [
    {
      'type': ActivityType.listening,
      'name': 'nyxx',
      'status': CurrentUserStatus.online,
      'state': 'Wrapper around Discord API for Dart'
    }
  ];

  try {
    Timer.periodic(const Duration(minutes: 2), (Timer timer) {
      final random = Random();
      final statusIndex = random.nextInt(options.length);

      final selectedStatus = options[statusIndex];

      event.gateway.updatePresence(PresenceBuilder(
        since: DateTime(2024, 9, 6, 17, 35),
        activities: [
          ActivityBuilder(
              name: selectedStatus['name'],
              type: selectedStatus['type'] as ActivityType,
              state: selectedStatus['state'])
        ],
        status: selectedStatus['status'] as CurrentUserStatus,
        isAfk: false,
      ));
    });
  } catch (e) {
    print('Error in randomStatus: $e');
  }
}

List<String> splitMessage(String message, {int maxLength = 2000}) {
  List<String> chunks = [];
  while (message.length > maxLength) {
    int index = message.substring(0, maxLength).lastIndexOf('\n');
    chunks.add(message.substring(0, index));
    message = message.substring(index + 1);
  }
  chunks.add(message);
  return chunks;
}
