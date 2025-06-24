import 'package:dotenv/dotenv.dart';
import 'package:toxbot/commands/configuration/announcements.dart';
import 'package:toxbot/commands/configuration/autorole.dart';
import 'package:toxbot/commands/configuration/color.dart';
import 'package:toxbot/commands/configuration/language.dart';
import 'package:toxbot/commands/developer/system.dart';
import 'package:toxbot/commands/fun/eightball.dart';
import 'package:toxbot/commands/fun/someone.dart';
import 'package:toxbot/commands/general/info.dart';
import 'package:toxbot/commands/general/ping.dart';
import 'package:toxbot/commands/image/awoo.dart';
import 'package:toxbot/commands/image/neko.dart';
import 'package:toxbot/commands/image/waifu.dart';
import 'package:toxbot/commands/moderation/clear.dart';
import 'package:toxbot/commands/roleplay/bite.dart';
import 'package:toxbot/commands/roleplay/blush.dart';
import 'package:toxbot/commands/roleplay/bonk.dart';
import 'package:toxbot/commands/roleplay/bully.dart';
import 'package:toxbot/commands/roleplay/cringe.dart';
import 'package:toxbot/commands/roleplay/cry.dart';
import 'package:toxbot/commands/roleplay/cuddle.dart';
import 'package:toxbot/commands/roleplay/dance.dart';
import 'package:toxbot/commands/roleplay/handhold.dart';
import 'package:toxbot/commands/roleplay/happy.dart';
import 'package:toxbot/commands/roleplay/highfive.dart';
import 'package:toxbot/commands/roleplay/hug.dart';
import 'package:toxbot/commands/roleplay/kick.dart';
import 'package:toxbot/commands/roleplay/kill.dart';
import 'package:toxbot/commands/roleplay/kiss.dart';
import 'package:toxbot/commands/roleplay/lick.dart';
import 'package:toxbot/commands/roleplay/nom.dart';
import 'package:toxbot/commands/roleplay/pat.dart';
import 'package:toxbot/commands/roleplay/poke.dart';
import 'package:toxbot/commands/roleplay/slap.dart';
import 'package:toxbot/commands/roleplay/smile.dart';
import 'package:toxbot/commands/roleplay/smug.dart';
import 'package:toxbot/commands/roleplay/wave.dart';
import 'package:toxbot/commands/roleplay/wink.dart';
import 'package:toxbot/commands/roleplay/yeet.dart';
import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

var env = DotEnv(includePlatformEnvironment: true)..load();

final Set<Snowflake> currentGuildIds = {};

CommandsPlugin setupCommandHandler(CommandsPlugin commands) {
  // Configuration commands
  commands.addCommand(announcements);
  commands.addCommand(autorole);
  commands.addCommand(color);
  commands.addCommand(language);

  // Developer commands
  commands.addCommand(system);

  // Fun commands
  commands.addCommand(eightball);
  commands.addCommand(someone);

  // General commands
  commands.addCommand(info);
  commands.addCommand(ping);

  // Image commands
  commands.addCommand(awoo);
  commands.addCommand(neko);
  commands.addCommand(waifu);

  // Moderation commands
  commands.addCommand(clear);

  // Roleplay commands
  commands.addCommand(bite);
  commands.addCommand(blush);
  commands.addCommand(bonk);
  commands.addCommand(bully);
  commands.addCommand(cringe);
  commands.addCommand(cry);
  commands.addCommand(cuddle);
  commands.addCommand(dance);
  commands.addCommand(handhold);
  commands.addCommand(happy);
  commands.addCommand(highfive);
  commands.addCommand(hug);
  commands.addCommand(kick);
  commands.addCommand(kill);
  commands.addCommand(kiss);
  commands.addCommand(lick);
  commands.addCommand(nom);
  commands.addCommand(pat);
  commands.addCommand(poke);
  commands.addCommand(slap);
  commands.addCommand(smile);
  commands.addCommand(smug);
  commands.addCommand(wave);
  commands.addCommand(wink);
  commands.addCommand(yeet);

  return commands;
}

