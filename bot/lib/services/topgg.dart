import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/services/events.dart';
import 'package:toxbot/utils/config.dart';

const _api = 'https://top.gg/api/v1/projects/@me';

const _hiddenCommands = {'system'};

const _discordOnlyFields = {'id', 'application_id', 'guild_id', 'version'};

const _commandsDelay = Duration(minutes: 2);

const _metricsInterval = Duration(minutes: 30);

Timer? _timer;

Future<void> _send(String method, String path, Object body) async {
  final token = topggToken;
  if (token == null) return;

  final request = http.Request(method, Uri.parse('$_api/$path'))
    ..headers.addAll({
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    })
    ..body = jsonEncode(body);
  final response = await http.Response.fromStream(await request.send());
  if (response.statusCode >= 300) {
    print(
        'Top.gg $method $path failed: ${response.statusCode} ${response.body}');
  }
}

Future<void> _postCommands(NyxxGateway client) async {
  try {
    final response = await http.get(
      Uri.parse(
          'https://discord.com/api/v10/applications/${client.user.id}/commands?with_localizations=true'),
      headers: {'Authorization': 'Bot $botToken'},
    );
    if (response.statusCode != 200) {
      print('Loading the commands for Top.gg failed: ${response.statusCode}');
      return;
    }

    final commands = [
      for (final command
          in (jsonDecode(response.body) as List).cast<Map<String, dynamic>>())
        if (!_hiddenCommands.contains(command['name']))
          {
            for (final entry in command.entries)
              if (!_discordOnlyFields.contains(entry.key))
                entry.key: entry.value,
          },
    ];
    await _send('PUT', 'commands', commands);
  } catch (e) {
    print('Posting the commands to Top.gg failed: $e');
  }
}

Future<void> _postMetrics(NyxxGateway client) async {
  try {
    await _send('PATCH', 'metrics', {
      'server_count': currentGuildIds.length,
      'shard_count': client.gateway.totalShards,
    });
  } catch (e) {
    print('Posting the server count to Top.gg failed: $e');
  }
}

void setupTopggSync(NyxxGateway client) {
  client.onReady.listen((_) {
    if (topggToken == null || _timer != null) return;
    Timer(_commandsDelay, () async {
      await _postCommands(client);
      await _postMetrics(client);
    });
    _timer = Timer.periodic(_metricsInterval, (_) => _postMetrics(client));
  });
}
