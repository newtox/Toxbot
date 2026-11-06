import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/models/guild_event.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

export 'package:toxbot/models/guild_event.dart';

const eventJoinPrefix = 'event_join:';

const eventLeavePrefix = 'event_leave:';

const eventColor = '#5865f2';

const maxEvents = 10;

const maxInvites = 50;

const maxEventName = 50;

final _mentionPattern = RegExp(r'<@!?(\d{15,22})>');

final _roleMentionPattern = RegExp(r'<@&(\d{15,22})>');

final _botAccess = _permissions([
  Permissions.viewChannel,
  Permissions.sendMessages,
  Permissions.readMessageHistory,
  Permissions.embedLinks,
  Permissions.manageChannels,
  Permissions.connect,
]);

final _memberAccess = _permissions([
  Permissions.viewChannel,
  Permissions.sendMessages,
  Permissions.readMessageHistory,
  Permissions.embedLinks,
  Permissions.attachFiles,
  Permissions.addReactions,
  Permissions.connect,
  Permissions.speak,
  Permissions.stream,
  Permissions.useVad,
]);

final _teamAccess = _permissions([
  Permissions.viewChannel,
  Permissions.sendMessages,
  Permissions.readMessageHistory,
  Permissions.embedLinks,
  Permissions.attachFiles,
  Permissions.addReactions,
  Permissions.manageMessages,
  Permissions.mentionEveryone,
  Permissions.connect,
  Permissions.speak,
  Permissions.stream,
  Permissions.useVad,
]);

final _readAccess = _permissions([
  Permissions.viewChannel,
  Permissions.readMessageHistory,
  Permissions.addReactions,
]);

final _writeAccess = _permissions([
  Permissions.sendMessages,
  Permissions.sendMessagesInThreads,
  Permissions.createPublicThreads,
  Permissions.createPrivateThreads,
]);

final _hidden = _permissions([Permissions.viewChannel]);

Flags<Permissions> _permissions(List<Flag<Permissions>> flags) =>
    Flags<Permissions>(flags.fold(0, (value, flag) => value | flag.value));

List<PermissionOverwriteBuilder> _overwrites(Guild guild, Snowflake botId,
        Snowflake roleId, Snowflake creatorId, Snowflake? teamId,
        {bool readOnly = false}) =>
    [
      PermissionOverwriteBuilder(
          id: guild.id, type: PermissionOverwriteType.role, deny: _hidden),
      PermissionOverwriteBuilder(
          id: botId, type: PermissionOverwriteType.member, allow: _botAccess),
      PermissionOverwriteBuilder(
        id: roleId,
        type: PermissionOverwriteType.role,
        allow: readOnly ? _readAccess : _memberAccess,
        deny: readOnly ? _writeAccess : null,
      ),
      PermissionOverwriteBuilder(
          id: creatorId,
          type: PermissionOverwriteType.member,
          allow: _teamAccess),
      if (teamId != null && teamId != roleId)
        PermissionOverwriteBuilder(
            id: teamId, type: PermissionOverwriteType.role, allow: _teamAccess),
    ];

Future<void> _quietly(Future<void> Function() action) async {
  try {
    await action();
  } catch (_) {}
}

Future<void> _deleteParts(NyxxRest client, Snowflake guildId,
    {Snowflake? roleId, List<Snowflake?> channels = const []}) async {
  for (final channelId in channels) {
    if (channelId != null) {
      await _quietly(() => client.channels[channelId]
          .delete(auditLogReason: 'Toxbot event ended'));
    }
  }
  if (roleId != null) {
    await _quietly(() => client.guilds[guildId].roles
        .delete(roleId, auditLogReason: 'Toxbot event ended'));
  }
}

Future<void> _respondUpdate(
    MessageComponentInteraction interaction, String content) async {
  await interaction.respond(
    MessageUpdateBuilder(content: content, allowedMentions: noMentions),
    updateMessage: true,
  );
}

Future<void> _announce(
    NyxxGateway client, GuildEvent event, String key, Snowflake userId) async {
  final channelId = event.textChannelId;
  if (channelId == null) return;
  try {
    final channel = await client.channels[channelId].get();
    if (channel is! TextChannel) return;
    await channel.sendMessage(MessageBuilder(
      allowedMentions: noMentions,
      content: await guildLocalized(event.guild, key, {'&user': '<@$userId>'}),
    ));
  } catch (e) {
    print('Posting to the event channel failed for event ${event.id}: $e');
  }
}

