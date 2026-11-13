import 'dart:async';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

const _lockColor = '#5865f2';

const _timeout = Duration(minutes: 10);

const _maxChannels = 25;

const _defaultPermissions = {'send', 'threads', 'thread_messages'};

final _permissions = {
  'send': Permissions.sendMessages.value,
  'threads': Permissions.createPublicThreads.value |
      Permissions.createPrivateThreads.value,
  'thread_messages': Permissions.sendMessagesInThreads.value,
  'reactions': Permissions.addReactions.value,
  'files': Permissions.attachFiles.value,
  'links': Permissions.embedLinks.value,
};

const _channelTypes = [
  ChannelType.guildText,
  ChannelType.guildAnnouncement,
  ChannelType.guildForum,
  ChannelType.guildMedia,
  ChannelType.guildCategory,
];

class _Ids {
  final ComponentId channels;
  final ComponentId permissions;
  final ComponentId lock;
  final ComponentId unlock;

  _Ids(Snowflake user)
      : channels = _id(user),
        permissions = _id(user),
        lock = _id(user),
        unlock = _id(user);

  static ComponentId _id(Snowflake user) =>
      ComponentId.generate(expirationTime: _timeout, allowedUser: user);
}

int _bits(Iterable<String> keys) =>
    keys.fold(0, (bits, key) => bits | (_permissions[key] ?? 0));

Future<List<ActionRowBuilder>> _components(
    User user, _Ids ids, List<Snowflake> channels, Set<String> permissions,
    {bool disabled = false}) async {
  return [
    ActionRowBuilder(components: [
      SelectMenuBuilder.channelSelect(
        customId: ids.channels.toString(),
        placeholder: await getString(user, 'lock_channels_placeholder'),
        defaultValues: [
          for (final id in channels) DefaultValue.channel(id: id),
        ],
        minValues: 1,
        maxValues: _maxChannels,
        isDisabled: disabled,
      )..channelTypes = _channelTypes,
    ]),
    ActionRowBuilder(components: [
      SelectMenuBuilder.stringSelect(
        customId: ids.permissions.toString(),
        placeholder: await getString(user, 'lock_permissions_placeholder'),
        options: [
          for (final key in _permissions.keys)
            SelectMenuOptionBuilder(
              label: await getString(user, 'lock_perm_$key'),
              value: key,
              isDefault: permissions.contains(key),
            ),
        ],
        minValues: 1,
        maxValues: _permissions.length,
        isDisabled: disabled,
      ),
    ]),
    ActionRowBuilder(components: [
      ButtonBuilder.danger(
        customId: ids.lock.toString(),
        label: await getString(user, 'lock_button'),
        isDisabled: disabled,
      ),
      ButtonBuilder.secondary(
        customId: ids.unlock.toString(),
        label: await getString(user, 'unlock_button'),
        isDisabled: disabled,
      ),
    ]),
  ];
}

Future<EmbedBuilder> _intro(User user) async => EmbedBuilder(
      color: DiscordColor.parseHexString(_lockColor),
      title: await getString(user, 'lock_title'),
      description: await getString(user, 'lock_text'),
    );

Future<bool> _apply(NyxxGateway client, Snowflake guildId, Snowflake channelId,
    int bits, bool lock) async {
  try {
    final channel = await client.channels.get(channelId);
    if (channel is! GuildChannel || channel.guildId != guildId) return false;

    final everyone =
        channel.permissionOverwrites.where((o) => o.id == guildId).firstOrNull;
    final allow = everyone?.allow.value ?? 0;
    final deny = everyone?.deny.value ?? 0;
    final newAllow = lock ? allow & ~bits : allow;
    final newDeny = lock ? deny | bits : deny & ~bits;
    if (newAllow == allow && newDeny == deny) return true;

    if (newAllow == 0 && newDeny == 0) {
      await client.channels.deletePermissionOverwrite(channelId, guildId);
    } else {
      await client.channels.updatePermissionOverwrite(
        channelId,
        PermissionOverwriteBuilder(
          id: guildId,
          type: PermissionOverwriteType.role,
          allow: Flags<Permissions>(newAllow),
          deny: Flags<Permissions>(newDeny),
        ),
      );
    }
    return true;
  } catch (e) {
    print('Changing the permissions in $channelId failed: $e');
    return false;
  }
}

Future<EmbedBuilder> _result(User user, List<Snowflake> done,
    List<Snowflake> failed, Set<String> permissions, bool lock) async {
  final names = [
    for (final key in _permissions.keys)
      if (permissions.contains(key)) await getString(user, 'lock_perm_$key'),
  ].join(', ');
  final lines = [
    if (done.isNotEmpty)
      await localized(user, lock ? 'lock_locked' : 'lock_unlocked', {
        '&permissions': names,
        '&channels': done.map((id) => '<#$id>').join(', '),
      }),
    if (failed.isNotEmpty)
      await localized(user, 'lock_failed', {
        '&channels': failed.map((id) => '<#$id>').join(', '),
      }),
  ];
  return EmbedBuilder(
    color:
        DiscordColor.parseHexString(failed.isEmpty ? successColor : errorColor),
    title: await getString(user, 'lock_title'),
    description: lines.join('\n\n'),
  );
}

