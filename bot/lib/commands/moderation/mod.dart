import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/moderation.dart';
import 'package:toxbot/utils/reply.dart';

const _userDescription = {
  Locale.da: 'Medlemmet.',
  Locale.de: 'Das Mitglied.',
  Locale.enUs: 'The member.',
  Locale.esEs: 'El miembro.',
  Locale.fr: 'Le membre.',
  Locale.ru: 'Участник.',
  Locale.hi: 'सदस्य।',
  Locale.zhCn: '成员。',
  Locale.ja: '対象のメンバー。',
  Locale.ko: '대상 멤버.',
};

const _reasonDescription = {
  Locale.da: 'Årsagen, vises i modloggen.',
  Locale.de: 'Der Grund, erscheint im Modlog.',
  Locale.enUs: 'The reason, shown in the modlog.',
  Locale.esEs: 'El motivo, aparece en el registro de moderación.',
  Locale.fr: 'La raison, affichée dans le journal de modération.',
  Locale.ru: 'Причина, видна в журнале модерации.',
  Locale.hi: 'कारण, मॉडलॉग में दिखता है।',
  Locale.zhCn: '原因，会显示在管理日志中。',
  Locale.ja: '理由。モデレーションログに表示されます。',
  Locale.ko: '사유, 관리 로그에 표시됩니다.',
};

const _durationDescription = {
  Locale.da: 'Varighed, f.eks. 10m, 2h, 7d.',
  Locale.de: 'Dauer, z. B. 10m, 2h, 7d.',
  Locale.enUs: 'Duration, e.g. 10m, 2h, 7d.',
  Locale.esEs: 'Duración, p. ej. 10m, 2h, 7d.',
  Locale.fr: 'Durée, par ex. 10m, 2h, 7d.',
  Locale.ru: 'Длительность, например 10m, 2h, 7d.',
  Locale.hi: 'अवधि, जैसे 10m, 2h, 7d।',
  Locale.zhCn: '时长，例如 10m、2h、7d。',
  Locale.ja: '期間（例: 10m、2h、7d）。',
  Locale.ko: '기간, 예: 10m, 2h, 7d.',
};

const _banDurationDescription = {
  Locale.da: 'Varighed for en midlertidig ban, f.eks. 7d. Tom = permanent.',
  Locale.de: 'Dauer für einen befristeten Bann, z. B. 7d. Leer = dauerhaft.',
  Locale.enUs: 'Duration of a temporary ban, e.g. 7d. Empty = permanent.',
  Locale.esEs: 'Duración de un baneo temporal, p. ej. 7d. Vacío = permanente.',
  Locale.fr:
      'Durée d\'un bannissement temporaire, par ex. 7d. Vide = permanent.',
  Locale.ru: 'Срок временного бана, например 7d. Пусто = навсегда.',
  Locale.hi: 'अस्थायी बैन की अवधि, जैसे 7d। खाली = स्थायी।',
  Locale.zhCn: '临时封禁的时长，例如 7d。留空 = 永久。',
  Locale.ja: '一時 BAN の期間（例: 7d）。空欄で永久。',
  Locale.ko: '임시 차단 기간, 예: 7d. 비우면 영구.',
};

const _deleteDescription = {
  Locale.da: 'Slet beskeder fra de sidste dage (0-7).',
  Locale.de: 'Nachrichten der letzten Tage löschen (0-7).',
  Locale.enUs: 'Delete messages from the last days (0-7).',
  Locale.esEs: 'Borrar mensajes de los últimos días (0-7).',
  Locale.fr: 'Supprimer les messages des derniers jours (0-7).',
  Locale.ru: 'Удалить сообщения за последние дни (0-7).',
  Locale.hi: 'पिछले दिनों के संदेश हटाएँ (0-7)।',
  Locale.zhCn: '删除最近几天的消息（0-7）。',
  Locale.ja: '直近の日数分のメッセージを削除（0〜7）。',
  Locale.ko: '최근 며칠간의 메시지 삭제 (0-7).',
};

const _userIdDescription = {
  Locale.da: 'Bruger-ID på den bannede person.',
  Locale.de: 'Benutzer-ID der gebannten Person.',
  Locale.enUs: 'User ID of the banned person.',
  Locale.esEs: 'ID de usuario de la persona baneada.',
  Locale.fr: 'ID de l\'utilisateur banni.',
  Locale.ru: 'ID забаненного пользователя.',
  Locale.hi: 'बैन किए गए व्यक्ति की यूज़र ID।',
  Locale.zhCn: '被封禁者的用户 ID。',
  Locale.ja: 'BAN されたユーザーの ID。',
  Locale.ko: '차단된 사용자의 ID.',
};