Future<void> _answer(NyxxGateway client,
    MessageComponentInteraction interaction, int eventId, bool join) async {
  final user = interaction.member?.user ?? interaction.user;
  if (user == null) return;

  final event = await findEvent(eventId);
  if (event == null || event.roleId == null) {
    await interaction.respond(
      MessageUpdateBuilder(
        content: await localized(user, 'event_gone'),
        components: [],
      ),
      updateMessage: true,
    );
    return;
  }

  final members = client.guilds[event.guild].members;
  try {
    if (join) {
      await members.addRole(user.id, event.roleId!,
          auditLogReason: 'Toxbot event: ${event.name}');
    } else {
      await members.removeRole(user.id, event.roleId!,
          auditLogReason: 'Toxbot event: ${event.name}');
    }
  } catch (e) {
    print('Changing the event role failed for event ${event.id}: $e');
    await interaction.respond(
      MessageBuilder(
        content: await guildLocalized(event.guild, 'event_not_member'),
        flags: MessageFlags(MessageFlags.ephemeral.value),
      ),
    );
    return;
  }

  await _respondUpdate(
    interaction,
    await guildLocalized(
        event.guild, join ? 'event_joined_status' : 'event_left_status'),
  );
  await _announce(client, event,
      join ? 'event_channel_joined' : 'event_channel_left', user.id);
}

Future<GuildEvent?> findEvent(int id, {Snowflake? guildId}) =>
    withConnection((connection) async {
      final rows = await connection.query(
        guildId == null
            ? 'SELECT * FROM `guild_events` WHERE `id` = ? LIMIT 1;'
            : 'SELECT * FROM `guild_events` WHERE `id` = ? AND `guild` = ? LIMIT 1;',
        [id, if (guildId != null) guildId.toString()],
      );
      return rows.isEmpty ? null : GuildEvent.fromRow(rows.first);
    });

Future<List<GuildEvent>> guildEvents(Snowflake guildId) =>
    withConnection((connection) async {
      final rows = await connection.query(
          'SELECT * FROM `guild_events` WHERE `guild` = ? ORDER BY `id`;',
          [guildId.toString()]);
      return rows.map(GuildEvent.fromRow).toList();
    });

Future<Iterable<CommandOptionChoiceBuilder<String>>> eventChoices(
  AutocompleteContext context,
) async {
  final guild = context.guild;
  if (guild == null) return const [];
  try {
    final search = context.currentValue.toLowerCase();
    return (await guildEvents(guild.id))
        .where((event) => event.name.toLowerCase().contains(search))
        .take(25)
        .map((event) =>
            CommandOptionChoiceBuilder(name: event.name, value: '${event.id}'));
  } catch (e) {
    print('Error in event autocomplete: $e');
    return const [];
  }
}

Future<GuildEvent?> createEvent(
    NyxxGateway client, Guild guild, String name, Member creator,
    {Role? team}) async {
  final reason = 'Toxbot event: $name';
  Role? role;
  final channels = <Snowflake>[];

  try {
    role = await guild.roles.create(
      RoleBuilder(
          name: name,
          permissions: Flags<Permissions>(0),
          isMentionable: false,
          isHoisted: false),
      auditLogReason: reason,
    );
    final overwrites =
        _overwrites(guild, client.user.id, role.id, creator.id, team?.id);

    final category = await guild.createChannel(
      GuildChannelBuilder(
          name: name,
          type: ChannelType.guildCategory,
          permissionOverwrites: overwrites),
      auditLogReason: reason,
    );
    channels.add(category.id);

    final info = await guild.createChannel(
      GuildTextChannelBuilder(
        name: await guildLocalized(guild.id, 'event_info_channel'),
        parentId: category.id,
        permissionOverwrites: _overwrites(
            guild, client.user.id, role.id, creator.id, team?.id,
            readOnly: true),
      ),
      auditLogReason: reason,
    );
    channels.insert(0, info.id);

    final text = await guild.createChannel(
      GuildTextChannelBuilder(
        name: await guildLocalized(guild.id, 'event_text_channel'),
        parentId: category.id,
        permissionOverwrites: overwrites,
      ),
      auditLogReason: reason,
    );
    channels.insert(0, text.id);

    final voice = await guild.createChannel(
      GuildVoiceChannelBuilder(
        name: await guildLocalized(guild.id, 'event_voice_channel'),
        parentId: category.id,
        permissionOverwrites: overwrites,
      ),
      auditLogReason: reason,
    );
    channels.insert(0, voice.id);

    final id = await withConnection((connection) async {
      final result = await connection.query(
        'INSERT INTO `guild_events` (`guild`, `name`, `role_id`, `team_role_id`, `category_id`, `info_channel_id`, `text_channel_id`, `voice_channel_id`, `created_by`, `created_at`, `updated_at`) '
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP());',
        [
          guild.id.toString(),
          name,
          role!.id.toString(),
          team?.id.toString(),
          category.id.toString(),
          info.id.toString(),
          text.id.toString(),
          voice.id.toString(),
          creator.id.toString(),
        ],
      );
      return result.insertId!;
    });

    await _quietly(() =>
        guild.members.addRole(creator.id, role!.id, auditLogReason: reason));

    return await findEvent(id);
  } catch (e) {
    print('Creating the event "$name" failed in ${guild.id}: $e');
    await _deleteParts(client, guild.id, roleId: role?.id, channels: channels);
    return null;
  }
}

