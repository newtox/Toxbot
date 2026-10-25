import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/models/tag.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';

final tagNamePattern = RegExp(r'^[a-z0-9_-]{1,32}$');

final _invitePattern = RegExp(
  r'(discord\.gg|discord(app)?\.com/invite|dsc\.gg)/[A-Za-z0-9-]+',
  caseSensitive: false,
);

final _massMentionPattern = RegExp(r'@(everyone|here)|<@[!&]?\d+>|&mention');

Future<Iterable<CommandOptionChoiceBuilder<String>>> _autocomplete(
  AutocompleteContext context, {
  required bool ownOnly,
}) async {
  try {
    final query = '${context.currentValue.toLowerCase()}%';
    return await withConnection((connection) async {
      final results = await connection.query(
        ownOnly
            ? 'SELECT `name` FROM `tags` WHERE `owner` = ? AND `name` LIKE ? ORDER BY `uses` DESC LIMIT 25;'
            : 'SELECT `name` FROM `tags` WHERE `name` LIKE ? ORDER BY `uses` DESC LIMIT 25;',
        [if (ownOnly) context.user.id.toString(), query],
      );
      return results.map(
        (row) => CommandOptionChoiceBuilder(
          name: row['name'] as String,
          value: row['name'] as String,
        ),
      );
    });
  } catch (e) {
    print('Error in tag autocomplete: $e');
    return const [];
  }
}

Future<Tag?> findTag(String name) => withConnection((connection) async {
      final results = await connection.query(
        'SELECT * FROM `tags` WHERE `name` = ? LIMIT 1;',
        [name.toLowerCase()],
      );
      return results.isEmpty ? null : Tag.fromRow(results.first);
    });

Future<int> countTags(Snowflake owner) => withConnection((connection) async {
      final results = await connection.query(
        'SELECT COUNT(*) AS `count` FROM `tags` WHERE `owner` = ?;',
        [owner.toString()],
      );
      return results.first['count'] as int;
    });

Future<bool> isTagAllowedInGuild(Tag tag, Snowflake? guild) async {
  if (guild == null) return true;
  return withConnection((connection) async {
    final settings = await connection.query(
      'SELECT `tags_enabled` FROM `guilds` WHERE `id` = ? LIMIT 1;',
      [guild.toString()],
    );
    if (settings.isNotEmpty && settings.first['tags_enabled'] == 0) {
      return false;
    }
    final blocked = await connection.query(
      'SELECT 1 FROM `tag_guild_blocks` WHERE `guild` = ? AND `tag_id` = ? LIMIT 1;',
      [guild.toString(), tag.id],
    );
    return blocked.isEmpty;
  });
}

String? tagContentProblem(String content) {
  if (content.trim().isEmpty) return 'tag_content_empty';
  if (_invitePattern.hasMatch(content)) return 'tag_content_invite';
  if (_massMentionPattern.hasMatch(content)) return 'tag_content_mention';
  return null;
}

bool isTagOwnerOrBotOwner(Tag tag, User user) =>
    tag.owner == user.id || user.id == ownerId;

Future<String> fillTagPlaceholders(String content, ChatContext context) async {
  var result = content
      .replaceAll('&mention', context.user.globalName ?? context.user.username)
      .replaceAll('&user', context.user.globalName ?? context.user.username);

  if (context.guild != null &&
      (result.contains('&server') || result.contains('&membercount'))) {
    final guild = await context.client.guilds.fetch(
      context.guild!.id,
      withCounts: true,
    );
    result = result.replaceAll('&server', guild.name).replaceAll(
          '&membercount',
          (guild.approximateMemberCount ?? 0).toString(),
        );
  } else {
    result = result.replaceAll('&server', '').replaceAll('&membercount', '');
  }
  return result;
}

Future<Iterable<CommandOptionChoiceBuilder<String>>> allTags(
  AutocompleteContext context,
) =>
    _autocomplete(context, ownOnly: false);

Future<Iterable<CommandOptionChoiceBuilder<String>>> ownTags(
  AutocompleteContext context,
) =>
    _autocomplete(context, ownOnly: true);
