import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';

const _cacheLifetime = Duration(minutes: 1);

const blacklistCheckName = 'blacklist';

Set<String> _users = {};

Set<String> _guilds = {};

DateTime? _loadedAt;

final blacklistCheck = Check(
  (CommandContext context) async => !await isUserBlacklisted(context.user.id),
  name: blacklistCheckName,
);

Future<void> _load() async {
  if (_loadedAt != null &&
      DateTime.now().difference(_loadedAt!) < _cacheLifetime) {
    return;
  }
  try {
    final rows = await withConnection((connection) =>
        connection.query('SELECT `user`, `guild` FROM `blacklist`;'));
    _users = {
      for (final row in rows)
        if (textOrNull(row['user']) case final String user) user,
    };
    _guilds = {
      for (final row in rows)
        if (textOrNull(row['guild']) case final String guild) guild,
    };
    _loadedAt = DateTime.now();
  } catch (e) {
    print('Loading the blacklist failed: $e');
  }
}

Future<bool> isUserBlacklisted(Snowflake userId) async {
  if (userId == ownerId) return false;
  await _load();
  return _users.contains(userId.toString());
}

Future<bool> isGuildBlacklisted(Snowflake guildId) async {
  await _load();
  return _guilds.contains(guildId.toString());
}

Future<bool> leaveIfBlacklisted(NyxxGateway client, Snowflake guildId) async {
  if (!await isGuildBlacklisted(guildId)) return false;
  try {
    await client.users.leaveGuild(guildId);
    print('Left blacklisted server $guildId');
  } catch (e) {
    print('Leaving blacklisted server $guildId failed: $e');
  }
  return true;
}
