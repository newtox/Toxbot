import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/setup.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

const _setupColor = '#5865f2';

final setup = ChatCommand(
  'setup',
  'Set up tickets, moderation, protection and verification.',
  localizedDescriptions: {
    Locale.da: 'Opsæt tickets, moderation, beskyttelse og verificering.',
    Locale.de: 'Tickets, Moderation, Schutz und Verifizierung einrichten.',
    Locale.enUs: 'Set up tickets, moderation, protection and verification.',
    Locale.esEs: 'Configurar tickets, moderación, protección y verificación.',
    Locale.fr:
        'Configurer les tickets, la modération, la protection et la vérification.',
    Locale.ru: 'Настроить тикеты, модерацию, защиту и верификацию.',
    Locale.hi: 'टिकट, मॉडरेशन, सुरक्षा और वेरिफ़िकेशन सेट करें।',
    Locale.zhCn: '设置工单、管理、防护和验证。',
    Locale.ja: 'チケット、モデレーション、保護、認証を設定します。',
    Locale.ko: '티켓, 관리, 보호, 인증을 설정합니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context, [
    @Description(
        'The team role. Without it, Toxbot uses roles with moderator rights.', {
      Locale.da:
          'Teamrollen. Uden den bruger Toxbot roller med moderatorrettigheder.',
      Locale.de:
          'Die Team-Rolle. Ohne sie nimmt Toxbot Rollen mit Moderationsrechten.',
      Locale.enUs:
          'The team role. Without it, Toxbot uses roles with moderator rights.',
      Locale.esEs:
          'El rol del equipo. Sin él, Toxbot usa roles con permisos de moderación.',
      Locale.fr:
          'Le rôle de l’équipe. Sans lui, Toxbot prend les rôles avec des droits de modération.',
      Locale.ru: 'Роль команды. Без неё Toxbot берёт роли с правами модерации.',
      Locale.hi: 'टीम रोल। इसके बिना Toxbot मॉडरेटर अधिकारों वाले रोल लेता है।',
      Locale.zhCn: '团队身份组。不填时 Toxbot 使用拥有管理权限的身份组。',
      Locale.ja: 'チームのロール。指定しない場合はモデレーター権限を持つロールを使います。',
      Locale.ko: '팀 역할. 비워 두면 관리 권한이 있는 역할을 사용합니다.',
    })
    Role? team,
  ]) async {
    final user = context.user;
    final guild = await context.client.guilds.get(context.guild!.id);

    if (dashboardUrl == null) {
      await replyError(context, 'setup_unavailable');
      return;
    }

    final roles = team == null ? await teamRoles(guild) : [team];
    if (roles.isEmpty) {
      await replyError(context, 'setup_no_team');
      return;
    }
    final roleList = roles.take(10).map((role) => '<@&${role.id}>').join(', ');

    final areas = await context.getMultiSelection<String>(
      setupAreas,
      MessageBuilder(
        allowedMentions: noMentions,
        embeds: [
          EmbedBuilder(
            color: DiscordColor.parseHexString(_setupColor),
            title: await getString(user, 'setup_title'),
            description:
                await localized(user, 'setup_choose', {'&roles': roleList}),
          ),
        ],
      ),
      level: ResponseLevel.hint,
      timeout: const Duration(minutes: 5),
      toSelectMenuOption: (area) async => SelectMenuOptionBuilder(
        label: await getString(user, 'setup_area_$area'),
        description: await getString(user, 'setup_area_${area}_text'),
        value: area,
        isDefault: area != 'verification',
      ),
    );

    await context.respond(
      MessageBuilder(
        allowedMentions: noMentions,
        content: await getString(user, 'setup_running'),
      ),
      level: ResponseLevel.hint,
    );

    final steps = await runAutoSetup(
      guild: guild,
      areas: areas,
      teamRoleIds: [for (final role in roles) role.id],
      user: user,
      userName: context.member?.nick,
    );
    if (steps == null) {
      await replyError(context, 'setup_failed');
      return;
    }

    final failed = steps.any((step) => !step.ok);
    final lines = [
      for (final step in steps) '${step.ok ? '✅' : '❌'} ${step.text}',
    ];
    final base = publicDashboardUrl;
    await context.respond(
      MessageBuilder(
        allowedMentions: noMentions,
        embeds: [
          EmbedBuilder(
            color:
                DiscordColor.parseHexString(failed ? errorColor : successColor),
            title: await getString(
                user, failed ? 'setup_done_errors' : 'setup_done'),
            description: lines.join('\n').length > 4000
                ? '${lines.join('\n').substring(0, 3990)}…'
                : lines.join('\n'),
          ),
        ],
        components: base == null
            ? null
            : [
                ActionRowBuilder(components: [
                  ButtonBuilder.link(
                    url: Uri.parse('$base/servers/${guild.id}'),
                    label: await getString(user, 'setup_dashboard'),
                  ),
                ]),
              ],
      ),
      level: ResponseLevel.hint,
    );
  },
);
