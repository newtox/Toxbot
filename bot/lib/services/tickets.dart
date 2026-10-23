import 'dart:async';
import 'dart:convert';

import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/models/ticket.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

export 'package:toxbot/models/ticket.dart';

const ticketOpenPrefix = 'ticket_open:';

const ticketCloseId = 'ticket_close';

const _ticketColor = '#5865f2';

const _closedColor = '#80848e';

const _transcriptLimit = 2000;

const _deleteDelay = Duration(seconds: 5);

final Set<String> _opening = {};

final Set<Snowflake> _closing = {};

final _memberAccess = _permissions([
  Permissions.viewChannel,
  Permissions.sendMessages,
  Permissions.readMessageHistory,
  Permissions.attachFiles,
  Permissions.embedLinks,
]);

final _botAccess = _permissions([
  Permissions.viewChannel,
  Permissions.sendMessages,
  Permissions.readMessageHistory,
  Permissions.manageChannels,
  Permissions.manageMessages,
  Permissions.embedLinks,
  Permissions.attachFiles,
]);

final _hidden = _permissions([Permissions.viewChannel]);

Flags<Permissions> _permissions(List<Flag<Permissions>> flags) =>
    Flags<Permissions>(flags.fold(0, (value, flag) => value | flag.value));

Future<ResultRow?> _guildRow(Snowflake guildId) =>
    withConnection((connection) async {
      final rows = await connection.query(
          'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
          [guildId.toString()]);
      return rows.isEmpty ? null : rows.first;
    });

Future<TicketType?> _type(Snowflake guildId, int? typeId) async {
  if (typeId == null) return null;
  return withConnection((connection) async {
    final rows = await connection.query(
        'SELECT * FROM `ticket_types` WHERE `id` = ? AND `guild` = ? LIMIT 1;',
        [typeId, guildId.toString()]);
    return rows.isEmpty ? null : TicketType.fromRow(rows.first);
  });
}

Future<int> _openCount(Snowflake guildId, Snowflake userId) =>
    withConnection((connection) async {
      final rows = await connection.query(
          "SELECT COUNT(*) AS `open` FROM `tickets` WHERE `guild` = ? AND `user_id` = ? AND `status` = 'open';",
          [guildId.toString(), userId.toString()]);
      return (rows.first['open'] as num).toInt();
    });

Future<Ticket> _insertTicket(Snowflake guildId, TicketType type, User user) =>
    withConnection((connection) async {
      for (var attempt = 0;; attempt++) {
        final next = await connection.query(
            'SELECT COALESCE(MAX(`number`), 0) + 1 AS `next` FROM `tickets` WHERE `guild` = ?;',
            [guildId.toString()]);
        final number = (next.first['next'] as num).toInt();
        try {
          final result = await connection.query(
            'INSERT INTO `tickets` (`guild`, `number`, `type_id`, `type_name`, `user_id`, `user_name`, `status`, `created_at`, `updated_at`) '
            "VALUES (?, ?, ?, ?, ?, ?, 'open', UTC_TIMESTAMP(), UTC_TIMESTAMP());",
            [
              guildId.toString(),
              number,
              type.id,
              type.name,
              user.id.toString(),
              user.username,
            ],
          );
          final rows = await connection.query(
              'SELECT * FROM `tickets` WHERE `id` = ? LIMIT 1;',
              [result.insertId]);
          return Ticket.fromRow(rows.first);
        } on MySqlException catch (e) {
          if (e.errorNumber != 1062 || attempt >= 4) rethrow;
        }
      }
    });

Future<void> _setChannel(Ticket ticket, Snowflake channelId) =>
    withConnection((connection) => connection.query(
        'UPDATE `tickets` SET `channel_id` = ?, `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;',
        [channelId.toString(), ticket.id]));

Future<void> _deleteTicket(Ticket ticket) => withConnection((connection) =>
    connection.query('DELETE FROM `tickets` WHERE `id` = ?;', [ticket.id]));

