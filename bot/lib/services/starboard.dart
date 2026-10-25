import 'dart:async';

import 'package:nyxx/nyxx.dart';
import 'package:toxbot/models/starboard_settings.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

const _starColor = '#f0b232';

const _defaultEmoji = '⭐';

const _debounce = Duration(seconds: 2);

const _settingsLifetime = Duration(minutes: 1);

const _maxDescription = 4000;

final _customEmoji = RegExp(r'^<(a?):(\w+):(\d+)>$');

final Map<Snowflake, (StarboardSettings?, DateTime)> _settingsCache = {};

final Map<Snowflake, Timer> _pending = {};

final Map<Snowflake, Future<void>> _running = {};

Future<StarboardSettings?> _settings(Snowflake guildId) async {
  final cached = _settingsCache[guildId];
  if (cached != null &&
      DateTime.now().difference(cached.$2) < _settingsLifetime) {
    return cached.$1;
  }

  final settings = await withConnection((connection) async {
    final rows = await connection.query(
        'SELECT `starboard_channel`, `starboard_emoji`, `starboard_threshold` FROM `guilds` WHERE `id` = ? LIMIT 1;',
        [guildId.toString()]);
    if (rows.isEmpty) return null;
    final channel = textOrNull(rows.first['starboard_channel']);
    if (channel == null || channel.isEmpty) return null;

    final emoji = textOrNull(rows.first['starboard_emoji']) ?? _defaultEmoji;
    final custom = _customEmoji.firstMatch(emoji);
    return StarboardSettings(
      channelId: Snowflake.parse(channel),
      emoji: emoji.isEmpty ? _defaultEmoji : emoji,
      emojiName: custom?.group(2) ?? (emoji.isEmpty ? _defaultEmoji : emoji),
      emojiId: custom == null ? null : Snowflake.parse(custom.group(3)!),
      threshold: (rows.first['starboard_threshold'] as int?) ?? 3,
    );
  });

  _settingsCache[guildId] = (settings, DateTime.now());
  return settings;
}

String _plain(String name) => name.replaceAll('️', '');

bool _matches(PartialEmoji emoji, StarboardSettings settings) {
  if (settings.emojiId != null) return emoji.id == settings.emojiId;
  return emoji is TextEmoji && _plain(emoji.name) == _plain(settings.emojiName);
}

String _authorName(MessageAuthor author) =>
    author is User ? (author.globalName ?? author.username) : author.username;

String? _imageUrl(Message message) {
  for (final attachment in message.attachments) {
    if (attachment.contentType?.startsWith('image/') ?? false) {
      return attachment.url.toString();
    }
  }
  for (final embed in message.embeds) {
    final url = embed.image?.url ?? embed.thumbnail?.url;
    if (url != null) return url.toString();
  }
  return null;
}

String? _text(Message message) {
  final parts = [
    if (message.content.isNotEmpty) message.content,
    if (message.content.isEmpty)
      for (final embed in message.embeds)
        [embed.title, embed.description]
            .whereType<String>()
            .where((part) => part.isNotEmpty)
            .join('\n'),
  ].where((part) => part.isNotEmpty);
  if (parts.isEmpty) return null;
  final text = parts.join('\n');
  return text.length > _maxDescription
      ? '${text.substring(0, _maxDescription)}…'
      : text;
}

String _jumpUrl(Snowflake guildId, Snowflake channelId, Snowflake messageId) =>
    'https://discord.com/channels/$guildId/$channelId/$messageId';

Future<int> _countStars(Message message, StarboardSettings settings) async {
  final reaction = message.reactions
      .where((reaction) => _matches(reaction.emoji, settings))
      .firstOrNull;
  if (reaction == null || reaction.count == 0) return 0;

  final builder =
      ReactionBuilder(name: settings.emojiName, id: settings.emojiId);
  final users = await message.fetchReactions(builder, limit: 100);
  final ignored =
      users.where((user) => user.isBot || user.id == message.author.id).length;
  final counted = users.length - ignored;
  return reaction.count > users.length ? reaction.count - ignored : counted;
}

