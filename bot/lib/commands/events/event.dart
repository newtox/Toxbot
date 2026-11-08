import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/guild_events.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

const _eventDescription = {
  Locale.da: 'Eventet.',
  Locale.de: 'Das Event.',
  Locale.enUs: 'The event.',
  Locale.esEs: 'El evento.',
  Locale.fr: 'L’événement.',
  Locale.ru: 'Событие.',
  Locale.hi: 'इवेंट।',
  Locale.zhCn: '活动。',
  Locale.ja: 'イベント。',
  Locale.ko: '이벤트.',
};

Future<GuildEvent?> _event(ChatContext context, String value) async {
  final id = int.tryParse(value);
  final event =
      id == null ? null : await findEvent(id, guildId: context.guild!.id);
  if (event == null) await replyError(context, 'event_not_found');
  return event;
}

Future<String> _mentionList(User user, List<Snowflake> ids) async {
  if (ids.isEmpty) return await localized(user, 'event_info_nobody');
  final shown = <String>[];
  var length = 0;
  for (final id in ids) {
    final mention = '<@$id>';
    if (length + mention.length + 2 > 960) break;
    shown.add(mention);
    length += mention.length + 2;
  }
  final rest = ids.length - shown.length;
  return rest == 0
      ? shown.join(', ')
      : '${shown.join(', ')} ${await localized(user, 'event_info_more', {
              '&count': '$rest'
            })}';
}

final _setup = ChatCommand(
  'setup',
  'Create an event with its own role, category, text and voice channel.',
  localizedDescriptions: {
    Locale.da:
        'Opret et event med egen rolle, kategori, tekst- og stemmekanal.',
    Locale.de:
        'Ein Event mit eigener Rolle, Kategorie, Text- und Sprachkanal anlegen.',
    Locale.enUs:
        'Create an event with its own role, category, text and voice channel.',
    Locale.esEs:
        'Crear un evento con su propio rol, categoría, canal de texto y de voz.',
    Locale.fr:
        'Créer un événement avec son propre rôle, sa catégorie, son salon textuel et vocal.',
    Locale.ru:
        'Создать событие со своей ролью, категорией, текстовым и голосовым каналом.',
    Locale.hi: 'अपने रोल, कैटेगरी, टेक्स्ट और वॉइस चैनल के साथ एक इवेंट बनाएँ।',
    Locale.zhCn: '创建一个拥有专属身份组、分类、文字和语音频道的活动。',
    Locale.ja: '専用のロール、カテゴリー、テキストとボイスチャンネルを持つイベントを作成します。',
    Locale.ko: '전용 역할, 카테고리, 텍스트·음성 채널이 있는 이벤트를 만듭니다.',
  },
  (
    ChatContext context,
    @Description(
        'The name of the event, also used for the role and category.', {
      Locale.da: 'Eventets navn, bruges også til rollen og kategorien.',
      Locale.de: 'Der Name des Events, gilt auch für Rolle und Kategorie.',
      Locale.enUs:
          'The name of the event, also used for the role and category.',
      Locale.esEs: 'El nombre del evento, también para el rol y la categoría.',
      Locale.fr:
          'Le nom de l’événement, utilisé aussi pour le rôle et la catégorie.',
      Locale.ru: 'Название события, оно же для роли и категории.',
      Locale.hi: 'इवेंट का नाम, रोल और कैटेगरी के लिए भी।',
      Locale.zhCn: '活动名称，也用作身份组和分类的名称。',
      Locale.ja: 'イベント名。ロールとカテゴリーの名前にも使われます。',
      Locale.ko: '이벤트 이름. 역할과 카테고리 이름으로도 쓰입니다.',
    })
    String name, [
    @Description('The team role that can write in the info channel.', {
      Locale.da: 'Teamrollen, der kan skrive i infokanalen.',
      Locale.de: 'Die Team-Rolle, die im Info-Kanal schreiben darf.',
      Locale.enUs: 'The team role that can write in the info channel.',
      Locale.esEs:
          'El rol del equipo que puede escribir en el canal de información.',
      Locale.fr: 'Le rôle de l’équipe qui peut écrire dans le salon d’infos.',
      Locale.ru: 'Роль команды, которая может писать в инфоканал.',
      Locale.hi: 'टीम रोल जो इन्फ़ो चैनल में लिख सकता है।',
      Locale.zhCn: '可以在信息频道发言的团队身份组。',
      Locale.ja: 'インフォチャンネルに書き込めるチームのロール。',
      Locale.ko: '정보 채널에 글을 쓸 수 있는 팀 역할.',
    })
    Role? team,
    @Description('How many voice channels the event gets, 1 to 5.', {
      Locale.da: 'Hvor mange stemmekanaler eventet får, 1 til 5.',
      Locale.de: 'Wie viele Sprachkanäle das Event bekommt, 1 bis 5.',
      Locale.enUs: 'How many voice channels the event gets, 1 to 5.',
      Locale.esEs: 'Cuántos canales de voz tiene el evento, de 1 a 5.',
      Locale.fr: 'Combien de salons vocaux l’événement reçoit, de 1 à 5.',
      Locale.ru: 'Сколько голосовых каналов получит событие, от 1 до 5.',
      Locale.hi: 'इवेंट को कितने वॉइस चैनल मिलें, 1 से 5।',
      Locale.zhCn: '活动的语音频道数量，1 到 5 个。',
      Locale.ja: 'イベントのボイスチャンネル数（1〜5）。',
      Locale.ko: '이벤트의 음성 채널 수, 1~5개.',
    })
    @UseConverter(IntConverter(min: 1, max: maxVoiceChannels))
    int? voice,
  ]) async {
    name = name.trim();
    if (name.isEmpty || name.length > maxEventName) {
      await replyError(context, 'event_invalid_name',
          replace: {'&max': '$maxEventName'});
      return;
    }

    final guild = await context.client.guilds.get(context.guild!.id);
    if ((await guildEvents(guild.id)).length >= maxEvents) {
      await replyError(context, 'event_limit',
          replace: {'&limit': '$maxEvents'});
      return;
    }

    final event = await createEvent(
        context.client, guild, name, context.member!,
        team: team, voiceCount: voice ?? 1);
    if (event == null) {
      await replyError(context, 'event_failed');
      return;
    }

    await replySuccess(context, 'event_created',
        replace: {
          '&name': event.name,
          '&role': '<@&${event.roleId}>',
          '&channel': '<#${event.textChannelId}>',
          '&voice': event.voiceChannelIds.map((id) => '<#$id>').join(', '),
          '&info': '<#${event.infoChannelId}>',
        },
        level: ResponseLevel.hint);
  },
);

