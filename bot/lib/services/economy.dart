import 'dart:math';

import 'package:mysql1/mysql1.dart';
import 'package:toxbot/models/economy.dart';

export 'package:toxbot/models/economy.dart';

int xpToNext(int level) => 100 * level + 100;

LevelProgress levelFromXp(int xp) {
  var level = 1;
  var rest = max(0, xp);
  while (rest >= xpToNext(level)) {
    rest -= xpToNext(level);
    level++;
  }
  return LevelProgress(level, rest, xpToNext(level));
}

Future<void> ensureUser(MySqlConnection connection, String userId) async {
  await connection.query(
    'INSERT IGNORE INTO `users` (`id`, `language`, `color`) VALUES (?, ?, ?);',
    [userId, 'en_us', '#7289da'],
  );
}

Future<void> addCredits(
  MySqlConnection connection,
  String userId,
  int amount,
  String reason, {
  String? reference,
}) async {
  await ensureUser(connection, userId);
  await connection.query(
    'INSERT INTO `credit_transactions` (`user`, `amount`, `reason`, `reference`, `created_at`) '
    'VALUES (?, ?, ?, ?, UTC_TIMESTAMP());',
    [userId, amount, reason, reference],
  );
  await connection.query(
    'UPDATE `users` SET `credits` = `credits` + ? WHERE `id` = ?;',
    [amount, userId],
  );
}

Future<int> getCredits(MySqlConnection connection, String userId) async {
  final rows = await connection.query(
    'SELECT `credits` FROM `users` WHERE `id` = ?;',
    [userId],
  );
  return rows.isEmpty ? 0 : rows.first['credits'] as int;
}

Future<bool> hasXpBoost(MySqlConnection connection, String userId) async {
  final rows = await connection.query(
    'SELECT (`xp_boost_until` IS NOT NULL AND `xp_boost_until` > UTC_TIMESTAMP()) AS `boost` '
    'FROM `users` WHERE `id` = ?;',
    [userId],
  );
  return rows.isNotEmpty && (rows.first['boost'] as int? ?? 0) == 1;
}

Future<XpResult> applyXp(
  MySqlConnection connection,
  String userId, {
  String? guildId,
  required int amount,
  required int cooldown,
  required int levelUpReward,
}) async {
  await ensureUser(connection, userId);

  final global = await connection.query(
    'UPDATE `users` SET `xp` = `xp` + ?, `xp_at` = UTC_TIMESTAMP() '
    'WHERE `id` = ? AND (`xp_at` IS NULL OR `xp_at` <= UTC_TIMESTAMP() - INTERVAL ? SECOND);',
    [amount, userId, cooldown],
  );

  final levelUps = <int>[];
  final gotGlobal = (global.affectedRows ?? 0) > 0;
  if (gotGlobal) {
    final rows = await connection.query(
      'SELECT `xp` FROM `users` WHERE `id` = ?;',
      [userId],
    );
    final xp = rows.first['xp'] as int;
    final before = levelFromXp(xp - amount).level;
    final after = levelFromXp(xp).level;
    for (var level = before + 1; level <= after; level++) {
      await addCredits(connection, userId, level * levelUpReward, 'level_up');
      levelUps.add(level);
    }
  }

  if (guildId == null) return XpResult(gotGlobal, levelUps, null);

  final updated = await connection.query(
    'UPDATE `member_levels` SET `xp` = `xp` + ?, `xp_at` = UTC_TIMESTAMP() '
    'WHERE `guild` = ? AND `user` = ? AND (`xp_at` IS NULL OR `xp_at` <= UTC_TIMESTAMP() - INTERVAL ? SECOND);',
    [amount, guildId, userId, cooldown],
  );
  var gotServer = (updated.affectedRows ?? 0) > 0;
  if (!gotServer) {
    final inserted = await connection.query(
      'INSERT IGNORE INTO `member_levels` (`guild`, `user`, `xp`, `xp_at`) VALUES (?, ?, ?, UTC_TIMESTAMP());',
      [guildId, userId, amount],
    );
    gotServer = (inserted.affectedRows ?? 0) > 0;
  }
  if (!gotServer) return XpResult(gotGlobal, levelUps, null);

  final rows = await connection.query(
    'SELECT `xp` FROM `member_levels` WHERE `guild` = ? AND `user` = ?;',
    [guildId, userId],
  );
  final xp = rows.first['xp'] as int;
  final before = levelFromXp(xp - amount).level;
  final after = levelFromXp(xp).level;
  return XpResult(gotGlobal, levelUps, after > before ? after : null);
}

Future<void> countCommand(MySqlConnection connection, String userId) async {
  await ensureUser(connection, userId);
  await connection.query(
    'UPDATE `users` SET `commands_used` = `commands_used` + 1 WHERE `id` = ?;',
    [userId],
  );
}

