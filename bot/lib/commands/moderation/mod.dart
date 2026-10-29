import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/moderation.dart';
import 'package:toxbot/utils/reply.dart';

const _bulkDeleteLimit = Duration(days: 14);

final _warn = ChatCommand(
  'warn',
  'Warn a member.',
  localizedDescriptions: {
    Locale.da: 'Advar et medlem.',
    Locale.de: 'Ein Mitglied verwarnen.',
    Locale.enUs: 'Warn a member.',
    Locale.esEs: 'Advertir a un miembro.',
    Locale.fr: 'Avertir un membre.',
    Locale.ru: 'Предупредить участника.',
    Locale.hi: 'किसी सदस्य को चेतावनी दें।',
    Locale.zhCn: '警告一名成员。',
    Locale.ja: 'メンバーに警告します。',
    Locale.ko: '멤버에게 경고합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
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
          [@Description('The reason, shown in the modlog.', {
            Locale.da: 'Begrundelsen, vises i modloggen.',
            Locale.de: 'Der Grund, erscheint im Modlog.',
            Locale.enUs: 'The reason, shown in the modlog.',
            Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
            Locale.fr: 'La raison, affichée dans le journal de modération.',
            Locale.ru: 'Причина, видна в журнале модерации.',
            Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
            Locale.zhCn: '原因，会显示在管理日志中。',
            Locale.ja: '理由。モデレーションログに表示されます。',
            Locale.ko: '사유. 관리 로그에 표시됩니다.',
          })
          String? reason]) =>
      runModerationCommand(context,
          type: 'warn', targetId: member.id, reason: reason),
);

final _timeout = ChatCommand(
  'timeout',
  'Time out a member.',
  localizedDescriptions: {
    Locale.da: 'Giv et medlem timeout.',
    Locale.de: 'Ein Mitglied in den Timeout schicken.',
    Locale.enUs: 'Time out a member.',
    Locale.esEs: 'Aislar temporalmente a un miembro.',
    Locale.fr: 'Exclure temporairement un membre.',
    Locale.ru: 'Отправить участника в тайм-аут.',
    Locale.hi: 'किसी सदस्य को टाइमआउट दें।',
    Locale.zhCn: '禁言一名成员。',
    Locale.ja: 'メンバーをタイムアウトします。',
    Locale.ko: '멤버를 타임아웃합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
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
      @Description('Duration, e.g. 10m, 2h, 7d.', {
        Locale.da: 'Varighed, f.eks. 10m, 2h, 7d.',
        Locale.de: 'Dauer, z. B. 10m, 2h, 7d.',
        Locale.enUs: 'Duration, e.g. 10m, 2h, 7d.',
        Locale.esEs: 'Duración, p. ej. 10m, 2h, 7d.',
        Locale.fr: 'Durée, ex. 10m, 2h, 7d.',
        Locale.ru: 'Длительность, например 10m, 2h, 7d.',
        Locale.hi: 'अवधि, जैसे 10m, 2h, 7d।',
        Locale.zhCn: '时长，例如 10m、2h、7d。',
        Locale.ja: '期間（例: 10m、2h、7d）。',
        Locale.ko: '기간, 예: 10m, 2h, 7d.',
      })
      String duration,
      [@Description('The reason, shown in the modlog.', {
        Locale.da: 'Begrundelsen, vises i modloggen.',
        Locale.de: 'Der Grund, erscheint im Modlog.',
        Locale.enUs: 'The reason, shown in the modlog.',
        Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
        Locale.fr: 'La raison, affichée dans le journal de modération.',
        Locale.ru: 'Причина, видна в журнале модерации.',
        Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
        Locale.zhCn: '原因，会显示在管理日志中。',
        Locale.ja: '理由。モデレーションログに表示されます。',
        Locale.ko: '사유. 관리 로그에 표시됩니다.',
      })
      String? reason]) async {
    final parsed = parseOptionalDuration(duration);
    if (parsed == null) {
      await replyError(context, 'mod_error_duration');
      return;
    }
    await runModerationCommand(context,
        type: 'timeout', targetId: member.id, reason: reason, duration: parsed);
  },
);

