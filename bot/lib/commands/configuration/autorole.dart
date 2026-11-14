import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';

final autorole = ChatGroup('autorole', 'Manage the role new members get.',
    localizedDescriptions: {
      Locale.da: 'Administrer rollen, som nye medlemmer får.',
      Locale.de: 'Die Rolle für neue Mitglieder verwalten.',
      Locale.enUs: 'Manage the role new members get.',
      Locale.esEs: 'Gestionar el rol que reciben los nuevos miembros.',
      Locale.fr: 'Gérer le rôle donné aux nouveaux membres.',
      Locale.ru: 'Управлять ролью для новых участников.',
      Locale.hi: 'नए सदस्यों को मिलने वाला रोल प्रबंधित करें।',
      Locale.zhCn: '管理新成员获得的身份组。',
      Locale.ja: '新しいメンバーに付与するロールを管理します。',
      Locale.ko: '새 멤버에게 줄 역할을 관리합니다.',
      Locale.es419: 'Gestionar el rol que reciben los nuevos miembros.',
      Locale.ptBr: 'Gerenciar o cargo que novos membros recebem.',
      Locale.tr: 'Yeni üyelerin aldığı rolü yönet.',
      Locale.pl: 'Zarządzaj rolą, którą dostają nowi członkowie.',
      Locale.it: 'Gestisci il ruolo che ricevono i nuovi membri.',
      Locale.uk: 'Керувати роллю, яку отримують нові учасники.',
      Locale.vi: 'Quản lý vai trò thành viên mới nhận được.',
      Locale.th: 'จัดการบทบาทที่สมาชิกใหม่ได้รับ',
      Locale.zhTw: '管理新成員會獲得的身分組。'
    },
    checks: [
      GuildCheck.all(),
    ],
    children: [
      ChatCommand('set', 'Set the role new members get.',
          localizedDescriptions: {
            Locale.da: 'Indstil rollen, som nye medlemmer får.',
            Locale.de: 'Die Rolle für neue Mitglieder festlegen.',
            Locale.enUs: 'Set the role new members get.',
            Locale.esEs: 'Establecer el rol que reciben los nuevos miembros.',
            Locale.fr: 'Définir le rôle donné aux nouveaux membres.',
            Locale.ru: 'Задать роль для новых участников.',
            Locale.hi: 'नए सदस्यों को मिलने वाला रोल सेट करें।',
            Locale.zhCn: '设置新成员获得的身份组。',
            Locale.ja: '新しいメンバーに付与するロールを設定します。',
            Locale.ko: '새 멤버에게 줄 역할을 설정합니다.',
            Locale.es419: 'Establecer el rol que reciben los nuevos miembros.',
            Locale.ptBr: 'Definir o cargo que novos membros recebem.',
            Locale.tr: 'Yeni üyelerin aldığı rolü ayarla.',
            Locale.pl: 'Ustaw rolę, którą dostają nowi członkowie.',
            Locale.it: 'Imposta il ruolo che ricevono i nuovi membri.',
            Locale.uk: 'Встановити роль, яку отримують нові учасники.',
            Locale.vi: 'Đặt vai trò thành viên mới nhận được.',
            Locale.th: 'ตั้งบทบาทที่สมาชิกใหม่ได้รับ',
            Locale.zhTw: '設定新成員會獲得的身分組。'
          },
          checks: [
            PermissionsCheck(Permissions.manageGuild, allowsOverrides: false)
          ], (ChatContext context,
              @Description('The role new members get.', {
                Locale.da: 'Rollen, som nye medlemmer får.',
                Locale.de: 'Die Rolle, die neue Mitglieder bekommen.',
                Locale.enUs: 'The role new members get.',
                Locale.esEs: 'El rol que reciben los nuevos miembros.',
                Locale.fr: 'Le rôle donné aux nouveaux membres.',
                Locale.ru: 'Роль, которую получают новые участники.',
                Locale.hi: 'वह रोल जो नए सदस्यों को मिलता है।',
                Locale.zhCn: '新成员获得的身份组。',
                Locale.ja: '新しいメンバーに付与するロール。',
                Locale.ko: '새 멤버가 받을 역할.',
                Locale.es419: 'El rol que reciben los nuevos miembros.',
                Locale.ptBr: 'O cargo que novos membros recebem.',
                Locale.tr: 'Yeni üyelerin aldığı rol.',
                Locale.pl: 'Rola, którą dostają nowi członkowie.',
                Locale.it: 'Il ruolo che ricevono i nuovi membri.',
                Locale.uk: 'Роль, яку отримують нові учасники.',
                Locale.vi: 'Vai trò thành viên mới nhận được.',
                Locale.th: 'บทบาทที่สมาชิกใหม่ได้รับ',
                Locale.zhTw: '新成員會獲得的身分組。'
              })
              Role? role) async {
        final connection = await getMySqlConnection();

        try {
          var results = await connection.query(
              'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
              [context.guild?.id.toString()]);

          if (results.first['autorole'] == null && role == null) {
            await replyError(context, 'autorole_norole');
            return;
          } else {
            final bot =
                await context.guild?.members.get(context.client.user.id);
            final botRoles = await bot?.roles.map((r) => r.get()).wait;
            botRoles?.sort((a, b) => a.position.compareTo(b.position));

            if (botRoles?.last != null &&
                role?.position != null &&
                role?.id != null) {
              if (role!.position < botRoles!.last.position) {
                await connection.query(
                    'UPDATE `guilds` SET `autorole` = ? WHERE `id` = ? LIMIT 1;',
                    [role.id.toString(), context.guild?.id.toString()]);

                await replySuccess(context, 'autorole_set',
                    replace: {'&autorole': '<@&${role.id}>'},
                    level: ResponseLevel.hint);
                return;
              } else {
                await replyError(context, 'autorole_highest');
                return;
              }
            }
          }
        } catch (e) {
          print('Error in autorole command: $e');
          await replyError(context, 'autorole_error',
              details: codeBlock(e.toString(), 'sh'));
          return;
        }
      }),
      ChatCommand('show', 'Show the current autorole.', localizedDescriptions: {
        Locale.da: 'Vis den nuværende autorolle.',
        Locale.de: 'Die aktuelle Autorole anzeigen.',
        Locale.enUs: 'Show the current autorole.',
        Locale.esEs: 'Mostrar el autorol actual.',
        Locale.fr: 'Afficher le rôle automatique actuel.',
        Locale.ru: 'Показать текущую автороль.',
        Locale.hi: 'मौजूदा ऑटोरोल दिखाएँ।',
        Locale.zhCn: '显示当前的自动身份组。',
        Locale.ja: '現在の自動ロールを表示します。',
        Locale.ko: '현재 자동 역할을 표시합니다.',
        Locale.es419: 'Mostrar el autorol actual.',
        Locale.ptBr: 'Mostrar o cargo automático atual.',
        Locale.tr: 'Şu anki otomatik rolü göster.',
        Locale.pl: 'Pokaż obecną automatyczną rolę.',
        Locale.it: 'Mostra il ruolo automatico attuale.',
        Locale.uk: 'Показати поточну автороль.',
        Locale.vi: 'Xem vai trò tự động hiện tại.',
        Locale.th: 'ดูบทบาทอัตโนมัติปัจจุบัน',
        Locale.zhTw: '顯示目前的自動身分組。'
      }, (ChatContext context) async {
        final connection = await getMySqlConnection();

        var results = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [context.guild?.id.toString()]);

        if (results.first['autorole'] == null) {
          await replyError(context, 'autorole_norole');
          return;
        } else {
          if (results.first['autorole'] != null) {
            final autoroleId = results.first['autorole'];
            final autoroleIdAsSnowflake = Snowflake(int.tryParse(autoroleId)!);
            final autorole =
                await context.guild?.roles.get(autoroleIdAsSnowflake);

            await replySuccess(context, 'autorole_current',
                replace: {'&autorole': '<@&${autorole?.id}>'},
                level: ResponseLevel.hint);
            return;
          }
        }
      }),
      ChatCommand('reset', 'Remove the autorole.', localizedDescriptions: {
        Locale.da: 'Fjern autorollen.',
        Locale.de: 'Die Autorole entfernen.',
        Locale.enUs: 'Remove the autorole.',
        Locale.esEs: 'Quitar el autorol.',
        Locale.fr: 'Retirer le rôle automatique.',
        Locale.ru: 'Убрать автороль.',
        Locale.hi: 'ऑटोरोल हटाएँ।',
        Locale.zhCn: '移除自动身份组。',
        Locale.ja: '自動ロールを解除します。',
        Locale.ko: '자동 역할을 해제합니다.',
        Locale.es419: 'Quitar el autorol.',
        Locale.ptBr: 'Remover o cargo automático.',
        Locale.tr: 'Otomatik rolü kaldır.',
        Locale.pl: 'Usuń automatyczną rolę.',
        Locale.it: 'Rimuovi il ruolo automatico.',
        Locale.uk: 'Прибрати автороль.',
        Locale.vi: 'Gỡ vai trò tự động.',
        Locale.th: 'ลบบทบาทอัตโนมัติ',
        Locale.zhTw: '移除自動身分組。'
      }, (ChatContext context) async {
        final connection = await getMySqlConnection();

        var results = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [context.guild?.id.toString()]);

        if (results.first['autorole'] == null) {
          await replyError(context, 'autorole_norole');
          return;
        } else {
          try {
            await connection.query(
                'UPDATE `guilds` SET `autorole` = ? WHERE `id` = ? LIMIT 1;',
                [null, context.guild?.id.toString()]);

            await replySuccess(context, 'autorole_reset',
                level: ResponseLevel.hint);
            return;
          } catch (e) {
            print('Error in autorole command: $e');
            await replyError(context, 'autorole_error',
                details: codeBlock(e.toString(), 'sh'));
            return;
          }
        }
      }, checks: [
        PermissionsCheck(Permissions.manageGuild, allowsOverrides: false)
      ])
    ]);