final _invite = ChatCommand(
  'invite',
  'Invite members to an event by direct message.',
  localizedDescriptions: {
    Locale.da: 'Inviter medlemmer til et event via privat besked.',
    Locale.de: 'Mitglieder per Direktnachricht zu einem Event einladen.',
    Locale.enUs: 'Invite members to an event by direct message.',
    Locale.esEs: 'Invitar a miembros a un evento por mensaje directo.',
    Locale.fr: 'Inviter des membres à un événement par message privé.',
    Locale.ru: 'Пригласить участников на событие личным сообщением.',
    Locale.hi: 'सदस्यों को डायरेक्ट मैसेज से इवेंट में बुलाएँ।',
    Locale.zhCn: '通过私信邀请成员参加活动。',
    Locale.ja: 'メンバーをDMでイベントに招待します。',
    Locale.ko: 'DM으로 멤버를 이벤트에 초대합니다.',
  },
  (
    ChatContext context,
    @Autocomplete(eventChoices)
    @Description('The event.', _eventDescription)
    String event,
    @Description(
        'Members or roles to invite, e.g. @Anna @Ben @Team or Anna, Ben.', {
      Locale.da:
          'Medlemmer eller roller, f.eks. @Anna @Ben @Team eller Anna, Ben.',
      Locale.de:
          'Mitglieder oder Rollen, z. B. @Anna @Ben @Team oder Anna, Ben.',
      Locale.enUs:
          'Members or roles to invite, e.g. @Anna @Ben @Team or Anna, Ben.',
      Locale.esEs: 'Miembros o roles, p. ej. @Anna @Ben @Team o Anna, Ben.',
      Locale.fr: 'Membres ou rôles, p. ex. @Anna @Ben @Team ou Anna, Ben.',
      Locale.ru: 'Участники или роли, например @Anna @Ben @Team или Anna, Ben.',
      Locale.hi: 'सदस्य या रोल, जैसे @Anna @Ben @Team या Anna, Ben।',
      Locale.zhCn: '要邀请的成员或身份组，例如 @Anna @Ben @Team 或 Anna, Ben。',
      Locale.ja: '招待するメンバーやロール（例：@Anna @Ben @Team や Anna, Ben）。',
      Locale.ko: '초대할 멤버나 역할. 예: @Anna @Ben @Team 또는 Anna, Ben',
    })
    String members, [
    @Description('Your message to the invited members.', {
      Locale.da: 'Din besked til de inviterede.',
      Locale.de: 'Deine Nachricht an die Eingeladenen.',
      Locale.enUs: 'Your message to the invited members.',
      Locale.esEs: 'Tu mensaje para los invitados.',
      Locale.fr: 'Ton message aux invités.',
      Locale.ru: 'Твоё сообщение приглашённым.',
      Locale.hi: 'बुलाए गए सदस्यों के लिए आपका संदेश।',
      Locale.zhCn: '给受邀成员的消息。',
      Locale.ja: '招待するメンバーへのメッセージ。',
      Locale.ko: '초대받는 멤버에게 보낼 메시지.',
    })
    String? message,
  ]) async {
    final found = await _event(context, event);
    if (found == null) return;

    final guild = await context.client.guilds.get(context.guild!.id);
    final resolved = await inviteTargets(guild, members);
    final onlySelf = resolved.ids.length == 1 &&
        resolved.ids.contains(context.user.id) &&
        resolved.unknown.isEmpty;
    final targets = resolved.ids..remove(context.user.id);
    if (onlySelf) {
      await replyError(context, 'event_self_only');
      return;
    }
    final participants = (await eventParticipants(guild, found)).toSet();
    final skipped = targets.where(participants.contains).length;
    targets.removeWhere(participants.contains);
    final unknown = resolved.unknown.map((name) => '`$name`').join(', ');
    if (targets.isEmpty) {
      if (skipped > 0 && resolved.unknown.isEmpty) {
        await replyError(context, 'event_all_joined');
      } else if (resolved.unknown.isEmpty) {
        await replyError(context, 'event_no_members');
      } else {
        await replyError(context, 'event_unknown_names',
            replace: {'&names': unknown});
      }
      return;
    }
    if (targets.length > maxInvites) {
      await replyError(context, 'event_too_many',
          replace: {'&max': '$maxInvites'});
      return;
    }

    var sent = 0;
    for (final userId in targets) {
      if (await sendInvite(
          context.client, found, guild, context.user, userId, message)) {
        sent++;
      }
    }
    final failed = targets.length - sent;

    var text = await localized(context.user, 'event_invited',
        {'&count': '$sent', '&name': found.name});
    if (resolved.unknown.isNotEmpty) {
      text += '\n${await localized(context.user, 'event_unknown_names', {
            '&names': unknown
          })}';
    }
    if (skipped > 0) {
      text += '\n${await localized(context.user, 'event_already_joined', {
            '&count': '$skipped'
          })}';
    }
    if (failed > 0) {
      text += '\n${await localized(context.user, 'event_invite_failed', {
            '&count': '$failed'
          })}';
    }
    await context.respond(
      MessageBuilder(allowedMentions: noMentions, embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(
              sent == 0 ? errorColor : successColor),
          description: text,
        ),
      ]),
      level: ResponseLevel.hint,
    );
  },
);

