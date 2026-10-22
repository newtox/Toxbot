import 'package:nyxx/nyxx.dart';
import 'package:toxbot/database.dart';

class _LiveRoleSettings {
  final Snowflake role;
  final Snowflake? required;

  const _LiveRoleSettings(this.role, this.required);
}

final Map<Snowflake, (_LiveRoleSettings?, DateTime)> _settingsCache = {};

Future<_LiveRoleSettings?> _settings(Snowflake guildId) async {
  final cached = _settingsCache[guildId];
  if (cached != null &&
      DateTime.now().difference(cached.$2) < const Duration(minutes: 1)) {
    return cached.$1;
  }

  final connection = await getMySqlConnection();
  try {
    final rows = await connection.query(
        'SELECT `live_role`, `live_role_required` FROM `guilds` WHERE `id` = ? LIMIT 1;',
        [guildId.toString()]);
    final role = rows.isEmpty ? null : rows.first['live_role'] as String?;
    final required =
        rows.isEmpty ? null : rows.first['live_role_required'] as String?;
    final settings = role == null
        ? null
        : _LiveRoleSettings(Snowflake.parse(role),
            required == null ? null : Snowflake.parse(required));
    _settingsCache[guildId] = (settings, DateTime.now());
    return settings;
  } finally {
    await connection.close();
  }
}

void setupLiveRoleHandler(NyxxGateway client) {
  client.onPresenceUpdate.listen((event) async {
    final guildId = event.guildId;
    final userId = event.user?.id;
    if (guildId == null || userId == null || event.activities == null) return;

    try {
      final settings = await _settings(guildId);
      if (settings == null) return;

      final streaming = event.activities!
          .any((activity) => activity.type == ActivityType.streaming);

      final member = await client.guilds[guildId].members.get(userId);
      if (member.user?.isBot ?? false) return;

      final hasRole = member.roleIds.contains(settings.role);
      final allowed = settings.required == null ||
          member.roleIds.contains(settings.required);

      if (streaming && allowed && !hasRole) {
        await member.addRole(settings.role,
            auditLogReason: 'Started streaming');
      } else if ((!streaming || !allowed) && hasRole) {
        await member.removeRole(settings.role,
            auditLogReason: 'Stopped streaming');
      }
    } catch (e) {
      print('Error in live role handler: $e');
    }
  });
}
