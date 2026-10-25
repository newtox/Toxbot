import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/tickets.dart';
import 'package:toxbot/utils/reply.dart';

final _close = ChatCommand(
  'close',
  'Close this ticket.',
  localizedDescriptions: {
    Locale.da: 'Luk dette ticket.',
    Locale.de: 'Dieses Ticket schließen.',
    Locale.enUs: 'Close this ticket.',
    Locale.esEs: 'Cerrar este ticket.',
    Locale.fr: 'Fermer ce ticket.',
    Locale.ru: 'Закрыть этот тикет.',
    Locale.hi: 'यह टिकट बंद करें।',
    Locale.zhCn: '关闭此工单。',
    Locale.ja: 'このチケットを閉じます。',
    Locale.ko: '이 티켓을 닫습니다.',
  },
  checks: [GuildCheck.all()],
  (
    ChatContext context, [
    @Description('Why the ticket is closed.', {
      Locale.da: 'Hvorfor ticketet lukkes.',
      Locale.de: 'Warum das Ticket geschlossen wird.',
      Locale.enUs: 'Why the ticket is closed.',
      Locale.esEs: 'Por qué se cierra el ticket.',
      Locale.fr: 'Pourquoi le ticket est fermé.',
      Locale.ru: 'Почему тикет закрывается.',
      Locale.hi: 'टिकट क्यों बंद किया जा रहा है।',
      Locale.zhCn: '关闭工单的原因。',
      Locale.ja: 'チケットを閉じる理由。',
      Locale.ko: '티켓을 닫는 이유.',
    })
    String? reason,
  ]) async {
    final ticket = await ticketForChannel(context.channel.id);
    if (ticket == null) {
      await replyError(context, 'ticket_not_ticket');
      return;
    }

    final guild = await context.client.guilds.get(context.guild!.id);
    if (!await mayManageTicket(guild, context.member!, ticket)) {
      await replyError(context, 'ticket_no_permission');
      return;
    }

    await context.respond(MessageBuilder(allowedMentions: noMentions, embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(successColor),
        description: await guildLocalized(
            ticket.guild, 'ticket_closing', {'&user': '<@${context.user.id}>'}),
      ),
    ]));
    await closeTicket(context.client, ticket, context.user, reason: reason);
  },
);

final _add = ChatCommand(
  'add',
  'Add a member to this ticket.',
  localizedDescriptions: {
    Locale.da: 'Tilføj et medlem til dette ticket.',
    Locale.de: 'Ein Mitglied zu diesem Ticket hinzufügen.',
    Locale.enUs: 'Add a member to this ticket.',
    Locale.esEs: 'Añadir a un miembro a este ticket.',
    Locale.fr: 'Ajouter un membre à ce ticket.',
    Locale.ru: 'Добавить участника в этот тикет.',
    Locale.hi: 'इस टिकट में किसी सदस्य को जोड़ें।',
    Locale.zhCn: '将一名成员加入此工单。',
    Locale.ja: 'このチケットにメンバーを追加します。',
    Locale.ko: '이 티켓에 멤버를 추가합니다.',
  },
  checks: [GuildCheck.all()],
  (
    ChatContext context,
    @Description('The member.', {
      Locale.da: 'Medlemmet.',
      Locale.de: 'Das Mitglied.',
      Locale.enUs: 'The member.',
      Locale.esEs: 'El miembro.',
      Locale.fr: 'Le membre.',
      Locale.ru: 'Участник.',
      Locale.hi: 'सदस्य।',
      Locale.zhCn: '成员。',
      Locale.ja: 'メンバー。',
      Locale.ko: '멤버.',
    })
    Member member,
  ) async {
    final ticket = await ticketForChannel(context.channel.id);
    if (ticket == null) {
      await replyError(context, 'ticket_not_ticket');
      return;
    }

    final guild = await context.client.guilds.get(context.guild!.id);
    if (!await isTicketTeam(guild, context.member!, ticket)) {
      await replyError(context, 'ticket_no_permission');
      return;
    }

    final channel = context.channel;
    if (channel is! GuildTextChannel) return;
    await channel.updatePermissionOverwrite(PermissionOverwriteBuilder(
        id: member.id,
        type: PermissionOverwriteType.member,
        allow: Flags<Permissions>(Permissions.viewChannel.value |
            Permissions.sendMessages.value |
            Permissions.readMessageHistory.value |
            Permissions.attachFiles.value)));
    await replySuccess(context, 'ticket_added',
        replace: {'&user': '<@${member.id}>'});
  },
);

final _remove = ChatCommand(
  'remove',
  'Remove a member from this ticket.',
  localizedDescriptions: {
    Locale.da: 'Fjern et medlem fra dette ticket.',
    Locale.de: 'Ein Mitglied aus diesem Ticket entfernen.',
    Locale.enUs: 'Remove a member from this ticket.',
    Locale.esEs: 'Quitar a un miembro de este ticket.',
    Locale.fr: 'Retirer un membre de ce ticket.',
    Locale.ru: 'Убрать участника из этого тикета.',
    Locale.hi: 'इस टिकट से किसी सदस्य को हटाएँ।',
    Locale.zhCn: '将一名成员移出此工单。',
    Locale.ja: 'このチケットからメンバーを外します。',
    Locale.ko: '이 티켓에서 멤버를 제거합니다.',
  },
  checks: [GuildCheck.all()],
  (
    ChatContext context,
    @Description('The member.', {
      Locale.da: 'Medlemmet.',
      Locale.de: 'Das Mitglied.',
      Locale.enUs: 'The member.',
      Locale.esEs: 'El miembro.',
      Locale.fr: 'Le membre.',
      Locale.ru: 'Участник.',
      Locale.hi: 'सदस्य।',
      Locale.zhCn: '成员。',
      Locale.ja: 'メンバー。',
      Locale.ko: '멤버.',
    })
    Member member,
  ) async {
    final ticket = await ticketForChannel(context.channel.id);
    if (ticket == null) {
      await replyError(context, 'ticket_not_ticket');
      return;
    }

    final guild = await context.client.guilds.get(context.guild!.id);
    if (!await isTicketTeam(guild, context.member!, ticket) ||
        member.id == ticket.userId) {
      await replyError(context, 'ticket_no_permission');
      return;
    }

    final channel = context.channel;
    if (channel is! GuildTextChannel) return;
    await channel.deletePermissionOverwrite(member.id);
    await replySuccess(context, 'ticket_removed',
        replace: {'&user': '<@${member.id}>'});
  },
);

final ticket = ChatGroup(
  'ticket',
  'Manage the ticket in this channel.',
  localizedDescriptions: {
    Locale.da: 'Administrer ticketet i denne kanal.',
    Locale.de: 'Das Ticket in diesem Kanal verwalten.',
    Locale.enUs: 'Manage the ticket in this channel.',
    Locale.esEs: 'Gestionar el ticket de este canal.',
    Locale.fr: 'Gérer le ticket de ce salon.',
    Locale.ru: 'Управлять тикетом в этом канале.',
    Locale.hi: 'इस चैनल का टिकट प्रबंधित करें।',
    Locale.zhCn: '管理此频道中的工单。',
    Locale.ja: 'このチャンネルのチケットを管理します。',
    Locale.ko: '이 채널의 티켓을 관리합니다.',
  },
  children: [_close, _add, _remove],
);
