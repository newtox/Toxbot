import 'dart:math';

import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';

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
  final (enabled, configured) = await economy.levelUpSettings(
    connection,
    guildId.toString(),
  );
  if (!enabled) return;

  final target =
      configured != null ? Snowflake(int.parse(configured)) : channelId;
  if (target == null) return;

  try {
    final channel = await client.channels[target].get();
    if (channel is! TextChannel) return;

    final text = (await getString(user, 'level_up'))
        .replaceAll('&mention', '<@${user.id}>')
        .replaceAll('&level', formatNumber(level));
    await channel.sendMessage(
      MessageBuilder(
        content: text,
        allowedMentions: AllowedMentions(users: [user.id]),
      ),
    );
  } catch (e) {
    print('Error announcing level-up: $e');
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
  CommandContext? context,
}) async {
  if (user.isBot) return;

  try {
    final premium = await isUserPremium(user.id, context: context);

    await withConnection((connection) async {
      final userId = user.id.toString();
      final boosted = await economy.hasXpBoost(connection, userId);
      final base = xpMin + _random.nextInt(max(1, xpMax - xpMin + 1));
      final amount = base * (premium ? 2 : 1) + (boosted ? base : 0);

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
    context: context,
  );
}

void setupMessageXpHandler(NyxxGateway client) {
  client.onMessageCreate.listen((event) async {
    final author = event.message.author;
    if (author is! User || author.isBot || event.guildId == null) return;

    await giveXp(
      client,
      user: author,
      guildId: event.guildId,
      channelId: event.message.channelId,
    );
  });
}