Future<bool> _hasPermission(
    Guild guild, Member member, Flag<Permissions> flag) async {
  if (member.id == guild.ownerId) return true;
  final roles = await guild.roles.list();
  var value = 0;
  for (final role in roles) {
    if (role.id == guild.id || member.roleIds.contains(role.id)) {
      value |= role.permissions.value;
    }
  }
  final permissions = Flags<Permissions>(value);
  return permissions.has(Permissions.administrator) || permissions.has(flag);
}

Future<bool> _isTeam(Guild guild, Member member, Ticket ticket) async {
  final type = await _type(guild.id, ticket.typeId);
  if (type != null && member.roleIds.any(type.roles.contains)) return true;
  return _hasPermission(guild, member, Permissions.manageChannels);
}

String _authorName(MessageAuthor author) =>
    author is User ? (author.globalName ?? author.username) : author.username;

String? _messageText(Message message) {
  final parts = [
    if (message.content.isNotEmpty) message.content,
    for (final embed in message.embeds)
      [embed.title, embed.description]
          .whereType<String>()
          .where((part) => part.isNotEmpty)
          .join('\n'),
  ].where((part) => part.isNotEmpty);
  return parts.isEmpty ? null : parts.join('\n');
}

Future<List<Message>> _history(TextChannel channel) async {
  final messages = <Message>[];
  Snowflake? before;
  while (messages.length < _transcriptLimit) {
    final page = await channel.messages.fetchMany(limit: 100, before: before);
    if (page.isEmpty) break;
    messages.addAll(page);
    before = page.last.id;
    if (page.length < 100) break;
  }
  return messages.reversed.toList();
}

Future<String> _saveTranscript(Ticket ticket, List<Message> messages) async {
  final lines = <String>[];
  await withConnection((connection) async {
    for (final message in messages) {
      final author = message.author;
      final text = _messageText(message);
      final attachments = message.attachments
          .map((attachment) =>
              {'name': attachment.fileName, 'url': attachment.url.toString()})
          .toList();
      await connection.query(
        'INSERT INTO `ticket_messages` (`ticket_id`, `author_id`, `author_name`, `author_avatar`, `author_bot`, `content`, `attachments`, `sent_at`) '
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?);',
        [
          ticket.id,
          author.id.toString(),
          _authorName(author),
          author.avatar?.url.toString(),
          author is User && author.isBot ? 1 : 0,
          text,
          attachments.isEmpty ? null : jsonEncode(attachments),
          message.timestamp.toUtc(),
        ],
      );

      final time = message.timestamp.toUtc().toIso8601String().substring(0, 16);
      lines
          .add('[$time UTC] ${_authorName(author)}: ${text ?? ''}'.trimRight());
      for (final attachment in attachments) {
        lines.add('    ${attachment['name']}: ${attachment['url']}');
      }
    }
  });
  return lines.join('\n');
}

Future<void> _postLog(NyxxGateway client, ResultRow settings, Ticket ticket,
    User closer, String? reason, String transcript) async {
  final channelId = textOrNull(settings['ticket_log_channel']);
  if (channelId == null) return;

  try {
    final channel = await client.channels[Snowflake.parse(channelId)].get();
    if (channel is! TextChannel) return;

    final baseUrl = publicDashboardUrl;
    await channel.sendMessage(MessageBuilder(
      allowedMentions: noMentions,
      embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(_closedColor),
          title: await guildLocalized(ticket.guild, 'ticket_log_title',
              {'&number': ticket.paddedNumber}),
          url: baseUrl == null
              ? null
              : Uri.parse(
                  '$baseUrl/servers/${ticket.guild}/ticket-history/${ticket.id}'),
          fields: [
            EmbedFieldBuilder(
                name:
                    await guildLocalized(ticket.guild, 'ticket_log_opened_by'),
                value: '<@${ticket.userId}> `${ticket.userId}`',
                isInline: true),
            EmbedFieldBuilder(
                name: await guildLocalized(ticket.guild, 'ticket_log_type'),
                value: ticket.typeName ?? '–',
                isInline: true),
            EmbedFieldBuilder(
                name:
                    await guildLocalized(ticket.guild, 'ticket_log_closed_by'),
                value: '<@${closer.id}>',
                isInline: true),
            EmbedFieldBuilder(
                name: await guildLocalized(ticket.guild, 'ticket_log_reason'),
                value: reason ??
                    await guildLocalized(ticket.guild, 'ticket_no_reason'),
                isInline: false),
          ],
          timestamp: DateTime.now().toUtc(),
        ),
      ],
      attachments: [
        AttachmentBuilder(
          data: utf8.encode(transcript.isEmpty ? '-' : transcript),
          fileName: 'ticket-${ticket.paddedNumber}.txt',
        ),
      ],
    ));
  } catch (e) {
    print('Posting the ticket log failed in ${ticket.guild}: $e');
  }
}