final _untimeout = ChatCommand(
  'untimeout',
  'Remove a member\'s timeout.',
  localizedDescriptions: {
    Locale.da: 'Fjern et medlems timeout.',
    Locale.de: 'Den Timeout eines Mitglieds aufheben.',
    Locale.enUs: 'Remove a member\'s timeout.',
    Locale.esEs: 'Quitar el aislamiento de un miembro.',
    Locale.fr: 'Lever l\'exclusion temporaire d\'un membre.',
    Locale.ru: 'Снять тайм-аут с участника.',
    Locale.hi: 'किसी सदस्य का टाइमआउट हटाएँ।',
    Locale.zhCn: '解除一名成员的禁言。',
    Locale.ja: 'メンバーのタイムアウトを解除します。',
    Locale.ko: '멤버의 타임아웃을 해제합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
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
          [@Description('The reason, shown in the modlog.', {
            Locale.da: 'Begrundelsen, vises i modloggen.',
            Locale.de: 'Der Grund, erscheint im Modlog.',
            Locale.enUs: 'The reason, shown in the modlog.',
            Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
            Locale.fr: 'La raison, affichée dans le journal de modération.',
            Locale.ru: 'Причина, видна в журнале модерации.',
            Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
            Locale.zhCn: '原因，会显示在管理日志中。',
            Locale.ja: '理由。モデレーションログに表示されます。',
            Locale.ko: '사유. 관리 로그에 표시됩니다.',
          })
          String? reason]) =>
      runModerationCommand(context,
          type: 'untimeout', targetId: member.id, reason: reason),
);

