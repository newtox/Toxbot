import 'package:nyxx/nyxx.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/utils/database.dart';

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
    final userId = entitlement.userId;
    if (userId != null && !(deleted ?? entitlement.isDeleted)) {
      await economy.grantPurchasedItems(
        connection,
        userId.toString(),
        [entitlement.skuId.toString()],
      );
    }
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