Future<void> _open(NyxxGateway client, MessageComponentInteraction interaction,
    int typeId) async {
  final guildId = interaction.guildId;
  final member = interaction.member;
  final user = member?.user ?? interaction.user;
  if (guildId == null || member == null || user == null) return;

  await interaction.acknowledge(isEphemeral: true);
  Future<void> reply(String key,
      [Map<String, String> replace = const {}]) async {
    await interaction.updateOriginalResponse(MessageUpdateBuilder(
        content: await localized(user, key, replace),
        allowedMentions: noMentions));
  }

  final key = '$guildId:${user.id}';
  if (!_opening.add(key)) return;
  try {
    final settings = await _guildRow(guildId);
    final categoryId =
        settings == null ? null : textOrNull(settings['ticket_category']);
    if (settings == null || categoryId == null) {
      await reply('ticket_not_setup');
      return;
    }

    final type = await _type(guildId, typeId);
    if (type == null) {
      await reply('ticket_type_missing');
      return;
    }

    final limit = settings['ticket_limit'] as int? ?? 1;
    if (await _openCount(guildId, user.id) >= limit) {
      await reply('ticket_limit', {'&limit': '$limit'});
      return;
    }

    final guild = await client.guilds.get(guildId);
    final ticket = await _insertTicket(guildId, type, user);

    final GuildTextChannel channel;
    try {
      channel = await guild.createChannel(
        GuildTextChannelBuilder(
          name: 'ticket-${ticket.paddedNumber}',
          parentId: Snowflake.parse(categoryId),
          topic: '${type.name} · ${user.username} (${user.id})',
          permissionOverwrites: [
            PermissionOverwriteBuilder(
                id: guild.id,
                type: PermissionOverwriteType.role,
                deny: _hidden),
            PermissionOverwriteBuilder(
                id: client.user.id,
                type: PermissionOverwriteType.member,
                allow: _botAccess),
            PermissionOverwriteBuilder(
                id: user.id,
                type: PermissionOverwriteType.member,
                allow: _memberAccess),
            for (final role in type.roles)
              PermissionOverwriteBuilder(
                  id: role,
                  type: PermissionOverwriteType.role,
                  allow: _memberAccess),
          ],
        ),
        auditLogReason: 'Toxbot ticket #${ticket.paddedNumber}',
      );
    } catch (e) {
      print('Creating a ticket channel failed in $guildId: $e');
      await _deleteTicket(ticket);
      await reply('ticket_failed');
      return;
    }

    await _setChannel(ticket, channel.id);

    await channel.sendMessage(MessageBuilder(
      content: [
        '<@${user.id}>',
        for (final role in type.roles) '<@&$role>',
      ].join(' '),
      allowedMentions:
          AllowedMentions(users: [user.id], roles: type.roles.toList()),
      embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(_ticketColor),
          title: '${type.label} · #${ticket.paddedNumber}',
          description: type.description ??
              await guildLocalized(
                  guildId, 'ticket_welcome_default', {'&user': user.username}),
        ),
      ],
      components: [
        ActionRowBuilder(components: [
          ButtonBuilder.danger(
              customId: ticketCloseId,
              label: await guildLocalized(guildId, 'ticket_close_button')),
        ]),
      ],
    ));

    await reply('ticket_opened', {'&channel': '<#${channel.id}>'});
  } finally {
    _opening.remove(key);
  }
}

