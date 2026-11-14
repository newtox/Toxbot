import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:shiori/services/setup.dart';
import 'package:shiori/utils/config.dart';
import 'package:shiori/utils/database.dart';
import 'package:shiori/utils/reply.dart';

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
    Locale.es419: 'Configurar tickets, moderación, protección y verificación.',
    Locale.ptBr: 'Configurar tickets, moderação, proteção e verificação.',
    Locale.tr: 'Ticket\'ları, moderasyonu, korumayı ve doğrulamayı kur.',
    Locale.pl: 'Skonfiguruj tickety, moderację, ochronę i weryfikację.',
    Locale.it: 'Configura ticket, moderazione, protezione e verifica.',
    Locale.uk: 'Налаштувати тікети, модерацію, захист і перевірку.',
    Locale.vi: 'Thiết lập ticket, kiểm duyệt, bảo vệ và xác minh.',
    Locale.th: 'ตั้งค่าทิกเก็ต การดูแล การป้องกัน และการยืนยันตัวตน',
    Locale.zhTw: '設定客服單、管理、防護和驗證。',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context, [
    @Description(
        'The team role. Without it, Shiori uses roles with moderator rights.', {
      Locale.da:
          'Teamrollen. Uden den bruger Shiori roller med moderatorrettigheder.',
      Locale.de:
          'Die Team-Rolle. Ohne sie nimmt Shiori Rollen mit Moderationsrechten.',
      Locale.enUs:
          'The team role. Without it, Shiori uses roles with moderator rights.',
      Locale.esEs:
          'El rol del equipo. Sin él, Shiori usa roles con permisos de moderación.',
      Locale.fr:
          'Le rôle de l’équipe. Sans lui, Shiori prend les rôles avec des droits de modération.',
      Locale.ru: 'Роль команды. Без неё Shiori берёт роли с правами модерации.',
      Locale.hi: 'टीम रोल। इसके बिना Shiori मॉडरेटर अधिकारों वाले रोल लेती है।',
      Locale.zhCn: '团队身份组。不填时 Shiori 使用拥有管理权限的身份组。',
      Locale.ja: 'チームのロール。指定しない場合はモデレーター権限を持つロールを使います。',
      Locale.ko: '팀 역할. 비워 두면 관리 권한이 있는 역할을 사용합니다.',
      Locale.es419:
          'El rol del equipo. Sin él, Shiori usa roles con permisos de moderación.',
      Locale.ptBr: 'O cargo da equipe. Sem ele, a Shiori usa cargos com direitos de moderador.',
      Locale.tr: 'Ekip rolü. Belirtilmezse Shiori moderatör yetkili rolleri kullanır.',
      Locale.pl: 'Rola zespołu. Bez niej Shiori użyje ról z uprawnieniami moderatora.',
      Locale.it: 'Il ruolo del team. Senza, Shiori usa i ruoli con permessi da moderatore.',
      Locale.uk: 'Роль команди. Без неї Shiori використовує ролі з правами модератора.',
      Locale.vi: 'Vai trò đội ngũ. Nếu bỏ trống, Shiori dùng các vai trò có quyền kiểm duyệt.',
      Locale.th: 'บทบาททีมงาน ถ้าไม่ระบุ Shiori จะใช้บทบาทที่มีสิทธิ์ผู้ดูแล',
      Locale.zhTw: '團隊身分組。沒有的話，Shiori 會使用有管理權限的身分組。',
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
