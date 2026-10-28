import 'dart:convert';

import 'package:nyxx/nyxx.dart';

const _permissionNames = [
  'CREATE_INSTANT_INVITE',
  'KICK_MEMBERS',
  'BAN_MEMBERS',
  'ADMINISTRATOR',
  'MANAGE_CHANNELS',
  'MANAGE_GUILD',
  'ADD_REACTIONS',
  'VIEW_AUDIT_LOG',
  'PRIORITY_SPEAKER',
  'STREAM',
  'VIEW_CHANNEL',
  'SEND_MESSAGES',
  'SEND_TTS_MESSAGES',
  'MANAGE_MESSAGES',
  'EMBED_LINKS',
  'ATTACH_FILES',
  'READ_MESSAGE_HISTORY',
  'MENTION_EVERYONE',
  'USE_EXTERNAL_EMOJIS',
  'VIEW_GUILD_INSIGHTS',
  'CONNECT',
  'SPEAK',
  'MUTE_MEMBERS',
  'DEAFEN_MEMBERS',
  'MOVE_MEMBERS',
  'USE_VAD',
  'CHANGE_NICKNAME',
  'MANAGE_NICKNAMES',
  'MANAGE_ROLES',
  'MANAGE_WEBHOOKS',
  'MANAGE_GUILD_EXPRESSIONS',
  'USE_APPLICATION_COMMANDS',
  'REQUEST_TO_SPEAK',
  'MANAGE_EVENTS',
  'MANAGE_THREADS',
  'CREATE_PUBLIC_THREADS',
  'CREATE_PRIVATE_THREADS',
  'USE_EXTERNAL_STICKERS',
  'SEND_MESSAGES_IN_THREADS',
  'USE_EMBEDDED_ACTIVITIES',
  'MODERATE_MEMBERS',
  'VIEW_CREATOR_MONETIZATION_ANALYTICS',
  'USE_SOUNDBOARD',
  'CREATE_GUILD_EXPRESSIONS',
  'CREATE_EVENTS',
  'USE_EXTERNAL_SOUNDS',
  'SEND_VOICE_MESSAGES',
  null,
  null,
  'SEND_POLLS',
  'USE_EXTERNAL_APPS',
  'PIN_MESSAGES',
  'BYPASS_SLOWMODE',
];

const _channelTypes = {
  0: 'text',
  2: 'voice',
  4: 'category',
  5: 'announcement',
  13: 'stage',
  15: 'forum',
  16: 'media',
};

List<String> _permissions(Object? value) {
  final bits = BigInt.parse(value?.toString() ?? '0');
  return [
    for (var i = 0; i < bits.bitLength; i++)
      if ((bits >> i) & BigInt.one == BigInt.one)
        (i < _permissionNames.length ? _permissionNames[i] : null) ?? 'BIT_$i',
  ];
}

Future<List<Map<String, dynamic>>> _get(
  NyxxRest client,
  String guildId,
  String resource,
) async {
  final route = HttpRoute()
    ..add(HttpRoutePart('guilds', [HttpRouteParam(guildId, isMajor: true)]))
    ..add(HttpRoutePart(resource));
  final response = await client.httpHandler.executeSafe(BasicRequest(route));
  return (response.jsonBody as List).cast<Map<String, dynamic>>();
}

String _overwriteKey(Map<String, dynamic> overwrite) =>
    '${overwrite['id']}:${overwrite['allow']}:${overwrite['deny']}';

Map<String, dynamic> _channel(
  Map<String, dynamic> channel,
  Map<String, Map<String, dynamic>> byId,
  Map<String, String> roleNames,
  String guildId,
) {
  final overwrites = ((channel['permission_overwrites'] as List?) ?? const [])
      .cast<Map<String, dynamic>>();
  final parent = byId[channel['parent_id']];
  final parentOverwrites = parent == null
      ? null
      : ((parent['permission_overwrites'] as List?) ?? const [])
          .cast<Map<String, dynamic>>()
          .map(_overwriteKey)
          .toSet();

  return {
    'id': channel['id'],
    'name': channel['name'],
    'type': _channelTypes[channel['type']] ?? channel['type'],
    'position': channel['position'],
    if (channel['topic'] != null) 'topic': channel['topic'],
    if (channel['nsfw'] == true) 'nsfw': true,
    if (parentOverwrites != null)
      'synced_with_category': parentOverwrites.length == overwrites.length &&
          overwrites.map(_overwriteKey).every(parentOverwrites.contains),
    'overwrites': [
      for (final overwrite in overwrites)
        {
          'target': overwrite['type'] == 1
              ? 'member:${overwrite['id']}'
              : overwrite['id'] == guildId
                  ? '@everyone'
                  : roleNames[overwrite['id']] ?? 'role:${overwrite['id']}',
          'id': overwrite['id'],
          if (_permissions(overwrite['allow']).isNotEmpty)
            'allow': _permissions(overwrite['allow']),
          if (_permissions(overwrite['deny']).isNotEmpty)
            'deny': _permissions(overwrite['deny']),
        },
    ],
  };
}

Future<String> exportGuildStructure(NyxxRest client, Guild guild) async {
  final guildId = guild.id.toString();
  final roles = await _get(client, guildId, 'roles');
  final channels = await _get(client, guildId, 'channels');

  roles.sort((a, b) => (b['position'] as int).compareTo(a['position'] as int));
  channels
      .sort((a, b) => (a['position'] as int).compareTo(b['position'] as int));

  final roleNames = {
    for (final role in roles) role['id'] as String: role['name'] as String,
  };
  final byId = {
    for (final channel in channels) channel['id'] as String: channel
  };

  Map<String, dynamic> describe(Map<String, dynamic> channel) =>
      _channel(channel, byId, roleNames, guildId);

  final categories = channels.where((c) => c['type'] == 4);
  final structure = {
    'guild': {'id': guildId, 'name': guild.name},
    'exported_at': DateTime.now().toUtc().toIso8601String(),
    'roles': [
      for (final role in roles)
        {
          'id': role['id'],
          'name': role['name'],
          'position': role['position'],
          'color':
              '#${(role['color'] as int).toRadixString(16).padLeft(6, '0')}',
          'hoist': role['hoist'],
          'mentionable': role['mentionable'],
          'managed': role['managed'],
          'permissions': _permissions(role['permissions']),
        },
    ],
    'channels_without_category': [
      for (final channel in channels)
        if (channel['type'] != 4 && channel['parent_id'] == null)
          describe(channel),
    ],
    'categories': [
      for (final category in categories)
        {
          ...describe(category),
          'channels': [
            for (final channel in channels)
              if (channel['parent_id'] == category['id']) describe(channel),
          ],
        },
    ],
  };

  return const JsonEncoder.withIndent('  ').convert(structure);
}
