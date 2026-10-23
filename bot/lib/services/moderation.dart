import 'dart:async';

import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/models/mod_case.dart';
import 'package:toxbot/models/moderation_exception.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

const modTypes = ['warn', 'timeout', 'untimeout', 'kick', 'ban', 'unban'];

const maxTimeout = Duration(days: 28);

const _colors = {
  'warn': '#fee75c',
  'timeout': '#f57c00',
  'untimeout': '#57f287',
  'kick': '#f47b67',
  'ban': '#ed4245',
  'unban': '#57f287',
};

final _requiredPermission = {
  'warn': Permissions.moderateMembers,
  'timeout': Permissions.moderateMembers,
  'untimeout': Permissions.moderateMembers,
  'kick': Permissions.kickMembers,
  'ban': Permissions.banMembers,
  'unban': Permissions.banMembers,
};

bool _queueBusy = false;

bool _expiryBusy = false;

String _name(User user) =>
    user.globalName != null && user.globalName != user.username
        ? '${user.globalName} (${user.username})'
        : user.username;

Future<Map<String, dynamic>> _guildSettings(Snowflake guildId) =>
    withConnection((connection) async {
      final rows = await connection.query(
          'SELECT `modlog_channel`, `mod_dm` FROM `guilds` WHERE `id` = ? LIMIT 1;',
          [guildId.toString()]);
      if (rows.isEmpty) return {'modlog_channel': null, 'mod_dm': true};
      return {
        'modlog_channel': rows.first['modlog_channel'] as String?,
        'mod_dm': (rows.first['mod_dm'] as int? ?? 1) == 1,
      };
    });

int _highestPosition(
    Iterable<Snowflake> roleIds, Map<Snowflake, int> positions) {
  var highest = 0;
  for (final id in roleIds) {
    final position = positions[id] ?? 0;
    if (position > highest) highest = position;
  }
  return highest;
}

Future<bool> _sendDm(NyxxGateway client, User target, Guild guild, String type,
    String? reason, Duration? duration) async {
  try {
    final dm = await client.users.createDm(target.id);
    final fields = <EmbedFieldBuilder>[
      EmbedFieldBuilder(
          name: await localized(target, 'mod_field_reason'),
          value: reason ?? await localized(target, 'mod_no_reason'),
          isInline: false),
      if (duration != null)
        EmbedFieldBuilder(
            name: await localized(target, 'mod_field_duration'),
            value: formatDuration(duration),
            isInline: true),
    ];
    await dm.sendMessage(MessageBuilder(allowedMentions: noMentions, embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(_colors[type]!),
        description:
            await localized(target, 'mod_dm_$type', {'&server': guild.name}),
        fields: fields,
        timestamp: DateTime.now().toUtc(),
      )
    ]));
    return true;
  } catch (_) {
    return false;
  }
}

Future<void> _escalate(NyxxGateway client, Guild guild, User target) async {
  final rule = await withConnection((connection) async {
    final count = await connection.query(
        "SELECT COUNT(*) AS `warns` FROM `mod_cases` WHERE `guild` = ? AND `user_id` = ? AND `type` = 'warn' AND `active` = 1;",
        [guild.id.toString(), target.id.toString()]);
    final warns = (count.first['warns'] as num).toInt();
    final rules = await connection.query(
        'SELECT `action`, `duration` FROM `warn_rules` WHERE `guild` = ? AND `warns` = ? LIMIT 1;',
        [guild.id.toString(), warns]);
    if (rules.isEmpty) return null;
    return (
      warns,
      rules.first['action'] as String,
      rules.first['duration'] as int?
    );
  });

  if (rule == null) return;
  final (warns, action, seconds) = rule;
  final bot = await client.users.get(client.user.id);

  try {
    await performAction(
      client,
      guild: guild,
      type: action,
      targetId: target.id,
      moderator: bot,
      reason: await localized(bot, 'mod_auto_reason', {'&count': '$warns'}),
      duration: seconds == null ? null : Duration(seconds: seconds),
      source: 'automod',
    );
  } catch (e) {
    print('Warn escalation failed in ${guild.id}: $e');
  }
}