Future<(Snowflake, Snowflake)?> _existingPost(Snowflake messageId) async {
  return withConnection((connection) async {
    final rows = await connection.query(
        'SELECT `starboard_channel_id`, `starboard_message_id` FROM `starboard_posts` WHERE `message_id` = ? LIMIT 1;',
        [messageId.toString()]);
    if (rows.isEmpty) return null;
    final channel = textOrNull(rows.first['starboard_channel_id']);
    final message = textOrNull(rows.first['starboard_message_id']);
    if (channel == null || message == null) return null;
    return (Snowflake.parse(channel), Snowflake.parse(message));
  });
}

Future<void> _forget(Snowflake messageId) async {
  await withConnection((connection) => connection.query(
      'DELETE FROM `starboard_posts` WHERE `message_id` = ?;',
      [messageId.toString()]));
}

Future<void> _removePost(NyxxGateway client, Snowflake messageId) async {
  final existing = await _existingPost(messageId);
  if (existing != null) {
    try {
      final channel = await client.channels.get(existing.$1) as TextChannel;
      await channel.messages.delete(existing.$2);
    } catch (e) {
      print('Deleting a starboard post failed: $e');
    }
  }
  await _forget(messageId);
}

Future<MessageBuilder> _postMessage(Snowflake guildId, Message message,
    StarboardSettings settings, int stars) async {
  final author = message.author;
  final image = _imageUrl(message);
  final url = _jumpUrl(guildId, message.channelId, message.id);
  return MessageBuilder(
    content: '${settings.emoji} **$stars** · <#${message.channelId}>',
    allowedMentions: noMentions,
    embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(_starColor),
        author: EmbedAuthorBuilder(
          name: _authorName(author),
          iconUrl: author.avatar?.url,
        ),
        description: _text(message),
        image: image == null ? null : EmbedImageBuilder(url: Uri.parse(image)),
        timestamp: message.timestamp,
      ),
    ],
    components: [
      ActionRowBuilder(components: [
        ButtonBuilder.link(
            label: await guildLocalized(guildId, 'starboard_jump'),
            url: Uri.parse(url)),
      ]),
    ],
  );
}

Future<void> _savePost(Snowflake guildId, Message message, int stars,
    Snowflake starboardChannelId, Snowflake starboardMessageId) async {
  final author = message.author;
  await withConnection((connection) => connection.query(
        'INSERT INTO `starboard_posts` (`guild`, `channel_id`, `message_id`, `author_id`, `author_name`, `author_avatar`, `content`, `image_url`, `stars`, `starboard_channel_id`, `starboard_message_id`, `message_created_at`, `created_at`, `updated_at`) '
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP()) '
        'ON DUPLICATE KEY UPDATE `author_name` = VALUES(`author_name`), `author_avatar` = VALUES(`author_avatar`), `content` = VALUES(`content`), `image_url` = VALUES(`image_url`), `stars` = VALUES(`stars`), '
        '`starboard_channel_id` = VALUES(`starboard_channel_id`), `starboard_message_id` = VALUES(`starboard_message_id`), `updated_at` = UTC_TIMESTAMP();',
        [
          guildId.toString(),
          message.channelId.toString(),
          message.id.toString(),
          author.id.toString(),
          _authorName(author),
          author.avatar?.url.toString(),
          _text(message),
          _imageUrl(message),
          stars,
          starboardChannelId.toString(),
          starboardMessageId.toString(),
          message.timestamp.toUtc(),
        ],
      ));
}

