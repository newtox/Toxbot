import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/events.dart';
import 'package:toxbot/services/levels.dart';
import 'package:toxbot/services/live_role.dart';
import 'package:toxbot/services/moderation.dart';
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/services/protection.dart';
import 'package:toxbot/services/starboard.dart';
import 'package:toxbot/services/status.dart';
import 'package:toxbot/services/tickets.dart';
import 'package:toxbot/utils/config.dart';

void main() async {
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
  setupStarboardHandlers(client);
  setupStatusHeartbeat(client);
}