void setupReadyHandler(NyxxGateway client) async {
  client.onReady.listen((event) async {
    print(
        'Client logged in as ${event.user.username} with ${event.guilds.length} guilds on ${event.totalShards} shards.');
    currentGuildIds.addAll(event.guilds.map((guild) => guild.id));

    randomStatus(event);
    clearOldGuilds(client);
    clearOldUsers(client);
  });
}

void setupGuildCreateHandler(NyxxGateway client) async {
  client.onGuildCreate.listen((event) async {
    try {
      final fullGuild = await client.guilds.get(event.guild.id);
      await checkForDatabase(fullGuild);

      if (currentGuildIds.contains(event.guild.id)) {
        return;
      }

      final channelId = Snowflake(1288846653704376462);
      final channel = await client.channels[channelId].get();

      if (channel is TextChannel) {
        await channel.sendMessage(MessageBuilder(embeds: [
          EmbedBuilder(
              timestamp: DateTime.now().toUtc(),
              color: DiscordColor.parseHexString('#57f287'),
              description:
                  'Joined Server: ${fullGuild.name} which has ${fullGuild.members.cache.length} members.')
        ]));
        return;
      }
    } catch (e) {
      print('Error in setupGuildCreateHandler: $e');
      return;
    }
  });
}

void setupGuildMemberAddHandler(NyxxGateway client) async {
  client.onGuildMemberAdd.listen((event) async {
    final connection = await getMySqlConnection();

    try {
      final fullGuild = await client.guilds.get(event.guild.id);

      final result = await connection.query(
          'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
          [fullGuild.id.toString()]);

      if (result.isNotEmpty) {
        var guildSettings = result.first;

        if (guildSettings['autorole'] != null) {
          try {
            await event.member.addRole(Snowflake(guildSettings['autorole']));
          } catch (e) {
            print('Failed to add autorole: $e');
            return;
          }
        }

        if (guildSettings['welcome_msg'] != null &&
            guildSettings['welcome_channel'] != null) {
          final welcomeChannelId =
              Snowflake(int.parse(guildSettings['welcome_channel']));
          final welcomeChannel = await client.channels[welcomeChannelId].get();

          if (welcomeChannel is TextChannel) {
            String welcomeMessage = (guildSettings['welcome_msg'] as Blob?)
                    ?.toString()
                    .replaceAll('&user',
                        '${event.member.user?.username} (${event.member.user?.globalName})')
                    .replaceAll('&server', fullGuild.name)
                    .replaceAll('&mention', "<@${event.member.id}>") ??
                'Default message if welcome_msg is null';

            try {
              await welcomeChannel
                  .sendMessage(MessageBuilder(content: welcomeMessage));
              return;
            } catch (e) {
              print('Failed to send welcome message: $e');
              return;
            }
          } else {
            print(
                'Welcome channel not found or not a text channel: $welcomeChannelId');
            return;
          }
        }
      }
    } catch (e) {
      print('Error in setupGuildMemberAddHandler: $e');
      return;
    } finally {
      await connection.close();
    }
  });
}

void setupGuildMemberRemoveHandler(NyxxGateway client) async {
  client.onGuildMemberRemove.listen((event) async {
    final connection = await getMySqlConnection();

    try {
      if (client.guilds.cache.containsKey(event.guild.id)) {
        final fullGuild = await client.guilds.get(event.guild.id);

        final result = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [fullGuild.id.toString()]);

        if (result.isNotEmpty) {
          var guildSettings = result.first;

          if (guildSettings['bye_msg'] != null &&
              guildSettings['bye_channel'] != null) {
            final byeChannelId =
                Snowflake(int.parse(guildSettings['bye_channel']));
            final byeChannel = await client.channels[byeChannelId].get();

            if (byeChannel is TextChannel) {
              String byeMessage = (guildSettings['bye_msg'] as Blob?)
                      ?.toString()
                      .replaceAll('&user',
                          '${event.removedMember?.user?.username} (${event.removedMember?.user?.globalName})')
                      .replaceAll('&server', fullGuild.name)
                      .replaceAll(
                          '&mention', "<@${event.removedMember?.id}>") ??
                  'Default message if bye_msg is null';

              try {
                await byeChannel
                    .sendMessage(MessageBuilder(content: byeMessage));
                return;
              } catch (e) {
                print('Failed to send message: $e');
                return;
              }
            } else {
              print('Channel not found or not a text channel: $byeChannelId');
              return;
            }
          }
        }
      }
    } catch (e) {
      print('Error in setupGuildMemberRemoveHandler: $e');
      return;
    } finally {
      await connection.close();
    }
  });
}