Future<void> _handleAuditEntry(
    NyxxGateway client, GuildAuditLogCreateEvent event) async {
  final entry = event.entry;
  if (entry.userId == null || entry.userId == client.user.id) return;
  if (entry.targetId == null) return;

  String? type;
  Duration? duration;

  if (entry.actionType == AuditLogEvent.memberBanAdd) {
    type = 'ban';
  } else if (entry.actionType == AuditLogEvent.memberBanRemove) {
    type = 'unban';
  } else if (entry.actionType == AuditLogEvent.memberKick) {
    type = 'kick';
  } else if (entry.actionType == AuditLogEvent.memberUpdate) {
    final change = entry.changes
        ?.where((c) => c.key == 'communication_disabled_until')
        .firstOrNull;
    if (change == null) return;
    if (change.newValue == null) {
      type = 'untimeout';
    } else {
      final until = DateTime.tryParse(change.newValue.toString());
      if (until == null) return;
      type = 'timeout';
      duration = until.toUtc().difference(DateTime.now().toUtc());
      if (duration.isNegative) return;
      duration = Duration(seconds: ((duration.inSeconds + 30) ~/ 60) * 60);
    }
  }

  if (type == null) return;

  final guild = await client.guilds.get(event.guildId);
  final moderator = await client.users.get(entry.userId!);
  final target = await client.users.get(entry.targetId!);

  final modCase = await createCase(
    guildId: guild.id,
    type: type,
    user: target,
    moderator: moderator,
    reason: entry.reason,
    duration: duration,
    source: 'discord',
  );
  await postModlog(client, guild, modCase, moderator);
}

Future<void> _processQueue(NyxxGateway client) async {
  final rows = await withConnection((connection) async {
    final pending = await connection.query(
        "SELECT * FROM `mod_actions` WHERE `status` = 'pending' ORDER BY `id` LIMIT 10;");
    for (final row in pending) {
      await connection.query(
          "UPDATE `mod_actions` SET `status` = 'running', `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;",
          [row['id']]);
    }
    return pending.toList();
  });

  for (final row in rows) {
    String status = 'done';
    String? error;
    int? caseId;

    try {
      final guild =
          await client.guilds.get(Snowflake.parse(row['guild'] as String));
      final moderatorId = Snowflake.parse(row['moderator_id'] as String);
      final targetId = Snowflake.parse(row['user_id'] as String);
      final type = row['type'] as String;

      await checkPermission(guild, moderatorId, type);
      if (type != 'unban') {
        await checkHierarchy(client, guild, moderatorId, targetId,
            requireMember: type != 'ban');
      }

      final modCase = await performAction(
        client,
        guild: guild,
        type: type,
        targetId: targetId,
        moderator: await client.users.get(moderatorId),
        reason: textOrNull(row['reason']),
        duration: row['duration'] == null
            ? null
            : Duration(seconds: row['duration'] as int),
        deleteDays: row['delete_days'] as int? ?? 0,
        source: 'dashboard',
      );
      caseId = modCase.id;
    } on ModerationException catch (e) {
      status = 'failed';
      error = e.key;
    } catch (e) {
      status = 'failed';
      error = 'mod_error_unknown';
      print('Moderation action ${row['id']} failed: $e');
    }

    await withConnection((connection) => connection.query(
        'UPDATE `mod_actions` SET `status` = ?, `error` = ?, `case_id` = ?, `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;',
        [status, error, caseId, row['id']]));
  }
}

Future<void> _expire(NyxxGateway client) async {
  final bans = await withConnection((connection) async {
    await connection.query(
        "UPDATE `mod_cases` SET `active` = 0 WHERE `type` = 'timeout' AND `active` = 1 AND `expires_at` <= UTC_TIMESTAMP();");
    final rows = await connection.query(
        "SELECT * FROM `mod_cases` WHERE `type` = 'ban' AND `active` = 1 AND `expires_at` IS NOT NULL AND `expires_at` <= UTC_TIMESTAMP() LIMIT 20;");
    return rows.map(ModCase.fromRow).toList();
  });

  if (bans.isEmpty) return;
  final bot = await client.users.get(client.user.id);

  for (final ban in bans) {
    await withConnection((connection) => connection.query(
        'UPDATE `mod_cases` SET `active` = 0 WHERE `id` = ?;', [ban.id]));
    try {
      final guild = await client.guilds.get(Snowflake.parse(ban.guild));
      await performAction(
        client,
        guild: guild,
        type: 'unban',
        targetId: Snowflake.parse(ban.userId),
        moderator: bot,
        reason: await localized(
            bot, 'mod_tempban_expired', {'&number': '${ban.number}'}),
        source: 'automod',
      );
    } catch (e) {
      print('Failed to lift temporary ban ${ban.id}: $e');
    }
  }
}