Future<void> _update(NyxxGateway client, Snowflake guildId, Snowflake channelId,
    Snowflake messageId) async {
  final settings = await _settings(guildId);
  if (settings == null || channelId == settings.channelId) return;

  final source = await client.channels.get(channelId);
  if (source is! TextChannel) return;
  final starboard = await client.channels.get(settings.channelId);
  if (starboard is! TextChannel) return;
  if (source is GuildChannel &&
      (source as GuildChannel).isNsfw &&
      !(starboard is GuildChannel && (starboard as GuildChannel).isNsfw)) {
    return;
  }

  final Message message;
  try {
    message = await source.messages.get(messageId);
  } catch (_) {
    await _removePost(client, messageId);
    return;
  }

  final stars = await _countStars(message, settings);
  final existing = await _existingPost(messageId);

  if (stars < settings.threshold) {
    if (existing != null) await _removePost(client, messageId);
    return;
  }

  final post = await _postMessage(guildId, message, settings, stars);
  if (existing != null && existing.$1 == settings.channelId) {
    try {
      await starboard.messages.update(
          existing.$2,
          MessageUpdateBuilder(
            content: post.content,
            embeds: post.embeds,
            components: post.components,
            allowedMentions: noMentions,
          ));
      await _savePost(guildId, message, stars, existing.$1, existing.$2);
      return;
    } catch (e) {
      print('Updating a starboard post failed, posting it again: $e');
    }
  } else if (existing != null) {
    await _removePost(client, messageId);
  }

  final sent = await starboard.sendMessage(post);
  await _savePost(guildId, message, stars, settings.channelId, sent.id);
}

void _schedule(NyxxGateway client, Snowflake guildId, Snowflake channelId,
    Snowflake messageId) {
  _pending[messageId]?.cancel();
  _pending[messageId] = Timer(_debounce, () {
    _pending.remove(messageId);
    final previous = _running[messageId] ?? Future<void>.value();
    late final Future<void> current;
    current = previous.then((_) async {
      try {
        await _update(client, guildId, channelId, messageId);
      } catch (e) {
        print('Error in starboard handler: $e');
      }
    }).whenComplete(() {
      if (identical(_running[messageId], current)) _running.remove(messageId);
    });
    _running[messageId] = current;
  });
}

Future<void> _handleReaction(NyxxGateway client, Snowflake? guildId,
    Snowflake channelId, Snowflake messageId, PartialEmoji? emoji) async {
  if (guildId == null) return;
  try {
    final settings = await _settings(guildId);
    if (settings == null || channelId == settings.channelId) return;
    if (emoji != null && !_matches(emoji, settings)) return;
    _schedule(client, guildId, channelId, messageId);
  } catch (e) {
    print('Error in starboard handler: $e');
  }
}

Future<void> _handleDelete(NyxxGateway client, Snowflake? guildId,
    Snowflake channelId, List<Snowflake> messageIds) async {
  if (guildId == null) return;
  try {
    final settings = await _settings(guildId);
    if (settings != null && channelId == settings.channelId) {
      await withConnection((connection) async {
        for (final id in messageIds) {
          await connection.query(
              'DELETE FROM `starboard_posts` WHERE `starboard_message_id` = ?;',
              [id.toString()]);
        }
      });
      return;
    }
    for (final id in messageIds) {
      _pending.remove(id)?.cancel();
      await _removePost(client, id);
    }
  } catch (e) {
    print('Error in starboard delete handler: $e');
  }
}

void setupStarboardHandlers(NyxxGateway client) {
  client.onMessageReactionAdd.listen((event) => _handleReaction(
      client, event.guildId, event.channelId, event.messageId, event.emoji));
  client.onMessageReactionRemove.listen((event) => _handleReaction(
      client, event.guildId, event.channelId, event.messageId, event.emoji));
  client.onMessageReactionRemoveEmoji.listen((event) => _handleReaction(
      client, event.guildId, event.channelId, event.messageId, event.emoji));
  client.onMessageReactionRemoveAll.listen((event) => _handleReaction(
      client, event.guildId, event.channelId, event.messageId, null));
  client.onMessageDelete.listen((event) =>
      _handleDelete(client, event.guildId, event.channelId, [event.id]));
  client.onMessageBulkDelete.listen((event) =>
      _handleDelete(client, event.guildId, event.channelId, event.ids));
}
