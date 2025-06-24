import 'dart:io';

import 'package:dotenv/dotenv.dart';
import 'package:toxbot/events.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';

void main() async {
  var env = DotEnv(includePlatformEnvironment: true)..load();

  final commands = CommandsPlugin(
      prefix: mentionOr((_) => '..'),
      options: CommandsOptions(
          acceptBotCommands: false,
          acceptSelfCommands: false,
          defaultResponseLevel: ResponseLevel.public,
          type: CommandType.slashOnly));

  final client =
      await Nyxx.connectGateway(env['token']!, GatewayIntents.allUnprivileged,
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
}