void setupGuildDeleteHandler(NyxxGateway client) async {
  client.onGuildDelete.listen((event) async {
    try {
      if (event.isUnavailable) {
        return;
      }

      if (client.guilds.cache.containsKey(event.guild.id)) {
        if (event.deletedGuild != null) {
          final fullGuild = await client.guilds.get(event.guild.id);
          await checkForDatabase(fullGuild);

          final channelId = Snowflake(1288846653704376462);
          final channel = await client.channels[channelId].get();

          if (channel is TextChannel) {
            await channel.sendMessage(MessageBuilder(embeds: [
              EmbedBuilder(
                  timestamp: DateTime.now().toUtc(),
                  color: DiscordColor.parseHexString('#ed4245'),
                  description:
                      'Left Server: ${fullGuild.name} which had ${fullGuild.members.cache.length} members.')
            ]));
          }
        } else {
          return;
        }
      }
    } catch (e) {
      print('Error in setupGuildDeleteHandler: $e');
      return;
    }
  });
}

void setupErrorHandler(CommandsPlugin commands) async {
  commands.onCommandError.listen((error) async {
    if (error is ConverterFailedException) {
      if (error.context case CommandContext context) {
        await context.respond(
            MessageBuilder(embeds: [
              EmbedBuilder(
                color: DiscordColor.parseHexString('#c41111'),
                title: await getString(context.user, 'global_error'),
                description: codeBlock(error.input.remaining, 'sh'),
              )
            ]),
            level: ResponseLevel.hint);
        return;
      }
    } else if (error is CheckFailedException) {
      if (error.context case CommandContext context) {
        await context.respond(
            MessageBuilder(embeds: [
              EmbedBuilder(
                color: DiscordColor.parseHexString('#c41111'),
                title: await getString(context.user, 'global_error'),
                description: codeBlock(error.toString(), 'sh'),
              )
            ]),
            level: ResponseLevel.hint);
        return;
      }
    } else {
      print('Uncaught error: $error');
      return;
    }
  });
}

void setupCommandPreCallHandler(
    CommandsPlugin commands, NyxxGateway client) async {
  commands.onPreCall.listen((context) async {
    try {
      if (context.channel.type == ChannelType.guildText) {
        final fullGuild = await client.guilds.get(context.guild!.id);
        await checkForDatabase(fullGuild);
      }
    } catch (e) {
      print('Error in setupCommandPreCallHandler: $e');
      return;
    }
  });
}

void setupCommandPostCallHandler(
    CommandsPlugin commands, NyxxGateway client) async {
  commands.onPostCall.listen((context) async {
    try {
      final channelId = Snowflake(1288846653704376462);
      final channel = await client.channels[channelId].get();

      if (channel is TextChannel) {
        if (context.user.id != Snowflake(402483602094555138)) {
          await channel.sendMessage(MessageBuilder(embeds: [
            EmbedBuilder(
                timestamp: DateTime.now().toUtc(),
                color: DiscordColor.parseHexString('#7289da'),
                description:
                    '${context.user.globalName} (${context.user.id}) executed ${bold(context.command.name)}.')
          ]));
          return;
        }
      }
    } catch (e) {
      print('Error in setupCommandPostCallHandler: $e');
      return;
    }
  });
}

void setupButtonInteractionHandler(NyxxGateway client) async {
  client.onMessageComponentInteraction.listen((context) async {
    try {
      final customId = context.interaction.data.customId;

      if (customId.startsWith('role_select:')) {}

      if (customId == 'create_ticket') {}

      if (customId == 'close_ticket') {}
    } catch (e) {
      print('Error in setupButtonInteractionHandler: $e');
      return;
    }
  });
}