Duration? parseDuration(String input) {
  final matches =
      RegExp(r'(\d+)\s*([smhdw])', caseSensitive: false).allMatches(input);
  final rest = input.replaceAll(
      RegExp(r'(\d+)\s*([smhdw])|\s', caseSensitive: false), '');
  if (matches.isEmpty || rest.isNotEmpty) return null;

  var seconds = 0;
  for (final match in matches) {
    final value = int.parse(match.group(1)!);
    seconds += value *
        switch (match.group(2)!.toLowerCase()) {
          's' => 1,
          'm' => 60,
          'h' => 3600,
          'd' => 86400,
          _ => 604800,
        };
  }
  return seconds > 0 ? Duration(seconds: seconds) : null;
}

String formatDuration(Duration duration) {
  final parts = <String>[];
  var rest = duration.inSeconds;
  for (final (unit, size) in [('d', 86400), ('h', 3600), ('m', 60), ('s', 1)]) {
    if (rest >= size) {
      parts.add('${rest ~/ size}$unit');
      rest %= size;
    }
  }
  return parts.isEmpty ? '0s' : parts.join(' ');
}

Future<void> checkHierarchy(
    NyxxGateway client, Guild guild, Snowflake moderatorId, Snowflake targetId,
    {bool requireMember = false}) async {
  if (targetId == moderatorId) throw ModerationException('mod_error_self');
  if (targetId == guild.ownerId) throw ModerationException('mod_error_owner');
  if (targetId == client.user.id) throw ModerationException('mod_error_bot');

  Member? target;
  try {
    target = await guild.members.get(targetId);
  } on HttpResponseError {
    if (requireMember) throw ModerationException('mod_error_not_member');
    return;
  }

  final roles = await guild.roles.list();
  final positions = {for (final role in roles) role.id: role.position};
  final targetTop = _highestPosition(target.roleIds, positions);

  final bot = await guild.members.get(client.user.id);
  if (_highestPosition(bot.roleIds, positions) <= targetTop) {
    throw ModerationException('mod_error_bot_hierarchy');
  }

  if (moderatorId == guild.ownerId || moderatorId == client.user.id) return;
  final moderator = await guild.members.get(moderatorId);
  if (_highestPosition(moderator.roleIds, positions) <= targetTop) {
    throw ModerationException('mod_error_hierarchy');
  }
}

Future<void> checkPermission(Guild guild, Snowflake userId, String type) async {
  if (userId == guild.ownerId) return;

  final member = await guild.members.get(userId);
  final roles = await guild.roles.list();
  var value = 0;
  for (final role in roles) {
    if (role.id == guild.id || member.roleIds.contains(role.id)) {
      value |= role.permissions.value;
    }
  }

  final permissions = Flags<Permissions>(value);
  if (permissions.has(Permissions.administrator)) return;
  if (!permissions.has(_requiredPermission[type]!)) {
    throw ModerationException('mod_error_permission');
  }
}

