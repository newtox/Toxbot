import 'dart:math';

import 'package:http/http.dart' as http;
import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/blacklist.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

export 'package:toxbot/services/economy.dart' show levelFromXp, LevelProgress;

final _random = Random();

Future<void> _announceLevelUp(
  NyxxGateway client,
  MySqlConnection connection,
  User user,
  Snowflake guildId,
  Snowflake? channelId,
  int level,
) async {
  final (enabled, configured, dm) = await economy.levelUpSettings(
    connection,
    guildId.toString(),
  );

  final target =
      configured != null ? Snowflake(int.parse(configured)) : channelId;
  if (enabled && target != null) {
    try {
      final channel = await client.channels[target].get();
      if (channel is TextChannel) {
        final text = (await getString(user, 'level_up'))
            .replaceAll('&mention', '<@${user.id}>')
            .replaceAll('&level', formatNumber(level));
        await channel.sendMessage(
          MessageBuilder(
            content: text,
            allowedMentions: AllowedMentions(users: [user.id]),
          ),
        );
      }
    } catch (e) {
      print('Error announcing level-up: $e');
    }
  }

  if (dm) {
    try {
      final guild = await client.guilds.get(guildId);
      final text = (await getString(user, 'level_up_dm'))
          .replaceAll('&server', guild.name)
          .replaceAll('&level', formatNumber(level));
      final channel = await client.users.createDm(user.id);
      await channel.sendMessage(
        MessageBuilder(content: text, allowedMentions: noMentions),
      );
    } catch (e) {
      print('Error sending the level-up DM: $e');
    }
  }
}

Future<Iterable<CommandOptionChoiceBuilder<String>>> _ownedItemChoices(
  AutocompleteContext context,
  String type,
) async {
  try {
    final user = context.user;
    final search = context.currentValue.toLowerCase();
    final items = await withConnection(
      (connection) => economy.usableItems(
        connection,
        user.id.toString(),
        context.currentValue,
        type: type,
      ),
    );
    final label = await getString(user, 'shop_default_$type');
    return [
      ...items.map(
        (item) => CommandOptionChoiceBuilder(name: item.$1, value: item.$1),
      ),
      if (search.isEmpty || label.toLowerCase().contains(search))
        CommandOptionChoiceBuilder(name: label, value: 'default:$type'),
    ].take(25);
  } catch (e) {
    print('Error in item autocomplete: $e');
    return const [];
  }
}

String formatNumber(int value) {
  final digits = value.abs().toString();
  final buffer = StringBuffer(value < 0 ? '-' : '');
  for (var i = 0; i < digits.length; i++) {
    if (i > 0 && (digits.length - i) % 3 == 0) buffer.write(' ');
    buffer.write(digits[i]);
  }
  return buffer.toString();
}

String formatCredits(int value) => '${formatNumber(value)} $currency';

Future<void> giveXp(
  NyxxGateway client, {
  required User user,
  Snowflake? guildId,
  Snowflake? channelId,
}) async {
  if (user.isBot) return;

  try {
    await withConnection((connection) async {
      final userId = user.id.toString();
      final amount = xpMin + _random.nextInt(max(1, xpMax - xpMin + 1));

      final result = await economy.applyXp(
        connection,
        userId,
        guildId: guildId?.toString(),
        amount: amount,
        cooldown: xpCooldown,
        levelUpReward: levelUpReward,
      );

      if (result.serverLevelUp != null && guildId != null) {
        await _announceLevelUp(
          client,
          connection,
          user,
          guildId,
          channelId,
          result.serverLevelUp!,
        );
      }
    });
  } catch (e) {
    print('Error in giveXp: $e');
  }
}

Future<void> countCommand(NyxxGateway client, CommandContext context) async {
  if (context.user.isBot) return;
  try {
    await withConnection(
      (connection) =>
          economy.countCommand(connection, context.user.id.toString()),
    );
  } catch (e) {
    print('Error in countCommand: $e');
  }

  await giveXp(
    client,
    user: context.user,
    guildId: context.guild?.id,
    channelId: context.channel.id,
  );
}

Future<({List<int> data, String fileName})?> fetchProfileCard(
  Snowflake userId, {
  Snowflake? guildId,
  economy.ShopItem? preview,
}) async {
  final base = dashboardUrl;
  if (base == null) return null;
  try {
    final uri = Uri.parse('$base/card/$userId.png').replace(
      queryParameters: {
        if (guildId != null) 'guild': guildId.toString(),
        if (preview != null)
          (preview.type == 'background' ? 'bg' : preview.type):
              preview.id.toString(),
      },
    );
    final response = await http.get(uri).timeout(const Duration(seconds: 25));
    if (response.statusCode != 200) {
      print('Profile card: dashboard answered ${response.statusCode}');
      return null;
    }
    final gif =
        response.headers['content-type']?.startsWith('image/gif') ?? false;
    return (data: response.bodyBytes, fileName: gif ? 'card.gif' : 'card.png');
  } catch (e) {
    print('Profile card could not be loaded: $e');
    return null;
  }
}

Future<Iterable<CommandOptionChoiceBuilder<String>>> backgroundChoices(
  AutocompleteContext context,
) =>
    _ownedItemChoices(context, 'background');

Future<Iterable<CommandOptionChoiceBuilder<String>>> frameChoices(
  AutocompleteContext context,
) =>
    _ownedItemChoices(context, 'frame');

Future<Iterable<CommandOptionChoiceBuilder<String>>> barChoices(
  AutocompleteContext context,
) =>
    _ownedItemChoices(context, 'bar');

Future<void> equipItem(ChatContext context, String type, String name) async {
  final user = context.user;
  try {
    if (name == 'default:$type') {
      await withConnection(
        (connection) => economy.unequip(connection, user.id.toString(), type),
      );
      await replySuccess(context, 'shop_equipped',
          replace: {'&name': await getString(user, 'shop_default_$type')},
          level: ResponseLevel.hint);
      return;
    }

    final error = await withConnection((connection) async {
      final found = await economy.shopItems(
        connection,
        type: type,
        name: name,
        includeRetired: true,
      );
      if (found.isEmpty) return 'background_not_found';
      return economy.buyOrEquip(
        connection,
        user.id.toString(),
        found.first,
        allowBuy: false,
      );
    });

    if (error != null) {
      await replyError(context, error, replace: {'&name': name});
      return;
    }
    await replySuccess(context, 'shop_equipped',
        replace: {'&name': name}, level: ResponseLevel.hint);
  } catch (e) {
    await replyCommandError(context, 'levels_error', type, e);
  }
}

Future<Iterable<CommandOptionChoiceBuilder<String>>> allItemChoices(
  AutocompleteContext context,
) async {
  try {
    final items = await withConnection(
      (connection) => economy.allItems(connection, context.currentValue),
    );
    final kinds = {
      for (final type in economy.shopItemTypes)
        type: await getString(context.user, 'shop_kind_$type'),
    };
    return items.map(
      (item) => CommandOptionChoiceBuilder(
        name: '${item.$1} · ${kinds[item.$2] ?? item.$2}',
        value: item.$1,
      ),
    );
  } catch (e) {
    print('Error in item autocomplete: $e');
    return const [];
  }
}

void setupMessageXpHandler(NyxxGateway client) {
  client.onMessageCreate.listen((event) async {
    final author = event.message.author;
    if (author is! User || author.isBot || event.guildId == null) return;
    if (await isUserBlacklisted(author.id)) return;

    await giveXp(
      client,
      user: author,
      guildId: event.guildId,
      channelId: event.message.channelId,
    );
  });
}