Future<void> _closeFromButton(
    NyxxGateway client, MessageComponentInteraction interaction) async {
  final guildId = interaction.guildId;
  final member = interaction.member;
  final user = member?.user ?? interaction.user;
  if (guildId == null || member == null || user == null) return;

  final ticket = await ticketForChannel(interaction.channelId!);
  if (ticket == null) {
    await interaction.respond(MessageBuilder(
        content: await localized(user, 'ticket_not_ticket'),
        flags: MessageFlags(MessageFlags.ephemeral.value)));
    return;
  }

  final guild = await client.guilds.get(guildId);
  if (!await mayManageTicket(guild, member, ticket)) {
    await interaction.respond(MessageBuilder(
        content: await localized(user, 'ticket_no_permission'),
        flags: MessageFlags(MessageFlags.ephemeral.value)));
    return;
  }

  await interaction.respond(MessageBuilder(
    allowedMentions: noMentions,
    content: await guildLocalized(
        ticket.guild, 'ticket_closing', {'&user': '<@${user.id}>'}),
  ));
  await closeTicket(client, ticket, user);
}

Future<Ticket?> ticketForChannel(Snowflake channelId) =>
    withConnection((connection) async {
      final rows = await connection.query(
          "SELECT * FROM `tickets` WHERE `channel_id` = ? AND `status` = 'open' LIMIT 1;",
          [channelId.toString()]);
      return rows.isEmpty ? null : Ticket.fromRow(rows.first);
    });

Future<bool> mayManageTicket(Guild guild, Member member, Ticket ticket) async =>
    member.id == ticket.userId || await _isTeam(guild, member, ticket);

Future<bool> isTicketTeam(Guild guild, Member member, Ticket ticket) =>
    _isTeam(guild, member, ticket);

Future<void> closeTicket(NyxxGateway client, Ticket ticket, User closer,
    {String? reason}) async {
  final channelId = ticket.channelId;
  if (channelId == null || !_closing.add(channelId)) return;

  try {
    final channel = await client.channels[channelId].get();
    var transcript = '';
    if (channel is TextChannel) {
      try {
        transcript = await _saveTranscript(ticket, await _history(channel));
      } catch (e) {
        print('Saving the transcript failed for ticket ${ticket.id}: $e');
      }
    }

    await withConnection((connection) => connection.query(
        "UPDATE `tickets` SET `status` = 'closed', `closed_by` = ?, `closed_by_name` = ?, `close_reason` = ?, `closed_at` = UTC_TIMESTAMP(), `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;",
        [closer.id.toString(), closer.username, reason, ticket.id]));

    final settings = await _guildRow(Snowflake.parse(ticket.guild));
    if (settings != null) {
      await _postLog(client, settings, ticket, closer, reason, transcript);
    }

    await Future.delayed(_deleteDelay);
    await channel.delete(
        auditLogReason: 'Toxbot ticket #${ticket.paddedNumber} closed');
  } catch (e) {
    print('Closing ticket ${ticket.id} failed: $e');
  } finally {
    _closing.remove(channelId);
  }
}

void setupTicketHandlers(NyxxGateway client) {
  client.onMessageComponentInteraction.listen((event) async {
    final interaction = event.interaction;
    final customId = interaction.data.customId;

    try {
      if (customId.startsWith(ticketOpenPrefix)) {
        final typeId =
            int.tryParse(customId.substring(ticketOpenPrefix.length));
        if (typeId != null) await _open(client, interaction, typeId);
      } else if (customId == ticketCloseId) {
        await _closeFromButton(client, interaction);
      }
    } catch (e) {
      print('Error in ticket interaction $customId: $e');
    }
  });

  client.onChannelDelete.listen((event) async {
    final channelId = event.channel.id;
    if (_closing.contains(channelId)) return;

    try {
      await withConnection((connection) => connection.query(
          "UPDATE `tickets` SET `status` = 'closed', `closed_at` = UTC_TIMESTAMP(), `updated_at` = UTC_TIMESTAMP() WHERE `channel_id` = ? AND `status` = 'open';",
          [channelId.toString()]));
    } catch (e) {
      print('Marking a deleted ticket channel as closed failed: $e');
    }
  });
}
