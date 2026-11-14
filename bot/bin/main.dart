import 'dart:async';
import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:shiori/services/events.dart';
import 'package:shiori/services/guild_events.dart';
import 'package:shiori/services/levels.dart';
import 'package:shiori/services/live_role.dart';
import 'package:shiori/services/moderation.dart';
import 'package:shiori/services/premium.dart';
import 'package:shiori/services/protection.dart';
import 'package:shiori/services/starboard.dart';
import 'package:shiori/services/status.dart';
import 'package:shiori/services/tickets.dart';
import 'package:shiori/services/topgg.dart';
import 'package:shiori/utils/config.dart';

var _connected = false;

Future<void> _start() async {
  final commands = CommandsPlugin(
      prefix: mentionOr((_) => '..'),
      options: CommandsOptions(
          acceptBotCommands: false,
          acceptSelfCommands: false,
          defaultResponseLevel: ResponseLevel.public,
          type: CommandType.slashOnly));

  final client = await Nyxx.connectGateway(botToken, GatewayIntents.all,
      options: GatewayClientOptions(plugins: [
        Logging(
            stderrLevel: Level.SEVERE,
            stackTraceLevel: Level.SEVERE,
            logLevel: Level.INFO,
            censorToken: true,
            stdout: stdout,
            stderr: stderr),
        cliIntegration,
        setupCommandHandler(commands)
      ]));

  _connected = true;
  setupReadyHandler(client);
  setupGuildCreateHandler(client);
  setupGuildMemberAddHandler(client);
  setupGuildMemberRemoveHandler(client);
  setupGuildDeleteHandler(client);
  setupErrorHandler(commands);
  setupCommandPreCallHandler(commands, client);
  setupCommandPostCallHandler(commands, client);
  setupEntitlementHandlers(client);
  setupMessageXpHandler(client);
  setupLiveRoleHandler(client);
  setupModerationHandlers(client);
  setupProtectionHandlers(client);
  setupTicketHandlers(client);
  setupGuildEventHandlers(client);
  setupStarboardHandlers(client);
  setupStatusHeartbeat(client);
  setupTopggSync(client);
}

void main() {
  runZonedGuarded(_start, (error, stack) async {
    if (error is OutOfRemainingSessionsError) {
      final wait = error.gatewayBot.sessionStartLimit.resetAfter;
      stderr.writeln(
          'Discord allows no more session starts right now, retrying in ${wait.inMinutes} minutes.');
      await Future.delayed(wait + const Duration(minutes: 1));
      exit(1);
    }
    stderr.writeln('Uncaught error: $error\n$stack');
    if (!_connected) exit(1);
  });
}
