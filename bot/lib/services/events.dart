import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/commands/configuration/announcements.dart';
import 'package:toxbot/commands/configuration/autorole.dart';
import 'package:toxbot/commands/configuration/color.dart';
import 'package:toxbot/commands/configuration/language.dart';
import 'package:toxbot/commands/developer/system.dart';
import 'package:toxbot/commands/fun/eightball.dart';
import 'package:toxbot/commands/fun/someone.dart';
import 'package:toxbot/commands/general/generate.dart';
import 'package:toxbot/commands/general/info.dart';
import 'package:toxbot/commands/general/invite.dart';
import 'package:toxbot/commands/general/ping.dart';
import 'package:toxbot/commands/general/vote.dart';
import 'package:toxbot/commands/image/awoo.dart';
import 'package:toxbot/commands/image/neko.dart';
import 'package:toxbot/commands/image/waifu.dart';
import 'package:toxbot/commands/levels/levels.dart';
import 'package:toxbot/commands/moderation/clear.dart';
import 'package:toxbot/commands/moderation/mod.dart';
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
import 'package:toxbot/commands/tags/tag.dart';
import 'package:toxbot/commands/tickets/ticket.dart';
import 'package:toxbot/services/blacklist.dart';
import 'package:toxbot/services/levels.dart';
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/services/protection.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const _modalCommands = {'tag create', 'tag edit', 'system eval'};

const _joinColor = '#57f287';

const _leaveColor = '#ed4245';

const _commandColor = '#7289da';

const _removalCheckDelay = Duration(seconds: 3);

const _removalWindow = Duration(seconds: 30);

final Set<Snowflake> currentGuildIds = {};

late DateTime botStartTime;

bool isBotReady = false;

final Map<Snowflake, (String, int)> _knownGuilds = {};

Future<void> _sendLog(NyxxGateway client, String color, String text) async {
  final channel = await client.channels[logChannel].get();
  if (channel is! TextChannel) return;

  await channel.sendMessage(MessageBuilder(
    allowedMentions: noMentions,
    embeds: [
      EmbedBuilder(
        timestamp: DateTime.now().toUtc(),
        color: DiscordColor.parseHexString(color),
        description: text,
      )
    ],
  ));
}

String _userLabel(User? user) {
  if (user == null) return '';
  final name = user.globalName;
  return name == null || name == user.username
      ? user.username
      : '${user.username} ($name)';
}

Future<bool> _removedByModerator(Guild guild, Snowflake userId) async {
  await Future.delayed(_removalCheckDelay);

  try {
    await guild.manager.fetchBan(guild.id, userId);
    return true;
  } catch (_) {}

  try {
    final kicks =
        await guild.auditLogs.list(type: AuditLogEvent.memberKick, limit: 10);
    final since = DateTime.now().subtract(_removalWindow);
    return kicks.any((entry) =>
        entry.targetId == userId && entry.id.timestamp.isAfter(since));
  } catch (e) {
    print('Checking the audit log for a kick failed: $e');
    return false;
  }
}

CommandsPlugin setupCommandHandler(CommandsPlugin commands) {
  commands.check(blacklistCheck);

  commands.addCommand(announcements);
  commands.addCommand(autorole);
  commands.addCommand(color);
  commands.addCommand(language);

  commands.addCommand(system);

  commands.addCommand(eightball);
  commands.addCommand(generate);
  commands.addCommand(someone);

  commands.addCommand(info);
  commands.addCommand(invite);
  commands.addCommand(ping);
  commands.addCommand(vote);

  commands.addCommand(awoo);
  commands.addCommand(neko);
  commands.addCommand(waifu);

  commands.addCommand(background);
  commands.addCommand(bar);
  commands.addCommand(boost);
  commands.addCommand(credits);
  commands.addCommand(daily);
  commands.addCommand(frame);
  commands.addCommand(leaderboard);
  commands.addCommand(pay);
  commands.addCommand(profile);
  commands.addCommand(shop);

  commands.addCommand(clear);
  commands.addCommand(mod);

  commands.addCommand(tag);
  commands.addCommand(ticket);

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
    if (!isBotReady) {
      botStartTime = DateTime.now();
      isBotReady = true;
      syncEntitlements(client);
    }

    print(
        'Client logged in as ${event.user.username} with ${event.guilds.length} guilds on ${event.totalShards} shards.');
    currentGuildIds.addAll(event.guilds.map((guild) => guild.id));

    randomStatus(event);
    clearOldGuilds(client);
  });
}

void setupGuildCreateHandler(NyxxGateway client) async {
  client.onGuildCreate.listen((event) async {
    try {
      if (event is! GuildCreateEvent) return;

      final guild = event.guild;
      if (await leaveIfBlacklisted(client, guild.id)) return;
      _knownGuilds[guild.id] = (guild.name, event.memberCount);
      await checkForDatabase(guild);

      if (currentGuildIds.contains(guild.id)) {
        return;
      }
      currentGuildIds.add(guild.id);

      await _sendLog(client, _joinColor,
          'Joined Server: ${guild.name} which has ${event.memberCount} members.');
    } catch (e) {
      print('Error in setupGuildCreateHandler: $e');
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

      final protection = await protectJoin(client, fullGuild, event.member);
      if (protection.removed) return;

      if (result.isNotEmpty) {
        var guildSettings = result.first;

        if (guildSettings['autorole'] != null && !protection.verifying) {
          try {
            await event.member
                .addRole(Snowflake(int.parse(guildSettings['autorole'])));
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
                    .replaceAll('&user', _userLabel(event.member.user))
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
              if (await _removedByModerator(fullGuild, event.user.id)) return;

              String byeMessage = (guildSettings['bye_msg'] as Blob?)
                      ?.toString()
                      .replaceAll('&user', _userLabel(event.user))
                      .replaceAll('&server', fullGuild.name)
                      .replaceAll('&mention', '<@${event.user.id}>') ??
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
      if (event.isUnavailable) return;

      final id = event.guild.id;
      final known = _knownGuilds.remove(id);
      currentGuildIds.remove(id);

      final name = event.deletedGuild?.name ?? known?.$1 ?? id.toString();
      final members = known == null ? '' : ' which had ${known.$2} members';
      await _sendLog(client, _leaveColor, 'Left Server: $name$members.');
    } catch (e) {
      print('Error in setupGuildDeleteHandler: $e');
    }
  });
}

void setupErrorHandler(CommandsPlugin commands) async {
  commands.onCommandError.listen((error) async {
    if (error is ConverterFailedException) {
      if (error.context case CommandContext context) {
        await replyErrorText(context, codeBlock(error.input.remaining, 'sh'));
        return;
      }
    } else if (error is CheckFailedException) {
      if (error.context case CommandContext context) {
        if (error.failed.name == blacklistCheckName) {
          await replyError(context, 'blacklisted');
          return;
        }
        await replyException(context, error);
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

      final opensModal = context is ChatContext &&
          _modalCommands.contains(context.command.fullName);
      if (context is InteractionInteractiveContext && !opensModal) {
        await (context as InteractionInteractiveContext).acknowledge();
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
    countCommand(client, context);

    try {
      if (context.user.id == ownerId) return;

      final user = context.user;
      final command = context is ChatContext
          ? context.command.fullName
          : context.command.name;
      await _sendLog(client, _commandColor,
          '${user.globalName ?? user.username} (${user.id}) executed ${bold(command)}.');
    } catch (e) {
      print('Error in setupCommandPostCallHandler: $e');
    }
  });
}
