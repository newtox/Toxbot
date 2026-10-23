import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/models/protection_settings.dart';
import 'package:toxbot/services/moderation.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

export 'package:toxbot/models/protection_settings.dart';

const _scamListUrl =
    'https://raw.githubusercontent.com/nikolaischunk/discord-phishing-links/main/domain-list.json';

const _alwaysAllowed = {
  'discord.com',
  'discordapp.com',
  'discordapp.net',
  'discord.media',
  'tenor.com',
};

const _settingsLifetime = Duration(minutes: 1);

const _inviteLifetime = Duration(hours: 1);

const _noticeLifetime = Duration(seconds: 8);

const _punishCooldown = Duration(seconds: 30);

const _linkTimeout = Duration(minutes: 10);

const _alarmColor = '#ed4245';

const _calmColor = '#57f287';

final _urlPattern = RegExp(r'https?://[^\s<>()\[\]]+', caseSensitive: false);

final _invitePattern = RegExp(
    r'(?:discord(?:app)?\.com/invite|discord\.gg)/([a-z0-9-]+)',
    caseSensitive: false);

final _barePattern =
    RegExp(r'\b(?:[a-z0-9-]+\.)+[a-z]{2,}\b', caseSensitive: false);

final Map<Snowflake, (ProtectionSettings?, DateTime)> _settingsCache = {};

final Map<String, (Snowflake?, DateTime)> _inviteCache = {};

final Map<Snowflake, List<(DateTime, Snowflake)>> _recentJoins = {};

final Map<Snowflake, DateTime> _raidUntil = {};

final Map<String, DateTime> _lastPunished = {};

Set<String> _scamDomains = {};

bool _raidTimerBusy = false;

Future<ProtectionSettings?> _settings(Snowflake guildId) async {
  final cached = _settingsCache[guildId];
  if (cached != null &&
      DateTime.now().difference(cached.$2) < _settingsLifetime) {
    return cached.$1;
  }

  final settings = await withConnection((connection) async {
    final rows = await connection.query(
        'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;', [guildId.toString()]);
    return rows.isEmpty ? null : ProtectionSettings.fromRow(rows.first);
  });
  _settingsCache[guildId] = (settings, DateTime.now());
  return settings;
}

String _normalizeHost(String host) {
  var value = host.toLowerCase().trim();
  if (value.endsWith('.')) value = value.substring(0, value.length - 1);
  if (value.startsWith('www.')) value = value.substring(4);
  return value;
}

bool _matches(String host, Set<String> domains) {
  if (domains.isEmpty) return false;
  final parts = host.split('.');
  for (var i = 0; i < parts.length - 1; i++) {
    if (domains.contains(parts.sublist(i).join('.'))) return true;
  }
  return false;
}

Future<Snowflake?> _inviteGuild(NyxxGateway client, String code) async {
  final key = code.toLowerCase();
  final cached = _inviteCache[key];
  if (cached != null &&
      DateTime.now().difference(cached.$2) < _inviteLifetime) {
    return cached.$1;
  }

  Snowflake? guildId;
  try {
    guildId = (await client.invites.fetch(code)).guild?.id;
  } catch (_) {
    guildId = null;
  }
  _inviteCache[key] = (guildId, DateTime.now());
  return guildId;
}

Future<(String, String)?> _violation(NyxxGateway client, Snowflake guildId,
    String content, ProtectionSettings settings) async {
  final inviteCodes = _invitePattern
      .allMatches(content)
      .map((match) => match.group(1)!)
      .toSet();
  final linkHosts = <String>{};
  for (final match in _urlPattern.allMatches(content)) {
    final url = match.group(0)!;
    if (_invitePattern.hasMatch(url)) continue;
    final host = Uri.tryParse(url)?.host;
    if (host != null && host.isNotEmpty) linkHosts.add(_normalizeHost(host));
  }
  final allHosts = {
    ...linkHosts,
    ..._barePattern
        .allMatches(content)
        .map((match) => _normalizeHost(match.group(0)!)),
  };

  if (settings.linkScam) {
    for (final host in allHosts) {
      if (_matches(host, _scamDomains)) return ('scam', host);
    }
  }

  for (final code in inviteCodes) {
    if (!settings.linkInvites && !settings.allowlistEnabled) break;
    if (await _inviteGuild(client, code) == guildId) continue;
    if (settings.linkInvites) return ('invite', 'discord.gg/$code');
    if (!_matches('discord.gg', settings.allowlist)) {
      return ('not_allowed', 'discord.gg');
    }
  }

  for (final host in allHosts) {
    if (_matches(host, settings.blocklist)) return ('blocked', host);
  }

  if (settings.allowlistEnabled) {
    final allowed = {...settings.allowlist, ..._alwaysAllowed};
    for (final host in linkHosts) {
      if (!_matches(host, allowed)) return ('not_allowed', host);
    }
  }

  return null;
}