Future<ModCase> performAction(
  NyxxGateway client, {
  required Guild guild,
  required String type,
  required Snowflake targetId,
  required User moderator,
  String? reason,
  Duration? duration,
  int deleteDays = 0,
  String source = 'command',
}) async {
  final target = await client.users.get(targetId);
  final settings = await _guildSettings(guild.id);
  final auditReason = '${moderator.username}: ${reason ?? '-'}';
  final cleanReason = (reason ?? '').trim().isEmpty ? null : reason!.trim();

  if (type == 'timeout') {
    if (duration == null) throw ModerationException('mod_error_duration');
    if (duration > maxTimeout) {
      throw ModerationException('mod_error_timeout_max');
    }
  }

  if (type == 'unban') {
    try {
      await guild.manager.fetchBan(guild.id, targetId);
    } on HttpResponseError {
      throw ModerationException('mod_error_not_banned');
    }
  }

  var dmSent = false;
  if (settings['mod_dm'] == true &&
      const ['warn', 'timeout', 'kick', 'ban'].contains(type) &&
      !target.isBot) {
    dmSent = await _sendDm(client, target, guild, type, cleanReason, duration);
  }

  try {
    switch (type) {
      case 'timeout':
        await guild.members.update(
            targetId,
            MemberUpdateBuilder(
                communicationDisabledUntil:
                    DateTime.now().toUtc().add(duration!)),
            auditLogReason: auditReason);
      case 'untimeout':
        await guild.members.update(
            targetId, MemberUpdateBuilder(communicationDisabledUntil: null),
            auditLogReason: auditReason);
      case 'kick':
        await guild.members.delete(targetId, auditLogReason: auditReason);
      case 'ban':
        await guild.createBan(targetId,
            deleteMessages:
                deleteDays > 0 ? Duration(days: deleteDays.clamp(0, 7)) : null,
            auditLogReason: auditReason);
      case 'unban':
        await guild.deleteBan(targetId, auditLogReason: auditReason);
    }
  } on HttpResponseError catch (e) {
    throw ModerationException('mod_error_failed', {'&error': e.message});
  }

  final modCase = await createCase(
    guildId: guild.id,
    type: type,
    user: target,
    moderator: moderator,
    reason: cleanReason,
    duration: type == 'warn' ? null : duration,
    source: source,
  );

  await postModlog(client, guild, modCase, moderator,
      channelId: settings['modlog_channel'] as String?, dmSent: dmSent);

  if (type == 'warn') {
    await _escalate(client, guild, target);
  }

  return modCase;
}

Future<ModCase> createCase({
  required Snowflake guildId,
  required String type,
  required User user,
  User? moderator,
  String? reason,
  Duration? duration,
  String source = 'command',
}) {
  return withConnection((connection) async {
    final guild = guildId.toString();
    final expiresAt = duration != null && (type == 'timeout' || type == 'ban')
        ? DateTime.now().toUtc().add(duration)
        : null;

    if (type == 'untimeout') {
      await connection.query(
          "UPDATE `mod_cases` SET `active` = 0 WHERE `guild` = ? AND `user_id` = ? AND `type` = 'timeout';",
          [guild, user.id.toString()]);
    }
    if (type == 'unban') {
      await connection.query(
          "UPDATE `mod_cases` SET `active` = 0 WHERE `guild` = ? AND `user_id` = ? AND `type` = 'ban';",
          [guild, user.id.toString()]);
    }

    for (var attempt = 0;; attempt++) {
      final next = await connection.query(
          'SELECT COALESCE(MAX(`number`), 0) + 1 AS `next` FROM `mod_cases` WHERE `guild` = ?;',
          [guild]);
      final number = (next.first['next'] as num).toInt();
      try {
        final result = await connection.query(
          'INSERT INTO `mod_cases` (`guild`, `number`, `type`, `user_id`, `user_name`, `moderator_id`, `moderator_name`, `reason`, `duration`, `expires_at`, `active`, `source`, `created_at`, `updated_at`) '
          'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP());',
          [
            guild,
            number,
            type,
            user.id.toString(),
            _name(user),
            moderator?.id.toString(),
            moderator == null ? null : _name(moderator),
            reason,
            duration?.inSeconds,
            expiresAt,
            const ['warn', 'timeout', 'ban'].contains(type) ? 1 : 0,
            source,
          ],
        );
        final rows = await connection.query(
            'SELECT * FROM `mod_cases` WHERE `id` = ? LIMIT 1;',
            [result.insertId]);
        return ModCase.fromRow(rows.first);
      } on MySqlException catch (e) {
        if (e.errorNumber != 1062 || attempt >= 4) rethrow;
      }
    }
  });
}