final _info = ChatCommand(
  'info',
  'Show who joined an event, who has not answered yet and who declined.',
  localizedDescriptions: {
    Locale.da:
        'Vis hvem der er med, hvem der ikke har svaret og hvem der har afslået.',
    Locale.de:
        'Zeigen, wer bei einem Event dabei ist, wer noch nicht geantwortet und wer abgesagt hat.',
    Locale.enUs:
        'Show who joined an event, who has not answered yet and who declined.',
    Locale.esEs:
        'Ver quién se ha unido a un evento, quién no ha respondido y quién ha rechazado.',
    Locale.fr:
        'Voir qui participe à un événement, qui n’a pas encore répondu et qui a refusé.',
    Locale.ru:
        'Показать, кто участвует в событии, кто ещё не ответил и кто отказался.',
    Locale.hi:
        'देखें कि इवेंट में कौन शामिल है, किसने अभी जवाब नहीं दिया और किसने मना किया।',
    Locale.zhCn: '查看谁加入了活动、谁还没回复、谁拒绝了。',
    Locale.ja: 'イベントの参加者、未回答の人、辞退した人を表示します。',
    Locale.ko: '이벤트 참가자, 아직 답하지 않은 사람, 거절한 사람을 보여 줍니다.',
  },
  (
    ChatContext context,
    @Autocomplete(eventChoices)
    @Description('The event.', _eventDescription)
    String event,
  ) async {
    final found = await _event(context, event);
    if (found == null) return;
    final user = context.user;

    final guild = await context.client.guilds.get(context.guild!.id);
    final joined = await eventParticipants(guild, found);
    final invites = await eventInvites(found.id);
    List<Snowflake> withStatus(String status) => [
          for (final entry in invites.entries)
            if (entry.value == status && !joined.contains(entry.key)) entry.key,
        ];
    final pending = withStatus(invitePending);
    final declined = withStatus(inviteDeclined);

    final channels = [
      found.infoChannelId,
      found.textChannelId,
      ...found.voiceChannelIds,
    ].whereType<Snowflake>().map((id) => '<#$id>').join(' ');
    final description = [
      [if (found.roleId != null) '<@&${found.roleId}>', channels]
          .where((part) => part.isNotEmpty)
          .join(' · '),
      if (found.teamRoleId != null)
        await localized(
            user, 'event_info_team', {'&role': '<@&${found.teamRoleId}>'}),
    ].where((line) => line.isNotEmpty).join('\n');

    await context.respond(
      MessageBuilder(allowedMentions: noMentions, embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(eventColor),
          title: found.name,
          description: description.isEmpty ? null : description,
          fields: [
            EmbedFieldBuilder(
              name: await localized(user, 'event_info_participants',
                  {'&count': '${joined.length}'}),
              value: await _mentionList(user, joined),
              isInline: false,
            ),
            EmbedFieldBuilder(
              name: await localized(
                  user, 'event_info_pending', {'&count': '${pending.length}'}),
              value: await _mentionList(user, pending),
              isInline: false,
            ),
            EmbedFieldBuilder(
              name: await localized(user, 'event_info_declined',
                  {'&count': '${declined.length}'}),
              value: await _mentionList(user, declined),
              isInline: false,
            ),
          ],
        ),
      ]),
      level: ResponseLevel.hint,
    );
  },
);