Future<bool> _isExempt(Guild guild, Snowflake userId) async {
  if (userId == guild.ownerId) return true;

  final member = await guild.members.get(userId);
  final roles = await guild.roles.list();
  var value = 0;
  for (final role in roles) {
    if (role.id == guild.id || member.roleIds.contains(role.id)) {
      value |= role.permissions.value;
    }
  }

  final permissions = Flags<Permissions>(value);
  return permissions.has(Permissions.administrator) ||
      permissions.has(Permissions.manageMessages);
}

Future<void> _refreshScamList() async {
  try {
    final response = await http.get(Uri.parse(_scamListUrl));
    if (response.statusCode != 200) return;
    final domains = (jsonDecode(response.body)['domains'] as List)
        .map((domain) => _normalizeHost(domain.toString()))
        .where((domain) => domain.isNotEmpty)
        .toSet();
    if (domains.isNotEmpty) _scamDomains = domains;
  } catch (e) {
    print('Failed to load the scam domain list: $e');
  }
}

Future<void> _postModlog(
    NyxxGateway client, ProtectionSettings settings, EmbedBuilder embed) async {
  final channelId = settings.modlogChannel;
  if (channelId == null) return;

  try {
    final channel = await client.channels[channelId].get();
    if (channel is! TextChannel) return;
    await channel.sendMessage(
        MessageBuilder(allowedMentions: noMentions, embeds: [embed]));
  } catch (e) {
    print('Failed to post protection alarm: $e');
  }
}

Future<void> _kick(Guild guild, Snowflake userId) async {
  try {
    await guild.members.delete(userId, auditLogReason: 'Toxbot anti-raid');
  } catch (e) {
    print('Anti-raid kick failed for $userId in ${guild.id}: $e');
  }
}

Future<void> _startRaid(NyxxGateway client, Guild guild,
    ProtectionSettings settings, List<Snowflake> joined) async {
  final until =
      DateTime.now().toUtc().add(Duration(minutes: settings.raidMinutes));
  _raidUntil[guild.id] = until;

  int? previousLevel;
  if (settings.raidLevel &&
      guild.verificationLevel.value < VerificationLevel.veryHigh.value) {
    try {
      await client.guilds.update(guild.id,
          GuildUpdateBuilder(verificationLevel: VerificationLevel.veryHigh),
          auditLogReason: 'Toxbot anti-raid');
      previousLevel = guild.verificationLevel.value;
    } catch (e) {
      print('Raising the verification level failed in ${guild.id}: $e');
    }
  }

  await withConnection((connection) => connection.query(
      'UPDATE `guilds` SET `raid_until` = ?, `raid_previous_level` = ? WHERE `id` = ?;',
      [until, previousLevel, guild.id.toString()]));
  _settingsCache.remove(guild.id);

  if (settings.raidKick) {
    for (final userId in joined) {
      await _kick(guild, userId);
    }
  }

  final bot = await client.users.get(client.user.id);
  final accounts = joined.map((id) => '<@$id> `$id`').join('\n');
  final measures = [
    if (previousLevel != null) await localized(bot, 'protect_raid_level'),
    if (settings.raidKick) await localized(bot, 'protect_raid_kick'),
  ];

  await _postModlog(
    client,
    settings,
    EmbedBuilder(
      color: DiscordColor.parseHexString(_alarmColor),
      title: await localized(bot, 'protect_raid_title'),
      description: await localized(bot, 'protect_raid_text', {
        '&count': '${joined.length}',
        '&seconds': '${settings.raidSeconds}',
        '&until': '<t:${until.millisecondsSinceEpoch ~/ 1000}:t>',
      }),
      fields: [
        EmbedFieldBuilder(
            name: await localized(bot, 'protect_raid_accounts'),
            value: accounts.length > 1024
                ? '${accounts.substring(0, 1020)}…'
                : accounts,
            isInline: false),
        EmbedFieldBuilder(
            name: await localized(bot, 'protect_raid_measures'),
            value: measures.isEmpty
                ? await localized(bot, 'protect_raid_none')
                : measures.map((line) => '• $line').join('\n'),
            isInline: false),
      ],
      timestamp: DateTime.now().toUtc(),
    ),
  );
}