final lock = ChatCommand(
  'lock',
  'Lock channels for everyone or open them again.',
  localizedDescriptions: {
    Locale.da: 'Lås kanaler for alle eller åbn dem igen.',
    Locale.de: 'Kanäle für alle sperren oder wieder freigeben.',
    Locale.enUs: 'Lock channels for everyone or open them again.',
    Locale.esEs: 'Bloquear canales para todos o volver a abrirlos.',
    Locale.fr: 'Verrouiller des salons pour tout le monde ou les rouvrir.',
    Locale.ru: 'Закрыть каналы для всех или открыть их снова.',
    Locale.hi: 'चैनलों को सबके लिए बंद करें या फिर से खोलें।',
    Locale.zhCn: '为所有人锁定频道或重新开放。',
    Locale.ja: 'チャンネルを全員に対してロックするか、再び開放します。',
    Locale.ko: '모든 사람에게 채널을 잠그거나 다시 엽니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.manageChannels)],
  (
    ChatContext context, [
    @Description('The first channel. You can add more afterwards.', {
      Locale.da: 'Den første kanal. Du kan tilføje flere bagefter.',
      Locale.de: 'Der erste Kanal. Weitere wählst du danach aus.',
      Locale.enUs: 'The first channel. You can add more afterwards.',
      Locale.esEs: 'El primer canal. Después puedes añadir más.',
      Locale.fr: 'Le premier salon. Tu pourras en ajouter d’autres ensuite.',
      Locale.ru: 'Первый канал. Остальные можно выбрать потом.',
      Locale.hi: 'पहला चैनल। बाकी आप बाद में चुन सकते हैं।',
      Locale.zhCn: '第一个频道。之后还可以添加更多。',
      Locale.ja: '最初のチャンネル。あとから追加できます。',
      Locale.ko: '첫 번째 채널. 나중에 더 추가할 수 있습니다.',
    })
    GuildChannel? channel,
  ]) async {
    final user = context.user;
    final guildId = context.guild!.id;
    final ids = _Ids(user.id);
    final start = channel ?? context.channel;
    var channels = <Snowflake>[
      start is Thread ? start.parentId! : start.id,
    ];
    var permissions = {..._defaultPermissions};
    final events = context.commands.eventManager;

    await context.respond(
      MessageBuilder(
        allowedMentions: noMentions,
        embeds: [await _intro(user)],
        components: await _components(user, ids, channels, permissions),
      ),
      level: ResponseLevel.hint,
    );

    final pending = <String, Future<(String, ComponentContext)>>{
      'channels':
          events.nextSelectMenuEvent(ids.channels).then((c) => ('channels', c)),
      'permissions': events
          .nextSelectMenuEvent(ids.permissions)
          .then((c) => ('permissions', c)),
      'lock': events.nextButtonEvent(ids.lock).then((c) => ('lock', c)),
      'unlock': events.nextButtonEvent(ids.unlock).then((c) => ('unlock', c)),
    };

    while (true) {
      final (String, ComponentContext) next;
      try {
        next = await Future.any(pending.values);
      } on TimeoutException {
        break;
      }
      final (key, event) = next;
      final interaction = event.interaction;

      switch (key) {
        case 'channels':
          channels = [
            for (final value in interaction.data.values ?? <String>[])
              Snowflake.parse(value),
          ];
          pending[key] = events
              .nextSelectMenuEvent(ids.channels)
              .then((c) => ('channels', c));
          await interaction.acknowledge(updateMessage: true);
        case 'permissions':
          permissions = {...?interaction.data.values};
          pending[key] = events
              .nextSelectMenuEvent(ids.permissions)
              .then((c) => ('permissions', c));
          await interaction.acknowledge(updateMessage: true);
        default:
          final lockIt = key == 'lock';
          pending[key] = lockIt
              ? events.nextButtonEvent(ids.lock).then((c) => ('lock', c))
              : events.nextButtonEvent(ids.unlock).then((c) => ('unlock', c));
          await interaction.acknowledge(updateMessage: true);

          final bits = _bits(permissions);
          final done = <Snowflake>[];
          final failed = <Snowflake>[];
          for (final id in channels) {
            (await _apply(context.client, guildId, id, bits, lockIt)
                    ? done
                    : failed)
                .add(id);
          }

          await interaction.updateOriginalResponse(MessageUpdateBuilder(
            allowedMentions: noMentions,
            embeds: [await _result(user, done, failed, permissions, lockIt)],
            components: await _components(user, ids, channels, permissions),
          ));
      }
    }

    if (context is! InteractionChatContext) return;
    try {
      await context.interaction.updateOriginalResponse(MessageUpdateBuilder(
        components:
            await _components(user, ids, channels, permissions, disabled: true),
      ));
    } catch (_) {}
  },
);
