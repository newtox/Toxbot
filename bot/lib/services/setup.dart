import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';

const setupAreas = ['tickets', 'moderation', 'protection', 'verification'];

const _teamPermissions = [
  Permissions.administrator,
  Permissions.manageGuild,
  Permissions.kickMembers,
  Permissions.banMembers,
  Permissions.manageMessages,
  Permissions.moderateMembers,
];

const _timeout = Duration(minutes: 3);

Future<String> _userLanguage(Snowflake userId) async {
  try {
    final language = await withConnection((connection) async {
      final rows = await connection.query(
          'SELECT `language` FROM `users` WHERE `id` = ? LIMIT 1;',
          [userId.toString()]);
      return rows.isEmpty ? null : textOrNull(rows.first['language']);
    });
    return language ?? 'en_us';
  } catch (_) {
    return 'en_us';
  }
}

bool isManagedRole(Role role) =>
    role.tags?.botId != null ||
    role.tags?.integrationId != null ||
    role.tags?.isPremiumSubscriber == true;

Future<List<Role>> teamRoles(Guild guild) async {
  final roles = await guild.roles.list();
  return roles
      .where((role) =>
          role.id != guild.id &&
          !isManagedRole(role) &&
          _teamPermissions.any(role.permissions.has))
      .toList()
    ..sort((a, b) => b.position.compareTo(a.position));
}

Future<List<({bool ok, String text})>?> runAutoSetup({
  required Guild guild,
  required List<String> areas,
  required List<Snowflake> teamRoleIds,
  required User user,
  String? userName,
}) async {
  final base = dashboardUrl;
  if (base == null) return null;

  final body = jsonEncode({
    'time': DateTime.now().millisecondsSinceEpoch ~/ 1000,
    'guild': guild.id.toString(),
    'guild_name': guild.name,
    'areas': areas,
    'team_roles': [for (final id in teamRoleIds.take(10)) id.toString()],
    'user_name': userName ?? user.globalName ?? user.username,
    'locale': await _userLanguage(user.id),
  });
  final signature =
      Hmac(sha256, utf8.encode(botToken)).convert(utf8.encode(body)).toString();

  try {
    final response = await http
        .post(
          Uri.parse('$base/internal/setup'),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Toxbot-Signature': signature,
          },
          body: body,
        )
        .timeout(_timeout);
    if (response.statusCode != 200) {
      print('Auto setup: dashboard answered ${response.statusCode}');
      return null;
    }
    final steps = (jsonDecode(response.body)['steps'] as List)
        .cast<Map<String, dynamic>>();
    return [
      for (final step in steps)
        (ok: step['ok'] == true, text: step['text'].toString()),
    ];
  } catch (e) {
    print('Auto setup failed in ${guild.id}: $e');
    return null;
  }
}