const _numberDescription = {
  Locale.da: 'Sagens nummer.',
  Locale.de: 'Die Nummer des Falls.',
  Locale.enUs: 'The case number.',
  Locale.esEs: 'El número del caso.',
  Locale.fr: 'Le numéro du cas.',
  Locale.ru: 'Номер случая.',
  Locale.hi: 'केस नंबर।',
  Locale.zhCn: '案例编号。',
  Locale.ja: 'ケース番号。',
  Locale.ko: '케이스 번호.',
};

final _warn = ChatCommand(
  'warn',
  'Warn a member.',
  localizedDescriptions: const {
    Locale.da: 'Giv et medlem en advarsel.',
    Locale.de: 'Ein Mitglied verwarnen.',
    Locale.enUs: 'Warn a member.',
    Locale.esEs: 'Advertir a un miembro.',
    Locale.fr: 'Avertir un membre.',
    Locale.ru: 'Выдать предупреждение участнику.',
    Locale.hi: 'किसी सदस्य को चेतावनी दें।',
    Locale.zhCn: '警告一名成员。',
    Locale.ja: 'メンバーに警告する。',
    Locale.ko: '멤버에게 경고합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
          @Description('The member.', _userDescription) Member member,
          [@Description('The reason, shown in the modlog.', _reasonDescription)
          String? reason]) =>
      runModerationCommand(context,
          type: 'warn', targetId: member.id, reason: reason),
);