Future<void> endEvent(NyxxGateway client, GuildEvent event) async {
  await _deleteParts(
    client,
    event.guild,
    roleId: event.roleId,
    channels: [
      event.voiceChannelId,
      event.textChannelId,
      event.infoChannelId,
      event.categoryId,
    ],
  );
  await withConnection((connection) => connection
      .query('DELETE FROM `guild_events` WHERE `id` = ?;', [event.id]));
}

Future<Set<Snowflake>> inviteTargets(Guild guild, String text) async {
  final ids = {
    for (final match in _mentionPattern.allMatches(text))
      Snowflake.parse(match.group(1)!),
  };
  final roles = {
    for (final match in _roleMentionPattern.allMatches(text))
      Snowflake.parse(match.group(1)!),
  };
  if (roles.isNotEmpty) {
    final members = await guild.members.list(limit: 1000);
    for (final member in members) {
      if (member.roleIds.any(roles.contains)) ids.add(member.id);
    }
  }
  return ids;
}

Future<bool> sendInvite(NyxxGateway client, GuildEvent event, Guild guild,
    User inviter, Snowflake userId, String? message) async {
  try {
    final user = await client.users.get(userId);
    if (user.isBot) return false;
    final dm = await client.users.createDm(userId);
    await dm.sendMessage(MessageBuilder(
      allowedMentions: noMentions,
      embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(eventColor),
          title: await guildLocalized(
              guild.id, 'event_invite_title', {'&name': event.name}),
          description: message == null || message.trim().isEmpty
              ? await guildLocalized(guild.id, 'event_invite_text',
                  {'&name': event.name, '&server': guild.name})
              : message,
          footer: EmbedFooterBuilder(
            text: await guildLocalized(guild.id, 'event_invite_from', {
              '&user': inviter.globalName ?? inviter.username,
              '&server': guild.name,
            }),
          ),
        ),
      ],
      components: [
        ActionRowBuilder(components: [
          ButtonBuilder.success(
            customId: '$eventJoinPrefix${event.id}',
            label: await guildLocalized(guild.id, 'event_join_button'),
          ),
          ButtonBuilder.secondary(
            customId: '$eventLeavePrefix${event.id}',
            label: await guildLocalized(guild.id, 'event_leave_button'),
          ),
        ]),
      ],
    ));
    return true;
  } catch (e) {
    print('Sending an event invite to $userId failed: $e');
    return false;
  }
}

void setupGuildEventHandlers(NyxxGateway client) {
  client.onMessageComponentInteraction.listen((event) async {
    final interaction = event.interaction;
    final customId = interaction.data.customId;
    final join = customId.startsWith(eventJoinPrefix);
    if (!join && !customId.startsWith(eventLeavePrefix)) return;

    final id = int.tryParse(
        customId.substring((join ? eventJoinPrefix : eventLeavePrefix).length));
    if (id == null) return;
    try {
      await _answer(client, interaction, id, join);
    } catch (e) {
      print('Error in event interaction $customId: $e');
    }
  });

  client.onChannelDelete.listen((event) async {
    try {
      await withConnection((connection) => connection.query(
            'UPDATE `guild_events` SET `text_channel_id` = IF(`text_channel_id` = ?, NULL, `text_channel_id`), '
            '`voice_channel_id` = IF(`voice_channel_id` = ?, NULL, `voice_channel_id`), '
            '`info_channel_id` = IF(`info_channel_id` = ?, NULL, `info_channel_id`), '
            '`category_id` = IF(`category_id` = ?, NULL, `category_id`) '
            'WHERE ? IN (`text_channel_id`, `voice_channel_id`, `info_channel_id`, `category_id`);',
            List.filled(5, event.channel.id.toString()),
          ));
    } catch (e) {
      print('Forgetting a deleted event channel failed: $e');
    }
  });
}
