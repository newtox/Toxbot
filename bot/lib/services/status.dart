import 'dart:async';
import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:toxbot/services/events.dart';
import 'package:toxbot/utils/database.dart';

const _interval = Duration(minutes: 1);

Timer? _timer;

String? _environment(String key) {
  final value = Platform.environment[key]?.trim();
  return value == null || value.isEmpty ? null : value;
}

Future<int?> _latencyMs(NyxxGateway client) async {
  final gateway = client.gateway.latency.inMilliseconds;
  if (gateway > 0) return gateway;

  try {
    final stopwatch = Stopwatch()..start();
    await client.users.fetchCurrentUser();
    return stopwatch.elapsedMilliseconds;
  } catch (_) {
    return null;
  }
}

Future<void> _writeStatus(NyxxGateway client) async {
  try {
    final latency = await _latencyMs(client);
    await withConnection((connection) => connection.query(
          'INSERT INTO `bot_status` (`id`, `started_at`, `heartbeat_at`, `commit`, `build_date`, `dart_version`, `guilds`, `latency_ms`, `memory_bytes`, `user_id`) '
          'VALUES (1, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?) '
          'ON DUPLICATE KEY UPDATE `started_at` = VALUES(`started_at`), `heartbeat_at` = VALUES(`heartbeat_at`), `commit` = VALUES(`commit`), `build_date` = VALUES(`build_date`), '
          '`dart_version` = VALUES(`dart_version`), `guilds` = VALUES(`guilds`), `latency_ms` = VALUES(`latency_ms`), `memory_bytes` = VALUES(`memory_bytes`), `user_id` = VALUES(`user_id`);',
          [
            botStartTime.toUtc(),
            _environment('GIT_COMMIT'),
            _environment('BUILD_DATE'),
            Platform.version.split(' ').first,
            currentGuildIds.length,
            latency,
            ProcessInfo.currentRss,
            client.user.id.toString(),
          ],
        ));
  } catch (e) {
    print('Writing the bot status failed: $e');
  }
}

void setupStatusHeartbeat(NyxxGateway client) {
  client.onReady.listen((_) {
    if (_timer != null) return;
    Timer(const Duration(seconds: 10), () => _writeStatus(client));
    _timer = Timer.periodic(_interval, (_) => _writeStatus(client));
  });
}