Future<void> _endRaids(NyxxGateway client) async {
  final rows = await withConnection((connection) => connection.query(
      'SELECT * FROM `guilds` WHERE `raid_until` IS NOT NULL AND `raid_until` <= UTC_TIMESTAMP();'));

  for (final row in rows) {
    final guildId = Snowflake.parse(row['id'].toString());
    final previousLevel = row['raid_previous_level'] as int?;
    _raidUntil.remove(guildId);
    _recentJoins.remove(guildId);

    if (previousLevel != null) {
      try {
        await client.guilds.update(
            guildId,
            GuildUpdateBuilder(
                verificationLevel: VerificationLevel(previousLevel)),
            auditLogReason: 'Toxbot anti-raid ended');
      } catch (e) {
        print('Restoring the verification level failed in $guildId: $e');
      }
    }

    await withConnection((connection) => connection.query(
        'UPDATE `guilds` SET `raid_until` = NULL, `raid_previous_level` = NULL WHERE `id` = ?;',
        [guildId.toString()]));
    _settingsCache.remove(guildId);

    final bot = await client.users.get(client.user.id);
    final text = await localized(bot, 'protect_raid_ended');
    final restored = previousLevel == null
        ? ''
        : ' ${await localized(bot, 'protect_raid_restored')}';
    await _postModlog(
      client,
      ProtectionSettings.fromRow(row),
      EmbedBuilder(
        color: DiscordColor.parseHexString(_calmColor),
        description: '$text$restored',
        timestamp: DateTime.now().toUtc(),
      ),
    );
  }
}

Future<void> _startVerification(NyxxGateway client, Guild guild, Member member,
    User user, ProtectionSettings settings) async {
  try {
    await member.addRole(settings.verifyRole!,
        auditLogReason: 'Toxbot verification pending');
  } catch (e) {
    print('Adding the verification role failed in ${guild.id}: $e');
    return;
  }

  await withConnection((connection) => connection.query(
      'INSERT INTO `verifications` (`guild`, `user_id`, `created_at`, `verified_at`) VALUES (?, ?, UTC_TIMESTAMP(), NULL) '
      'ON DUPLICATE KEY UPDATE `created_at` = UTC_TIMESTAMP(), `verified_at` = NULL;',
      [guild.id.toString(), user.id.toString()]));

  final baseUrl = publicDashboardUrl;
  if (baseUrl == null) return;

  try {
    final dm = await client.users.createDm(user.id);
    await dm.sendMessage(MessageBuilder(
      allowedMentions: noMentions,
      content: await localized(user, 'protect_verify_dm', {
        '&server': guild.name,
        '&link': '$baseUrl/verify/${guild.id}',
      }),
    ));
  } catch (_) {}
}