final _timeout = ChatCommand(
  'timeout',
  'Time out a member.',
  localizedDescriptions: const {
    Locale.da: 'Giv et medlem timeout.',
    Locale.de: 'Ein Mitglied in den Timeout schicken.',
    Locale.enUs: 'Time out a member.',
    Locale.esEs: 'Aislar temporalmente a un miembro.',
    Locale.fr: 'Exclure temporairement un membre.',
    Locale.ru: 'Отправить участника в тайм-аут.',
    Locale.hi: 'किसी सदस्य को टाइमआउट दें।',
    Locale.zhCn: '禁言一名成员。',
    Locale.ja: 'メンバーをタイムアウトする。',
    Locale.ko: '멤버를 타임아웃합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
      @Description('The member.', _userDescription) Member member,
      @Description('Duration, e.g. 10m, 2h, 7d.', _durationDescription)
      String duration,
      [@Description('The reason, shown in the modlog.', _reasonDescription)
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
  localizedDescriptions: const {
    Locale.da: 'Fjern et medlems timeout.',
    Locale.de: 'Den Timeout eines Mitglieds aufheben.',
    Locale.enUs: 'Remove a member\'s timeout.',
    Locale.esEs: 'Quitar el aislamiento de un miembro.',
    Locale.fr: 'Lever l\'exclusion temporaire d\'un membre.',
    Locale.ru: 'Снять тайм-аут с участника.',
    Locale.hi: 'किसी सदस्य का टाइमआउट हटाएँ।',
    Locale.zhCn: '解除成员的禁言。',
    Locale.ja: 'メンバーのタイムアウトを解除する。',
    Locale.ko: '멤버의 타임아웃을 해제합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (ChatContext context,
          @Description('The member.', _userDescription) Member member,
          [@Description('The reason, shown in the modlog.', _reasonDescription)
          String? reason]) =>
      runModerationCommand(context,
          type: 'untimeout', targetId: member.id, reason: reason),
);

final _kick = ChatCommand(
  'kick',
  'Kick a member from the server.',
  localizedDescriptions: const {
    Locale.da: 'Smid et medlem ud af serveren.',
    Locale.de: 'Ein Mitglied vom Server kicken.',
    Locale.enUs: 'Kick a member from the server.',
    Locale.esEs: 'Expulsar a un miembro del servidor.',
    Locale.fr: 'Expulser un membre du serveur.',
    Locale.ru: 'Выгнать участника с сервера.',
    Locale.hi: 'किसी सदस्य को सर्वर से निकालें।',
    Locale.zhCn: '将成员踢出服务器。',
    Locale.ja: 'メンバーをサーバーからキックする。',
    Locale.ko: '멤버를 서버에서 추방합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.kickMembers)],
  (ChatContext context,
          @Description('The member.', _userDescription) Member member,
          [@Description('The reason, shown in the modlog.', _reasonDescription)
          String? reason]) =>
      runModerationCommand(context,
          type: 'kick', targetId: member.id, reason: reason),
);

final _ban = ChatCommand(
  'ban',
  'Ban a user, optionally only for a while.',
  localizedDescriptions: const {
    Locale.da: 'Ban en bruger, eventuelt kun midlertidigt.',
    Locale.de: 'Einen Nutzer bannen, auf Wunsch nur befristet.',
    Locale.enUs: 'Ban a user, optionally only for a while.',
    Locale.esEs: 'Banear a un usuario, opcionalmente solo por un tiempo.',
    Locale.fr: 'Bannir un utilisateur, éventuellement pour une durée limitée.',
    Locale.ru: 'Забанить пользователя, при желании временно.',
    Locale.hi: 'किसी यूज़र को बैन करें, चाहें तो कुछ समय के लिए।',
    Locale.zhCn: '封禁一名用户，可选择临时封禁。',
    Locale.ja: 'ユーザーを BAN する（期間指定も可）。',
    Locale.ko: '사용자를 차단합니다. 기간을 정할 수도 있습니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.banMembers)],
  (ChatContext context, @Description('The member.', _userDescription) User user,
      [@Description('The reason, shown in the modlog.', _reasonDescription)
      String? reason,
      @Description('Duration of a temporary ban, e.g. 7d. Empty = permanent.',
          _banDurationDescription)
      String? duration,
      @Description(
          'Delete messages from the last days (0-7).', _deleteDescription)
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
  localizedDescriptions: const {
    Locale.da: 'Ophæv en ban.',
    Locale.de: 'Einen Bann aufheben.',
    Locale.enUs: 'Lift a ban.',
    Locale.esEs: 'Levantar un baneo.',
    Locale.fr: 'Lever un bannissement.',
    Locale.ru: 'Снять бан.',
    Locale.hi: 'बैन हटाएँ।',
    Locale.zhCn: '解除封禁。',
    Locale.ja: 'BAN を解除する。',
    Locale.ko: '차단을 해제합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.banMembers)],
  (ChatContext context,
      @Description('User ID of the banned person.', _userIdDescription)
      String userId,
      [@Description('The reason, shown in the modlog.', _reasonDescription)
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
  'Show the latest cases of a user.',
  localizedDescriptions: const {
    Locale.da: 'Vis de seneste sager for en bruger.',
    Locale.de: 'Die letzten Fälle eines Nutzers anzeigen.',
    Locale.enUs: 'Show the latest cases of a user.',
    Locale.esEs: 'Mostrar los últimos casos de un usuario.',
    Locale.fr: 'Afficher les derniers cas d\'un utilisateur.',
    Locale.ru: 'Показать последние случаи пользователя.',
    Locale.hi: 'किसी यूज़र के हाल के केस दिखाएँ।',
    Locale.zhCn: '显示用户最近的案例。',
    Locale.ja: 'ユーザーの最近のケースを表示する。',
    Locale.ko: '사용자의 최근 케이스를 표시합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (
    ChatContext context,
    @Description('The member.', _userDescription) User user,
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
  localizedDescriptions: const {
    Locale.da: 'Vis en enkelt sag.',
    Locale.de: 'Einen einzelnen Fall anzeigen.',
    Locale.enUs: 'Show a single case.',
    Locale.esEs: 'Mostrar un caso concreto.',
    Locale.fr: 'Afficher un cas précis.',
    Locale.ru: 'Показать один случай.',
    Locale.hi: 'एक केस दिखाएँ।',
    Locale.zhCn: '显示单个案例。',
    Locale.ja: 'ケースを 1 件表示する。',
    Locale.ko: '케이스 하나를 표시합니다.',
  },
  checks: [GuildCheck.all(), PermissionsCheck(Permissions.moderateMembers)],
  (
    ChatContext context,
    @Description('The case number.', _numberDescription)
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

final mod = ChatGroup(
  'mod',
  'Moderation: warnings, timeouts, kicks, bans and cases.',
  localizedDescriptions: const {
    Locale.da: 'Moderation: advarsler, timeouts, kicks, bans og sager.',
    Locale.de: 'Moderation: Verwarnungen, Timeouts, Kicks, Banns und Fälle.',
    Locale.enUs: 'Moderation: warnings, timeouts, kicks, bans and cases.',
    Locale.esEs:
        'Moderación: advertencias, aislamientos, expulsiones, baneos y casos.',
    Locale.fr:
        'Modération : avertissements, exclusions, expulsions, bannissements et cas.',
    Locale.ru: 'Модерация: предупреждения, тайм-ауты, кики, баны и случаи.',
    Locale.hi: 'मॉडरेशन: चेतावनी, टाइमआउट, किक, बैन और केस।',
    Locale.zhCn: '管理：警告、禁言、踢出、封禁和案例。',
    Locale.ja: 'モデレーション: 警告、タイムアウト、キック、BAN、ケース。',
    Locale.ko: '관리: 경고, 타임아웃, 추방, 차단, 케이스.',
  },
  children: [_warn, _timeout, _untimeout, _kick, _ban, _unban, _cases, _case],
);