Future<(bool, String?, bool)> levelUpSettings(
  MySqlConnection connection,
  String guildId,
) async {
  final rows = await connection.query(
    'SELECT `levelup_enabled`, `levelup_channel`, `levelup_dm` FROM `guilds` WHERE `id` = ? LIMIT 1;',
    [guildId],
  );
  if (rows.isEmpty) return (false, null, false);
  return (
    rows.first['levelup_enabled'] == 1,
    rows.first['levelup_channel'] as String?,
    rows.first['levelup_dm'] == 1,
  );
}

Future<DailyResult> claimDaily(
  MySqlConnection connection,
  String userId, {
  required int reward,
  required int streakBonus,
  required bool premium,
}) async {
  await ensureUser(connection, userId);
  final rows = await connection.query(
    'SELECT `daily_streak`, TIMESTAMPDIFF(SECOND, `daily_at`, UTC_TIMESTAMP()) AS `since` '
    'FROM `users` WHERE `id` = ?;',
    [userId],
  );
  final since = rows.first['since'] as int?;
  const day = 24 * 3600;

  if (since != null && since < day) return DailyResult.wait(day - since);

  final streak = since != null && since < 2 * day
      ? (rows.first['daily_streak'] as int) + 1
      : 1;
  final amount =
      reward * (premium ? 2 : 1) + (streak - 1).clamp(0, 10) * streakBonus;

  final updated = await connection.query(
    'UPDATE `users` SET `daily_at` = UTC_TIMESTAMP(), `daily_streak` = ? '
    'WHERE `id` = ? AND (`daily_at` IS NULL OR `daily_at` <= UTC_TIMESTAMP() - INTERVAL 1 DAY);',
    [streak, userId],
  );
  if ((updated.affectedRows ?? 0) == 0) return const DailyResult.wait(60);

  await addCredits(connection, userId, amount, 'daily');
  return DailyResult.claimed(amount, streak);
}

Future<CreditsOverview> creditsOverview(
  MySqlConnection connection,
  String userId,
) async {
  final rows = await connection.query(
    'SELECT `credits`, `daily_streak`, TIMESTAMPDIFF(SECOND, `daily_at`, UTC_TIMESTAMP()) AS `since` '
    'FROM `users` WHERE `id` = ?;',
    [userId],
  );
  final history = await connection.query(
    'SELECT `amount`, `reason`, `created_at` FROM `credit_transactions` '
    'WHERE `user` = ? ORDER BY `id` DESC LIMIT 10;',
    [userId],
  );
  final entries = history.map(CreditEntry.fromRow).toList();
  if (rows.isEmpty) return CreditsOverview(0, 0, null, entries);

  const day = 24 * 3600;
  final since = rows.first['since'] as int?;
  final streak =
      since != null && since < 2 * day ? rows.first['daily_streak'] as int : 0;
  final nextDaily = since != null && since < day
      ? DateTime.now().toUtc().add(Duration(seconds: day - since))
      : null;

  return CreditsOverview(
      rows.first['credits'] as int, streak, nextDaily, entries);
}

Future<List<ShopItem>> shopItems(
  MySqlConnection connection, {
  String? type,
  String? name,
  bool includeRetired = false,
}) async {
  final rows = await connection.query(
    'SELECT `id`, `name`, `type`, `animated`, `price`, `premium_only`, `active` FROM `backgrounds` '
    'WHERE ${includeRetired ? '1 = 1' : '`active` = 1'} ${type != null ? 'AND `type` = ? ' : ''}${name != null ? 'AND `name` = ? ' : ''}'
    'ORDER BY `sort`, `price`, `id`;',
    [if (type != null) type, if (name != null) name],
  );
  return rows.map(ShopItem.fromRow).toList();
}

Future<Set<int>> ownedItems(
  MySqlConnection connection,
  String userId,
) async {
  final rows = await connection.query(
    'SELECT `background_id` FROM `user_backgrounds` WHERE `user` = ?;',
    [userId],
  );
  return rows.map((row) => row['background_id'] as int).toSet();
}

Future<List<(String, String)>> usableItems(
  MySqlConnection connection,
  String userId,
  String search, {
  String? type,
}) async {
  final rows = await connection.query(
    'SELECT `b`.`name`, `b`.`type` FROM `backgrounds` AS `b` '
    'LEFT JOIN `user_backgrounds` AS `ub` ON `ub`.`background_id` = `b`.`id` AND `ub`.`user` = ? '
    'WHERE `b`.`name` LIKE ? ${type != null ? 'AND `b`.`type` = ? ' : ''}'
    'AND (`ub`.`id` IS NOT NULL OR (`b`.`active` = 1 AND `b`.`price` = 0 AND `b`.`premium_only` = 0)) '
    'ORDER BY `b`.`type`, `b`.`sort`, `b`.`id` LIMIT 24;',
    [userId, '%$search%', if (type != null) type],
  );
  return rows
      .map((row) => (row['name'] as String, row['type'] as String))
      .toList();
}

