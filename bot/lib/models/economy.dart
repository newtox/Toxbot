import 'package:mysql1/mysql1.dart';

class LevelProgress {
  final int level;
  final int current;
  final int needed;

  const LevelProgress(this.level, this.current, this.needed);
}

class XpResult {
  final bool global;

  final List<int> globalLevelUps;

  final int? serverLevelUp;

  const XpResult(this.global, this.globalLevelUps, this.serverLevelUp);
}

class DailyResult {
  final bool claimed;

  final int waitSeconds;
  final int amount;
  final int streak;

  const DailyResult.claimed(this.amount, this.streak)
      : claimed = true,
        waitSeconds = 0;

  const DailyResult.wait(this.waitSeconds)
      : claimed = false,
        amount = 0,
        streak = 0;
}

const shopItemTypes = ['background', 'frame', 'bar'];

class ShopItem {
  final int id;
  final String name;
  final String type;
  final bool animated;
  final int price;
  final bool premiumOnly;
  final bool active;

  ShopItem.fromRow(ResultRow row)
      : id = row['id'] as int,
        name = row['name'] as String,
        type = row['type'] as String? ?? 'background',
        animated = row['animated'] == 1,
        price = row['price'] as int,
        premiumOnly = row['premium_only'] == 1,
        active = row['active'] != 0;

  bool get isFree => price == 0 && !premiumOnly;

  String get column => shopItemColumn(type);
}

String shopItemColumn(String type) => switch (type) {
      'frame' => 'frame_id',
      'bar' => 'bar_id',
      _ => 'background_id',
    };

class ProfileStats {
  final int xp;
  final int credits;
  final int commands;
  final int rank;
  final int? serverXp;

  const ProfileStats(
    this.xp,
    this.credits,
    this.commands,
    this.rank,
    this.serverXp,
  );
}

class CreditEntry {
  final int amount;
  final String reason;
  final DateTime? createdAt;

  CreditEntry.fromRow(ResultRow row)
      : amount = row['amount'] as int,
        reason = row['reason'] as String,
        createdAt = (row['created_at'] as DateTime?)?.toUtc();
}

class CreditsOverview {
  final int credits;
  final int streak;
  final DateTime? nextDaily;
  final List<CreditEntry> entries;

  const CreditsOverview(
      this.credits, this.streak, this.nextDaily, this.entries);
}
