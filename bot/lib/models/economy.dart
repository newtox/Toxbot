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

class ShopBackground {
  final int id;
  final String name;
  final int price;
  final bool premiumOnly;

  ShopBackground.fromRow(ResultRow row)
      : id = row['id'] as int,
        name = row['name'] as String,
        price = row['price'] as int,
        premiumOnly = row['premium_only'] == 1;

  bool get isFree => price == 0 && !premiumOnly;
}

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