Future<String?> buyOrEquip(
  MySqlConnection connection,
  String userId,
  ShopItem item, {
  required bool premium,
  bool allowBuy = true,
}) async {
  if (item.premiumOnly && !premium) return 'shop_needs_premium';
  await ensureUser(connection, userId);

  final bought = (await ownedItems(connection, userId)).contains(item.id);
  final owned = bought || (item.isFree && item.active);

  if (!owned) {
    if (!item.active) return 'background_not_owned';
    if (!allowBuy) return 'background_not_owned';

    if (item.price > 0) {
      final paid = await connection.query(
        'UPDATE `users` SET `credits` = `credits` - ? WHERE `id` = ? AND `credits` >= ?;',
        [item.price, userId, item.price],
      );
      if ((paid.affectedRows ?? 0) == 0) return 'shop_not_enough';
    }

    final added = await connection.query(
      'INSERT IGNORE INTO `user_backgrounds` (`user`, `background_id`, `created_at`) VALUES (?, ?, UTC_TIMESTAMP());',
      [userId, item.id],
    );
    if ((added.affectedRows ?? 0) == 0) {
      if (item.price > 0) {
        await connection.query(
          'UPDATE `users` SET `credits` = `credits` + ? WHERE `id` = ?;',
          [item.price, userId],
        );
      }
    } else if (item.price > 0) {
      await connection.query(
        'INSERT INTO `credit_transactions` (`user`, `amount`, `reason`, `created_at`) VALUES (?, ?, ?, UTC_TIMESTAMP());',
        [userId, -item.price, 'purchase'],
      );
    }
  }

  if (owned && !bought) {
    await connection.query(
      'INSERT IGNORE INTO `user_backgrounds` (`user`, `background_id`, `created_at`) VALUES (?, ?, UTC_TIMESTAMP());',
      [userId, item.id],
    );
  }

  await connection.query(
    'UPDATE `users` SET `${item.column}` = ? WHERE `id` = ?;',
    [item.id, userId],
  );
  return null;
}

Future<void> unequip(
  MySqlConnection connection,
  String userId,
  String type,
) async {
  await ensureUser(connection, userId);
  await connection.query(
    'UPDATE `users` SET `${shopItemColumn(type)}` = NULL WHERE `id` = ?;',
    [userId],
  );
}

Future<List<(String, int)>> leaderboard(
  MySqlConnection connection, {
  String? guildId,
}) async {
  final rows = guildId == null
      ? await connection.query(
          'SELECT `id` AS `user`, `xp` FROM `users` WHERE `xp` > 0 ORDER BY `xp` DESC, `id` LIMIT 10;',
        )
      : await connection.query(
          'SELECT `user`, `xp` FROM `member_levels` WHERE `guild` = ? AND `xp` > 0 ORDER BY `xp` DESC, `user` LIMIT 10;',
          [guildId],
        );
  return rows.map((row) => (row['user'].toString(), row['xp'] as int)).toList();
}

Future<ProfileStats> profileStats(
  MySqlConnection connection,
  String userId, {
  String? guildId,
}) async {
  final rows = await connection.query(
    'SELECT `xp`, `credits`, `commands_used`, '
    '(SELECT COUNT(*) FROM `users` AS `u` WHERE `u`.`xp` > `users`.`xp`) + 1 AS `rank` '
    'FROM `users` WHERE `id` = ?;',
    [userId],
  );
  int? serverXp;
  if (guildId != null) {
    final member = await connection.query(
      'SELECT `xp` FROM `member_levels` WHERE `guild` = ? AND `user` = ?;',
      [guildId, userId],
    );
    serverXp = member.isEmpty ? 0 : member.first['xp'] as int;
  }
  if (rows.isEmpty) return ProfileStats(0, 0, 0, 1, serverXp);
  final row = rows.first;
  return ProfileStats(
    row['xp'] as int,
    row['credits'] as int,
    row['commands_used'] as int,
    row['rank'] as int,
    serverXp,
  );
}