Future<EmbedBuilder> caseEmbed(ModCase modCase, User reader) async {
  final fields = <EmbedFieldBuilder>[
    EmbedFieldBuilder(
        name: await localized(reader, 'mod_field_user'),
        value: '<@${modCase.userId}> · ${modCase.userName ?? modCase.userId}',
        isInline: true),
    EmbedFieldBuilder(
        name: await localized(reader, 'mod_field_moderator'),
        value: modCase.moderatorId == null
            ? '–'
            : '<@${modCase.moderatorId}> · ${modCase.moderatorName ?? ''}',
        isInline: true),
    if (modCase.duration != null)
      EmbedFieldBuilder(
          name: await localized(reader, 'mod_field_duration'),
          value: modCase.expiresAt == null
              ? formatDuration(Duration(seconds: modCase.duration!))
              : '${formatDuration(Duration(seconds: modCase.duration!))} · <t:${modCase.expiresAt!.millisecondsSinceEpoch ~/ 1000}:R>',
          isInline: true),
    EmbedFieldBuilder(
        name: await localized(reader, 'mod_field_reason'),
        value: modCase.reason ?? await localized(reader, 'mod_no_reason'),
        isInline: false),
  ];

  return EmbedBuilder(
    color: DiscordColor.parseHexString(_colors[modCase.type] ?? '#5865f2'),
    title: await localized(reader, 'mod_case_title', {
      '&number': '${modCase.number}',
      '&type': await localized(reader, 'mod_type_${modCase.type}'),
    }),
    fields: fields,
    footer: modCase.source == 'command'
        ? null
        : EmbedFooterBuilder(
            text: await localized(reader, 'mod_source_${modCase.source}')),
    timestamp: modCase.createdAt,
  );
}

Future<void> postModlog(
    NyxxGateway client, Guild guild, ModCase modCase, User reader,
    {String? channelId, bool dmSent = false}) async {
  channelId ??= (await _guildSettings(guild.id))['modlog_channel'] as String?;
  if (channelId == null) return;

  try {
    final channel = await client.channels[Snowflake.parse(channelId)].get();
    if (channel is! TextChannel) return;
    final message = await channel.sendMessage(MessageBuilder(
        allowedMentions: noMentions,
        embeds: [await caseEmbed(modCase, reader)]));
    await withConnection((connection) => connection.query(
        'UPDATE `mod_cases` SET `log_message_id` = ? WHERE `id` = ?;',
        [message.id.toString(), modCase.id]));
  } catch (e) {
    print('Failed to post modlog for case ${modCase.id}: $e');
  }
}

Future<List<ModCase>> casesFor(Snowflake guildId,
        {Snowflake? userId, int limit = 10}) =>
    withConnection((connection) async {
      final rows = await connection.query(
          'SELECT * FROM `mod_cases` WHERE `guild` = ?${userId == null ? '' : ' AND `user_id` = ?'} ORDER BY `number` DESC LIMIT $limit;',
          [guildId.toString(), if (userId != null) userId.toString()]);
      return rows.map(ModCase.fromRow).toList();
    });

Future<ModCase?> caseByNumber(Snowflake guildId, int number) =>
    withConnection((connection) async {
      final rows = await connection.query(
          'SELECT * FROM `mod_cases` WHERE `guild` = ? AND `number` = ? LIMIT 1;',
          [guildId.toString(), number]);
      return rows.isEmpty ? null : ModCase.fromRow(rows.first);
    });

void setupModerationHandlers(NyxxGateway client) {
  client.onGuildAuditLogCreate.listen((event) async {
    try {
      await _handleAuditEntry(client, event);
    } catch (e) {
      print('Error in audit log handler: $e');
    }
  });

  Timer.periodic(const Duration(seconds: 3), (_) async {
    if (_queueBusy) return;
    _queueBusy = true;
    try {
      await _processQueue(client);
    } catch (e) {
      print('Error in moderation queue: $e');
    } finally {
      _queueBusy = false;
    }
  });

  Timer.periodic(const Duration(minutes: 1), (_) async {
    if (_expiryBusy) return;
    _expiryBusy = true;
    try {
      await _expire(client);
    } catch (e) {
      print('Error in moderation expiry: $e');
    } finally {
      _expiryBusy = false;
    }
  });
}
