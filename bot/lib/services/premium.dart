import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';

bool _isActive(Entitlement entitlement) {
  if (entitlement.isDeleted) return false;
  final now = DateTime.now().toUtc();
  if (entitlement.startsAt != null && entitlement.startsAt!.isAfter(now)) {
    return false;
  }
  if (entitlement.endsAt != null && entitlement.endsAt!.isBefore(now)) {
    return false;
  }
  return true;
}

Future<bool> _hasPremiumGrant(Snowflake userId) async {
  try {
    return await withConnection((connection) async {
      final results = await connection.query(
        'SELECT 1 FROM `premium_grants` '
        "WHERE `type` = 'user' AND `target_id` = ? "
        'AND (`ends_at` IS NULL OR `ends_at` > UTC_TIMESTAMP()) LIMIT 1;',
        [userId.toString()],
      );
      return results.isNotEmpty;
    });
  } catch (e) {
    print('Error in _hasPremiumGrant: $e');
    return false;
  }
}

Future<bool> isUserPremium(Snowflake userId, {CommandContext? context}) async {
  if (premiumUserIds.contains(userId.toString())) return true;
  if (await _hasPremiumGrant(userId)) return true;

  final skus = premiumUserSkus;
  if (skus.isEmpty) return false;

  if (context is InteractionCommandContext && context.user.id == userId) {
    return context.interaction.entitlements.any(
      (entitlement) =>
          entitlement.userId == userId &&
          skus.contains(entitlement.skuId.toString()) &&
          _isActive(entitlement),
    );
  }

  final connection = await getMySqlConnection();
  try {
    final results = await connection.query(
      'SELECT COUNT(*) AS `count` FROM `entitlements` '
      'WHERE `user_id` = ? AND `sku_id` IN (${List.filled(skus.length, '?').join(', ')}) '
      'AND `deleted` = 0 '
      'AND (`starts_at` IS NULL OR `starts_at` <= UTC_TIMESTAMP()) '
      'AND (`ends_at` IS NULL OR `ends_at` > UTC_TIMESTAMP());',
      [userId.toString(), ...skus],
    );
    return (results.first['count'] as int) > 0;
  } catch (e) {
    print('Error in isUserPremium: $e');
    return false;
  } finally {
    await connection.close();
  }
}

Future<void> storeEntitlement(Entitlement entitlement, {bool? deleted}) async {
  final connection = await getMySqlConnection();
  try {
    await connection.query(
      'INSERT INTO `entitlements` '
      '(`id`, `sku_id`, `user_id`, `guild_id`, `type`, `starts_at`, `ends_at`, `deleted`, `consumed`, `updated_at`) '
      'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP()) '
      'ON DUPLICATE KEY UPDATE `sku_id` = VALUES(`sku_id`), `user_id` = VALUES(`user_id`), '
      '`guild_id` = VALUES(`guild_id`), `type` = VALUES(`type`), `starts_at` = VALUES(`starts_at`), '
      '`ends_at` = VALUES(`ends_at`), `deleted` = VALUES(`deleted`), `consumed` = VALUES(`consumed`), '
      '`updated_at` = UTC_TIMESTAMP();',
      [
        entitlement.id.toString(),
        entitlement.skuId.toString(),
        entitlement.userId?.toString(),
        entitlement.guildId?.toString(),
        entitlement.type.value,
        entitlement.startsAt?.toUtc(),
        entitlement.endsAt?.toUtc(),
        (deleted ?? entitlement.isDeleted) ? 1 : 0,
        entitlement.isConsumed ? 1 : 0,
      ],
    );
  } catch (e) {
    print('Error in storeEntitlement: $e');
  } finally {
    await connection.close();
  }
}

Future<void> syncEntitlements(NyxxGateway client) async {
  try {
    Snowflake? after;
    var count = 0;
    while (true) {
      final page = await client.application.entitlements.list(
        after: after,
        limit: 100,
      );
      if (page.isEmpty) break;
      for (final entitlement in page) {
        await storeEntitlement(entitlement);
      }
      count += page.length;
      if (page.length < 100) break;
      after = page
          .map((entitlement) => entitlement.id)
          .reduce((a, b) => a.value > b.value ? a : b);
    }
    print('Synced $count entitlements.');
  } catch (e) {
    print('Error in syncEntitlements: $e');
  }
}

void setupEntitlementHandlers(NyxxGateway client) {
  client.onEntitlementCreate.listen(
    (event) => storeEntitlement(event.entitlement),
  );
  client.onEntitlementUpdate.listen(
    (event) => storeEntitlement(event.entitlement),
  );
  client.onEntitlementDelete.listen(
    (event) => storeEntitlement(event.entitlement, deleted: true),
  );
}