final _kick = ChatCommand(
  'kick',
  'Kick a member from the server.',
  localizedDescriptions: {
    Locale.da: 'Smid et medlem ud af serveren.',
    Locale.de: 'Ein Mitglied vom Server kicken.',
    Locale.enUs: 'Kick a member from the server.',
    Locale.esEs: 'Expulsar a un miembro del servidor.',
    Locale.fr: 'Expulser un membre du serveur.',
    Locale.ru: 'Выгнать участника с сервера.',
    Locale.hi: 'किसी सदस्य को सर्वर से निकालें।',
    Locale.zhCn: '将一名成员踢出服务器。',
    Locale.ja: 'メンバーをサーバーからキックします。',
    Locale.ko: '멤버를 서버에서 추방합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.kickMembers)],
  (ChatContext context,
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
          [@Description('The reason, shown in the modlog.', {
            Locale.da: 'Begrundelsen, vises i modloggen.',
            Locale.de: 'Der Grund, erscheint im Modlog.',
            Locale.enUs: 'The reason, shown in the modlog.',
            Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
            Locale.fr: 'La raison, affichée dans le journal de modération.',
            Locale.ru: 'Причина, видна в журнале модерации.',
            Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
            Locale.zhCn: '原因，会显示在管理日志中。',
            Locale.ja: '理由。モデレーションログに表示されます。',
            Locale.ko: '사유. 관리 로그에 표시됩니다.',
          })
          String? reason]) =>
      runModerationCommand(context,
          type: 'kick', targetId: member.id, reason: reason),
);

final _ban = ChatCommand(
  'ban',
  'Ban a user, permanently or for a while.',
  localizedDescriptions: {
    Locale.da: 'Udeluk en bruger, permanent eller midlertidigt.',
    Locale.de: 'Einen Nutzer bannen, dauerhaft oder befristet.',
    Locale.enUs: 'Ban a user, permanently or for a while.',
    Locale.esEs: 'Banear a un usuario, para siempre o por un tiempo.',
    Locale.fr: 'Bannir un utilisateur, définitivement ou pour un temps.',
    Locale.ru: 'Забанить пользователя навсегда или на время.',
    Locale.hi: 'किसी उपयोगकर्ता को स्थायी या कुछ समय के लिए बैन करें।',
    Locale.zhCn: '永久或临时封禁一名用户。',
    Locale.ja: 'ユーザーを永久または期間限定で BAN します。',
    Locale.ko: '사용자를 영구 또는 일정 기간 차단합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.banMembers)],
  (ChatContext context,
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
      User user,
      [@Description('The reason, shown in the modlog.', {
        Locale.da: 'Begrundelsen, vises i modloggen.',
        Locale.de: 'Der Grund, erscheint im Modlog.',
        Locale.enUs: 'The reason, shown in the modlog.',
        Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
        Locale.fr: 'La raison, affichée dans le journal de modération.',
        Locale.ru: 'Причина, видна в журнале модерации.',
        Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
        Locale.zhCn: '原因，会显示在管理日志中。',
        Locale.ja: '理由。モデレーションログに表示されます。',
        Locale.ko: '사유. 관리 로그에 표시됩니다.',
      })
      String? reason,
      @Description('Duration of a temporary ban, e.g. 7d. Empty = permanent.', {
        Locale.da:
            'Varighed for en midlertidig udelukkelse, f.eks. 7d. Tom = permanent.',
        Locale.de:
            'Dauer für einen befristeten Bann, z. B. 7d. Leer = dauerhaft.',
        Locale.enUs: 'Duration of a temporary ban, e.g. 7d. Empty = permanent.',
        Locale.esEs:
            'Duración de un baneo temporal, p. ej. 7d. Vacío = permanente.',
        Locale.fr:
            'Durée d\'un bannissement temporaire, ex. 7d. Vide = permanent.',
        Locale.ru:
            'Длительность временного бана, например 7d. Пусто = навсегда.',
        Locale.hi: 'अस्थायी बैन की अवधि, जैसे 7d। खाली = स्थायी।',
        Locale.zhCn: '临时封禁的时长，例如 7d。留空 = 永久。',
        Locale.ja: '期間限定 BAN の期間（例: 7d）。空欄 = 永久。',
        Locale.ko: '임시 차단 기간, 예: 7d. 비우면 영구.',
      })
      String? duration,
      @Description('Delete messages from the last days (0-7).', {
        Locale.da: 'Slet beskeder fra de seneste dage (0-7).',
        Locale.de: 'Nachrichten der letzten Tage löschen (0-7).',
        Locale.enUs: 'Delete messages from the last days (0-7).',
        Locale.esEs: 'Borrar los mensajes de los últimos días (0-7).',
        Locale.fr: 'Supprimer les messages des derniers jours (0-7).',
        Locale.ru: 'Удалить сообщения за последние дни (0-7).',
        Locale.hi: 'पिछले दिनों के संदेश हटाएँ (0-7)।',
        Locale.zhCn: '删除最近几天的消息（0-7）。',
        Locale.ja: '過去の日数分のメッセージを削除（0-7）。',
        Locale.ko: '최근 며칠간의 메시지 삭제 (0-7).',
      })
      @UseConverter(IntConverter(min: 0, max: 7))
      int? deleteMessages]) async {
    final parsed = parseOptionalDuration(duration);
    if (duration != null && duration.trim().isNotEmpty && parsed == null) {
      await replyError(context, 'mod_error_duration');
      return;
    }
    await runModerationCommand(context,
        type: 'ban',
        targetId: user.id,
        reason: reason,
        duration: parsed,
        deleteDays: deleteMessages ?? 0);
  },
);

final _unban = ChatCommand(
  'unban',
  'Lift a ban.',
  localizedDescriptions: {
    Locale.da: 'Ophæv en udelukkelse.',
    Locale.de: 'Einen Bann aufheben.',
    Locale.enUs: 'Lift a ban.',
    Locale.esEs: 'Levantar un baneo.',
    Locale.fr: 'Lever un bannissement.',
    Locale.ru: 'Снять бан.',
    Locale.hi: 'बैन हटाएँ।',
    Locale.zhCn: '解除封禁。',
    Locale.ja: 'BAN を解除します。',
    Locale.ko: '차단을 해제합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.banMembers)],
  (ChatContext context,
      @Description('User ID of the banned person.', {
        Locale.da: 'Bruger-ID på den udelukkede person.',
        Locale.de: 'Nutzer-ID der gebannten Person.',
        Locale.enUs: 'User ID of the banned person.',
        Locale.esEs: 'ID de usuario de la persona baneada.',
        Locale.fr: 'ID de l\'utilisateur banni.',
        Locale.ru: 'ID забаненного пользователя.',
        Locale.hi: 'बैन किए गए व्यक्ति की यूज़र ID।',
        Locale.zhCn: '被封禁用户的 ID。',
        Locale.ja: 'BAN されたユーザーの ID。',
        Locale.ko: '차단된 사용자의 ID.',
      })
      String userId,
      [@Description('The reason, shown in the modlog.', {
        Locale.da: 'Begrundelsen, vises i modloggen.',
        Locale.de: 'Der Grund, erscheint im Modlog.',
        Locale.enUs: 'The reason, shown in the modlog.',
        Locale.esEs: 'El motivo, se muestra en el registro de moderación.',
        Locale.fr: 'La raison, affichée dans le journal de modération.',
        Locale.ru: 'Причина, видна в журнале модерации.',
        Locale.hi: 'कारण, जो मॉडलॉग में दिखता है।',
        Locale.zhCn: '原因，会显示在管理日志中。',
        Locale.ja: '理由。モデレーションログに表示されます。',
        Locale.ko: '사유. 관리 로그에 표시됩니다.',
      })
      String? reason]) async {
    final id = Snowflake.parse(
        int.tryParse(userId.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0);
    if (id.value == 0) {
      await replyError(context, 'mod_error_not_banned');
      return;
    }
    await runModerationCommand(context,
        type: 'unban', targetId: id, reason: reason);
  },
);

final _cases = ChatCommand(
  'cases',
  'Show the latest cases of a member.',
  localizedDescriptions: {
    Locale.da: 'Vis de seneste sager for et medlem.',
    Locale.de: 'Die letzten Fälle eines Mitglieds anzeigen.',
    Locale.enUs: 'Show the latest cases of a member.',
    Locale.esEs: 'Mostrar los últimos casos de un miembro.',
    Locale.fr: 'Afficher les derniers cas d\'un membre.',
    Locale.ru: 'Показать последние случаи участника.',
    Locale.hi: 'किसी सदस्य के हाल के केस दिखाएँ।',
    Locale.zhCn: '显示一名成员的最近案例。',
    Locale.ja: 'メンバーの最近のケースを表示します。',
    Locale.ko: '멤버의 최근 기록을 표시합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
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
    User user,
  ) async {
    final cases = await casesFor(context.guild!.id, userId: user.id);
    if (cases.isEmpty) {
      await replySuccess(context, 'mod_cases_none');
      return;
    }

    final lines = <String>[];
    for (final modCase in cases) {
      final type = await localized(context.user, 'mod_type_${modCase.type}');
      final reason =
          modCase.reason ?? await localized(context.user, 'mod_no_reason');
      final short = reason.length > 80 ? '${reason.substring(0, 77)}…' : reason;
      lines.add(
          '**#${modCase.number}** · $type · <t:${modCase.createdAt.millisecondsSinceEpoch ~/ 1000}:d>\n$short');
    }

    await context.respond(
      MessageBuilder(allowedMentions: noMentions, embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString('#5865f2'),
          title: await localized(context.user, 'mod_cases_title',
              {'&user': user.globalName ?? user.username}),
          description: lines.join('\n\n'),
        )
      ]),
      level: ResponseLevel.hint,
    );
  },
);

final _case = ChatCommand(
  'case',
  'Show a single case.',
  localizedDescriptions: {
    Locale.da: 'Vis en enkelt sag.',
    Locale.de: 'Einen einzelnen Fall anzeigen.',
    Locale.enUs: 'Show a single case.',
    Locale.esEs: 'Mostrar un caso concreto.',
    Locale.fr: 'Afficher un cas précis.',
    Locale.ru: 'Показать один случай.',
    Locale.hi: 'एक केस दिखाएँ।',
    Locale.zhCn: '显示单个案例。',
    Locale.ja: '1 件のケースを表示します。',
    Locale.ko: '기록 하나를 표시합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (
    ChatContext context,
    @Description('The case number.', {
      Locale.da: 'Sagsnummeret.',
      Locale.de: 'Die Nummer des Falls.',
      Locale.enUs: 'The case number.',
      Locale.esEs: 'El número del caso.',
      Locale.fr: 'Le numéro du cas.',
      Locale.ru: 'Номер случая.',
      Locale.hi: 'केस नंबर।',
      Locale.zhCn: '案例编号。',
      Locale.ja: 'ケース番号。',
      Locale.ko: '기록 번호.',
    })
    @UseConverter(IntConverter(min: 1))
    int number,
  ) async {
    final modCase = await caseByNumber(context.guild!.id, number);
    if (modCase == null) {
      await replyError(context, 'mod_case_not_found',
          replace: {'&number': '$number'});
      return;
    }
    await context.respond(
      MessageBuilder(
          allowedMentions: noMentions,
          embeds: [await caseEmbed(modCase, context.user)]),
      level: ResponseLevel.hint,
    );
  },
);

final _clear = ChatCommand(
  'clear',
  'Delete messages in this channel.',
  localizedDescriptions: {
    Locale.da: 'Slet beskeder i denne kanal.',
    Locale.de: 'Nachrichten in diesem Kanal löschen.',
    Locale.enUs: 'Delete messages in this channel.',
    Locale.esEs: 'Borrar mensajes en este canal.',
    Locale.fr: 'Supprimer des messages dans ce salon.',
    Locale.ru: 'Удалить сообщения в этом канале.',
    Locale.hi: 'इस चैनल में संदेश हटाएँ।',
    Locale.zhCn: '删除此频道中的消息。',
    Locale.ja: 'このチャンネルのメッセージを削除します。',
    Locale.ko: '이 채널의 메시지를 삭제합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.manageMessages)],
  (
    ChatContext context,
    @UseConverter(IntConverter(min: 1, max: 500))
    @Description('How many messages you want to delete (1-500).', {
      Locale.da: 'Hvor mange beskeder du vil slette (1-500).',
      Locale.de: 'Wie viele Nachrichten du löschen willst (1-500).',
      Locale.enUs: 'How many messages you want to delete (1-500).',
      Locale.esEs: 'Cuántos mensajes quieres borrar (1-500).',
      Locale.fr: 'Combien de messages tu veux supprimer (1-500).',
      Locale.ru: 'Сколько сообщений хочешь удалить (1-500).',
      Locale.hi: 'आप कितने संदेश हटाना चाहते हैं (1-500)।',
      Locale.zhCn: '你想删除的消息数量（1-500）。',
      Locale.ja: '削除したいメッセージの数（1〜500）。',
      Locale.ko: '삭제할 메시지 수 (1-500).',
    })
    int amount, [
    @Description('Only delete messages from this user.', {
      Locale.da: 'Slet kun beskeder fra denne bruger.',
      Locale.de: 'Nur Nachrichten von diesem Nutzer löschen.',
      Locale.enUs: 'Only delete messages from this user.',
      Locale.esEs: 'Borrar solo los mensajes de este usuario.',
      Locale.fr: 'Supprimer seulement les messages de cet utilisateur.',
      Locale.ru: 'Удалять только сообщения этого пользователя.',
      Locale.hi: 'सिर्फ़ इस उपयोगकर्ता के संदेश हटाएँ।',
      Locale.zhCn: '只删除此用户的消息。',
      Locale.ja: 'このユーザーのメッセージだけを削除します。',
      Locale.ko: '이 사용자의 메시지만 삭제합니다.',
    })
    User? user,
  ]) async {
    if (context is InteractionChatContext) {
      await context.acknowledge(level: ResponseLevel.hint);
    }

    final cutoff = DateTime.now().toUtc().subtract(_bulkDeleteLimit);
    var deleted = 0;
    var tooOld = false;
    Snowflake? before;

    try {
      while (deleted < amount && !tooOld) {
        final batch = await context.channel.messages
            .fetchMany(limit: 100, before: before);
        if (batch.isEmpty) break;
        before = batch.last.id;

        final ids = <Snowflake>[];
        for (final message in batch) {
          if (deleted + ids.length >= amount) break;
          if (message.timestamp.toUtc().isBefore(cutoff)) {
            tooOld = true;
            break;
          }
          if (user == null || message.author.id == user.id) {
            ids.add(message.id);
          }
        }

        if (ids.length == 1) {
          await context.channel.messages.delete(ids.first);
        } else if (ids.length > 1) {
          await context.channel.messages.bulkDelete(ids);
        }
        deleted += ids.length;
        if (batch.length < 100) break;
      }
    } on HttpResponseError {
      await replyError(context, 'clear_failed',
          replace: {'&count': '$deleted'});
      return;
    }

    await replySuccess(
      context,
      tooOld && deleted < amount ? 'clear_cleared_old' : 'clear_cleared',
      replace: {'&count': '$deleted'},
      level: ResponseLevel.hint,
    );
  },
);

final mod = ChatGroup(
  'mod',
  'Warn, time out, kick and ban members, clear messages and look up cases.',
  localizedDescriptions: {
    Locale.da:
        'Advar, giv timeout, smid ud og udeluk medlemmer, slet beskeder og se sager.',
    Locale.de:
        'Mitglieder verwarnen, timeouten, kicken und bannen, Nachrichten löschen und Fälle ansehen.',
    Locale.enUs:
        'Warn, time out, kick and ban members, clear messages and look up cases.',
    Locale.esEs:
        'Advertir, aislar, expulsar y banear miembros, borrar mensajes y ver casos.',
    Locale.fr:
        'Avertir, exclure, expulser et bannir des membres, supprimer des messages et consulter les cas.',
    Locale.ru:
        'Предупреждать, мьютить, выгонять и банить участников, удалять сообщения и смотреть случаи.',
    Locale.hi:
        'सदस्यों को चेतावनी, टाइमआउट, किक और बैन दें, संदेश हटाएँ और केस देखें।',
    Locale.zhCn: '警告、禁言、踢出和封禁成员，删除消息并查看案例。',
    Locale.ja: 'メンバーへの警告、タイムアウト、キック、BAN、メッセージ削除とケースの確認を行います。',
    Locale.ko: '멤버 경고, 타임아웃, 추방, 차단, 메시지 삭제 및 기록 조회를 합니다.',
  },
  children: [
    _warn,
    _timeout,
    _untimeout,
    _kick,
    _ban,
    _unban,
    _clear,
    _cases,
    _case,
  ],
);
