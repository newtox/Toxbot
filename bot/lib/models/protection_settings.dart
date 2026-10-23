import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/utils/database.dart';

class ProtectionSettings {
  final bool verifyEnabled;
  final Snowflake? verifyRole;
  final Snowflake? autorole;
  final bool linkInvites;
  final bool linkScam;
  final bool allowlistEnabled;
  final Set<String> allowlist;
  final Set<String> blocklist;
  final String linkAction;
  final Set<Snowflake> exemptRoles;
  final bool raidEnabled;
  final int raidJoins;
  final int raidSeconds;
  final int raidMinutes;
  final bool raidKick;
  final bool raidLevel;
  final DateTime? raidUntil;
  final int minAccountAge;
  final Snowflake? modlogChannel;

  ProtectionSettings.fromRow(ResultRow row)
      : verifyEnabled = _flag(row['verify_enabled']),
        verifyRole = _snowflake(row['verify_role']),
        autorole = _snowflake(row['autorole']),
        linkInvites = _flag(row['link_invites']),
        linkScam = _flag(row['link_scam']),
        allowlistEnabled = _flag(row['link_allowlist_enabled']),
        allowlist = _domains(row['link_allowlist']),
        blocklist = _domains(row['link_blocklist']),
        linkAction = textOrNull(row['link_action']) ?? 'delete',
        exemptRoles = _snowflakes(row['link_exempt_roles']),
        raidEnabled = _flag(row['raid_enabled']),
        raidJoins = row['raid_joins'] as int? ?? 10,
        raidSeconds = row['raid_seconds'] as int? ?? 10,
        raidMinutes = row['raid_minutes'] as int? ?? 10,
        raidKick = _flag(row['raid_kick']),
        raidLevel = _flag(row['raid_level']),
        raidUntil = (row['raid_until'] as DateTime?)?.toUtc(),
        minAccountAge = row['min_account_age'] as int? ?? 0,
        modlogChannel = _snowflake(row['modlog_channel']);

  bool get verifying => verifyEnabled && verifyRole != null;

  bool get filtersLinks =>
      linkInvites || linkScam || allowlistEnabled || blocklist.isNotEmpty;

  static Snowflake? _snowflake(Object? value) {
    final text = textOrNull(value);
    return text == null ? null : Snowflake.parse(text);
  }

  static Set<Snowflake> _snowflakes(Object? value) => (textOrNull(value) ?? '')
      .split(',')
      .map((id) => int.tryParse(id.trim()))
      .whereType<int>()
      .map(Snowflake.new)
      .toSet();

  static Set<String> _domains(Object? value) => (textOrNull(value) ?? '')
      .split('\n')
      .map((domain) => domain.trim().toLowerCase())
      .where((domain) => domain.isNotEmpty)
      .toSet();

  static bool _flag(Object? value) => (value as int? ?? 0) == 1;
}