Future<void> _handleMessage(
    NyxxGateway client, Snowflake? guildId, Message message) async {
  final author = message.author;
  if (guildId == null || author is! User || author.isBot) return;
  if (message.content.isEmpty) return;

  final settings = await _settings(guildId);
  if (settings == null || !settings.filtersLinks) return;

  final violation =
      await _violation(client, guildId, message.content, settings);
  if (violation == null) return;
  final (reason, host) = violation;

  final guild = await client.guilds.get(guildId);
  if (await _isExempt(guild, author.id)) return;

  try {
    await message.manager
        .delete(message.id, auditLogReason: 'Toxbot link filter: $host');
  } catch (e) {
    print('Deleting a filtered message failed in $guildId: $e');
    return;
  }

  try {
    final notice = await message.channel.sendMessage(MessageBuilder(
      allowedMentions: AllowedMentions(users: [author.id]),
      content: await localized(author, 'protect_link_removed', {
        '&user': '<@${author.id}>',
        '&reason': await localized(author, 'protect_reason_$reason'),
      }),
    ));
    Timer(_noticeLifetime, () => notice.delete().catchError((_) {}));
  } catch (_) {}

  if (settings.linkAction == 'delete') return;

  final key = '$guildId:${author.id}';
  final last = _lastPunished[key];
  if (last != null && DateTime.now().difference(last) < _punishCooldown) {
    return;
  }
  _lastPunished[key] = DateTime.now();

  final bot = await client.users.get(client.user.id);
  try {
    await performAction(
      client,
      guild: guild,
      type: settings.linkAction == 'timeout' ? 'timeout' : 'warn',
      targetId: author.id,
      moderator: bot,
      reason: await localized(bot, 'protect_link_case', {'&host': host}),
      duration: settings.linkAction == 'timeout' ? _linkTimeout : null,
      source: 'automod',
    );
  } catch (e) {
    print('Link filter punishment failed in $guildId: $e');
  }
}

Future<({bool removed, bool verifying})> protectJoin(
    NyxxGateway client, Guild guild, Member member) async {
  const keep = (removed: false, verifying: false);

  final settings = await _settings(guild.id);
  if (settings == null) return keep;

  final user = member.user ?? await client.users.get(member.id);
  if (user.isBot) return keep;

  if (settings.raidEnabled) {
    final now = DateTime.now().toUtc();
    final until = _raidUntil[guild.id] ?? settings.raidUntil;

    if (until != null && until.isAfter(now)) {
      if (settings.raidKick) {
        await _kick(guild, member.id);
        return (removed: true, verifying: false);
      }
    } else {
      final window = Duration(seconds: settings.raidSeconds);
      final joins = (_recentJoins[guild.id] ?? [])
        ..add((now, member.id))
        ..removeWhere((join) => now.difference(join.$1) > window);
      _recentJoins[guild.id] = joins;

      if (joins.length >= settings.raidJoins) {
        final joined = joins.map((join) => join.$2).toList();
        joins.clear();
        await _startRaid(client, guild, settings, joined);
        if (settings.raidKick) return (removed: true, verifying: false);
      }
    }
  }

  if (settings.minAccountAge > 0) {
    final age = DateTime.now().toUtc().difference(member.id.timestamp);
    if (age < Duration(days: settings.minAccountAge)) {
      final bot = await client.users.get(client.user.id);
      try {
        await performAction(
          client,
          guild: guild,
          type: 'kick',
          targetId: member.id,
          moderator: bot,
          reason: await localized(bot, 'protect_young_case',
              {'&days': '${settings.minAccountAge}'}),
          source: 'automod',
        );
        return (removed: true, verifying: false);
      } catch (e) {
        print('Kicking a young account failed in ${guild.id}: $e');
      }
    }
  }

  if (settings.verifying) {
    await _startVerification(client, guild, member, user, settings);
    return (removed: false, verifying: true);
  }

  return keep;
}

void setupProtectionHandlers(NyxxGateway client) {
  client.onMessageCreate.listen((event) async {
    try {
      await _handleMessage(client, event.guildId, event.message);
    } catch (e) {
      print('Error in link filter: $e');
    }
  });

  client.onMessageUpdate.listen((event) async {
    try {
      await _handleMessage(client, event.guildId, event.message);
    } catch (e) {
      print('Error in link filter: $e');
    }
  });

  _refreshScamList();
  Timer.periodic(const Duration(hours: 6), (_) => _refreshScamList());

  Timer.periodic(const Duration(seconds: 30), (_) async {
    if (_raidTimerBusy) return;
    _raidTimerBusy = true;
    try {
      await _endRaids(client);
    } catch (e) {
      print('Error while ending raids: $e');
    } finally {
      _raidTimerBusy = false;
    }
  });
}