final _end = ChatCommand(
  'end',
  'End an event and delete its role, category and channels.',
  localizedDescriptions: {
    Locale.da: 'Afslut et event og slet dets rolle, kategori og kanaler.',
    Locale.de: 'Ein Event beenden und Rolle, Kategorie und Kanäle löschen.',
    Locale.enUs: 'End an event and delete its role, category and channels.',
    Locale.esEs: 'Terminar un evento y borrar su rol, categoría y canales.',
    Locale.fr:
        'Terminer un événement et supprimer son rôle, sa catégorie et ses salons.',
    Locale.ru: 'Завершить событие и удалить его роль, категорию и каналы.',
    Locale.hi: 'इवेंट खत्म करें और उसका रोल, कैटेगरी और चैनल हटाएँ।',
    Locale.zhCn: '结束活动并删除其身份组、分类和频道。',
    Locale.ja: 'イベントを終了し、ロール、カテゴリー、チャンネルを削除します。',
    Locale.ko: '이벤트를 끝내고 역할, 카테고리, 채널을 삭제합니다.',
  },
  (
    ChatContext context,
    @Autocomplete(eventChoices)
    @Description('The event.', _eventDescription)
    String event,
  ) async {
    final found = await _event(context, event);
    if (found == null) return;
    final user = context.user;

    final confirmed = await context.getConfirmation(
      MessageBuilder(
        allowedMentions: noMentions,
        embeds: [
          EmbedBuilder(
            color: DiscordColor.parseHexString(errorColor),
            description: await localized(
                user, 'event_end_question', {'&name': found.name}),
          ),
        ],
      ),
      values: {
        true: await getString(user, 'event_end_button'),
        false: await getString(user, 'shop_cancel'),
      },
      styles: {true: ButtonStyle.danger, false: ButtonStyle.secondary},
      level: ResponseLevel.hint,
      timeout: const Duration(minutes: 2),
    );
    if (!confirmed) {
      await context.respond(
        MessageBuilder(content: await getString(user, 'event_end_cancelled')),
        level: ResponseLevel.hint,
      );
      return;
    }

    await endEvent(context.client, found);
    await replySuccess(context, 'event_ended',
        replace: {'&name': found.name}, level: ResponseLevel.hint);
  },
);

final event = ChatGroup(
  'event',
  'Create events with their own role and channels and invite members.',
  localizedDescriptions: {
    Locale.da:
        'Opret events med egen rolle og egne kanaler, og inviter medlemmer.',
    Locale.de:
        'Events mit eigener Rolle und eigenen Kanälen anlegen und Mitglieder einladen.',
    Locale.enUs:
        'Create events with their own role and channels and invite members.',
    Locale.esEs:
        'Crear eventos con su propio rol y canales e invitar a miembros.',
    Locale.fr:
        'Créer des événements avec leur rôle et leurs salons, et inviter des membres.',
    Locale.ru:
        'Создавать события со своей ролью и каналами и приглашать участников.',
    Locale.hi: 'अपने रोल और चैनल वाले इवेंट बनाएँ और सदस्यों को बुलाएँ।',
    Locale.zhCn: '创建拥有专属身份组和频道的活动并邀请成员。',
    Locale.ja: '専用のロールとチャンネルを持つイベントを作成し、メンバーを招待します。',
    Locale.ko: '전용 역할과 채널이 있는 이벤트를 만들고 멤버를 초대합니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageEvents, allowsOverrides: false),
  ],
  children: [_setup, _invite, _info, _end],
);