Future<DateTime> extendXpBoost(
  MySqlConnection connection,
  String userId,
) async {
  await ensureUser(connection, userId);
  await connection.query(
    'UPDATE `users` SET `xp_boost_until` = '
    'GREATEST(COALESCE(`xp_boost_until`, UTC_TIMESTAMP()), UTC_TIMESTAMP()) + INTERVAL 24 HOUR '
    'WHERE `id` = ?;',
    [userId],
  );
  final rows = await connection.query(
    'SELECT `xp_boost_until` FROM `users` WHERE `id` = ?;',
    [userId],
  );
  return rows.first['xp_boost_until'] as DateTime;
}

Future<String?> transferCredits(
  MySqlConnection connection,
  String fromId,
  String toId,
  int amount,
) async {
  await ensureUser(connection, fromId);
  final paid = await connection.query(
    'UPDATE `users` SET `credits` = `credits` - ? WHERE `id` = ? AND `credits` >= ?;',
    [amount, fromId, amount],
  );
  if ((paid.affectedRows ?? 0) == 0) return 'pay_not_enough';

  await ensureUser(connection, toId);
  await connection.query(
    'UPDATE `users` SET `credits` = `credits` + ? WHERE `id` = ?;',
    [amount, toId],
  );
  await connection.query(
    'INSERT INTO `credit_transactions` (`user`, `amount`, `reason`, `reference`, `created_at`) '
    'VALUES (?, ?, ?, NULL, UTC_TIMESTAMP()), (?, ?, ?, NULL, UTC_TIMESTAMP());',
    [fromId, -amount, 'transfer_out', toId, amount, 'transfer_in'],
  );
  return null;
}

Future<int> adjustCredits(
  MySqlConnection connection,
  String userId,
  int amount,
) async {
  await ensureUser(connection, userId);
  final balance = await getCredits(connection, userId);
  final change = amount < 0 ? -min(balance, -amount) : amount;
  if (change != 0) {
    await addCredits(connection, userId, change, 'admin');
  }
  return balance + change;
}

Future<bool> giveItem(
  MySqlConnection connection,
  String userId,
  ShopItem item,
) async {
  await ensureUser(connection, userId);
  final added = await connection.query(
    'INSERT IGNORE INTO `user_backgrounds` (`user`, `background_id`, `created_at`) VALUES (?, ?, UTC_TIMESTAMP());',
    [userId, item.id],
  );
  return (added.affectedRows ?? 0) > 0;
}

Future<bool> takeItem(
  MySqlConnection connection,
  String userId,
  ShopItem item,
) async {
  final removed = await connection.query(
    'DELETE FROM `user_backgrounds` WHERE `user` = ? AND `background_id` = ?;',
    [userId, item.id],
  );
  await connection.query(
    'UPDATE `users` SET `${item.column}` = NULL WHERE `id` = ? AND `${item.column}` = ?;',
    [userId, item.id],
  );
  return (removed.affectedRows ?? 0) > 0;
}

Future<List<(String, String)>> allItems(
  MySqlConnection connection,
  String search,
) async {
  final rows = await connection.query(
    'SELECT `name`, `type` FROM `backgrounds` WHERE `name` LIKE ? '
    'ORDER BY `type`, `sort`, `id` LIMIT 25;',
    ['%$search%'],
  );
  return rows
      .map((row) => (row['name'] as String, row['type'] as String))
      .toList();
}

Future<DateTime?> lastVote(MySqlConnection connection, String userId) async {
  final rows = await connection.query(
    'SELECT `created_at` FROM `credit_transactions` '
    'WHERE `user` = ? AND `reason` = ? ORDER BY `id` DESC LIMIT 1;',
    [userId, 'vote'],
  );
  return rows.isEmpty ? null : rows.first['created_at'] as DateTime?;
}

Future<int?> settleSlots(
  MySqlConnection connection,
  String userId,
  int bet,
  int payout,
) async {
  await ensureUser(connection, userId);
  final paid = await connection.query(
    'UPDATE `users` SET `credits` = `credits` - ? WHERE `id` = ? AND `credits` >= ?;',
    [bet, userId, bet],
  );
  if ((paid.affectedRows ?? 0) == 0) return null;

  final change = payout - bet;
  if (payout > 0) {
    await connection.query(
      'UPDATE `users` SET `credits` = `credits` + ? WHERE `id` = ?;',
      [payout, userId],
    );
  }
  if (change != 0) {
    await connection.query(
      'INSERT INTO `credit_transactions` (`user`, `amount`, `reason`, `created_at`) VALUES (?, ?, ?, UTC_TIMESTAMP());',
      [userId, change, 'slots'],
    );
  }
  return getCredits(connection, userId);
}

Future<bool> slotsAllowed(MySqlConnection connection, String guildId) async {
  final rows = await connection.query(
    'SELECT `slots_enabled` FROM `guilds` WHERE `id` = ? LIMIT 1;',
    [guildId],
  );
  return rows.isEmpty || rows.first['slots_enabled'] != 0;
}
