import 'package:collection/collection.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const announcementTypeConverter = SimpleConverter.fixed(
  elements: ['Join', 'Leave'],
  stringify: stringifyOption,
);

final announcements = ChatGroup(
    'announcements', 'Manage the welcome and goodbye messages.',
    localizedDescriptions: {
      Locale.da: 'Administrer velkomst- og farvelbeskeder.',
      Locale.de: 'Willkommens- und Abschiedsnachrichten verwalten.',
      Locale.enUs: 'Manage the welcome and goodbye messages.',
      Locale.esEs: 'Gestionar los mensajes de bienvenida y despedida.',
      Locale.fr: 'Gérer les messages de bienvenue et d\'au revoir.',
      Locale.ru: 'Управлять приветствиями и прощаниями.',
      Locale.hi: 'स्वागत और विदाई संदेश प्रबंधित करें।',
      Locale.zhCn: '管理欢迎和告别消息。',
      Locale.ja: '歓迎メッセージとお別れメッセージを管理します。',
      Locale.ko: '환영 및 작별 메시지를 관리합니다.'
    },
    checks: [
      GuildCheck.all(),
    ],
    children: [
      ChatCommand('set', 'Set the channel and message for joins or leaves.',
          localizedDescriptions: {
            Locale.da:
                'Indstil kanal og besked for nye eller forladte medlemmer.',
            Locale.de:
                'Kanal und Nachricht für Beitritte oder Abgänge festlegen.',
            Locale.enUs: 'Set the channel and message for joins or leaves.',
            Locale.esEs:
                'Establecer el canal y el mensaje para llegadas o salidas.',
            Locale.fr:
                'Définir le salon et le message pour les arrivées ou les départs.',
            Locale.ru: 'Задать канал и сообщение для входа или выхода.',
            Locale.hi: 'जुड़ने या जाने के लिए चैनल और संदेश सेट करें।',
            Locale.zhCn: '设置加入或离开时的频道和消息。',
            Locale.ja: '参加時または退出時のチャンネルとメッセージを設定します。',
            Locale.ko: '입장 또는 퇴장 시 채널과 메시지를 설정합니다.'
          },
          checks: [
            PermissionsCheck(Permissions.manageChannels, allowsOverrides: false)
          ], (ChatContext context,
              @Description('Whether it is about members joining or leaving.', {
                Locale.da: 'Om det gælder medlemmer, der kommer eller går.',
                Locale.de:
                    'Ob es um Mitglieder geht, die beitreten oder gehen.',
                Locale.enUs: 'Whether it is about members joining or leaving.',
                Locale.esEs: 'Si se trata de miembros que llegan o se van.',
                Locale.fr:
                    'S\'il s\'agit des membres qui arrivent ou qui partent.',
                Locale.ru: 'Для входящих или уходящих участников.',
                Locale.hi: 'जुड़ने वाले या जाने वाले सदस्यों के लिए।',
                Locale.zhCn: '针对加入还是离开的成员。',
                Locale.ja: '参加するメンバーか退出するメンバーか。',
                Locale.ko: '입장하는 멤버인지 퇴장하는 멤버인지.'
              })
              @UseConverter(announcementTypeConverter)
              String type,
              @Description('The channel the message is sent to.', {
                Locale.da: 'Kanalen, beskeden sendes til.',
                Locale.de: 'Der Kanal, in den die Nachricht geschickt wird.',
                Locale.enUs: 'The channel the message is sent to.',
                Locale.esEs: 'El canal al que se envía el mensaje.',
                Locale.fr: 'Le salon où le message est envoyé.',
                Locale.ru: 'Канал, куда отправляется сообщение.',
                Locale.hi: 'वह चैनल जिसमें संदेश भेजा जाता है।',
                Locale.zhCn: '发送消息的频道。',
                Locale.ja: 'メッセージを送るチャンネル。',
                Locale.ko: '메시지를 보낼 채널.'
              })
              GuildTextChannel channel,
              @Description(
                  'The message. &user, &server and &mention are replaced.', {
                Locale.da: 'Beskeden. &user, &server og &mention erstattes.',
                Locale.de:
                    'Die Nachricht. &user, &server und &mention werden ersetzt.',
                Locale.enUs:
                    'The message. &user, &server and &mention are replaced.',
                Locale.esEs:
                    'El mensaje. Se reemplazan &user, &server y &mention.',
                Locale.fr:
                    'Le message. &user, &server et &mention sont remplacés.',
                Locale.ru: 'Сообщение. &user, &server и &mention заменяются.',
                Locale.hi:
                    'संदेश। &user, &server और &mention बदल दिए जाते हैं।',
                Locale.zhCn: '消息内容。&user、&server 和 &mention 会被替换。',
                Locale.ja: 'メッセージ。&user、&server、&mention は置き換えられます。',
                Locale.ko: '메시지. &user, &server, &mention은 바뀝니다.'
              })
              String message) async {
        final connection = await getMySqlConnection();

        try {
          String channelColumn =
              type == 'Join' ? 'welcome_channel' : 'bye_channel';
          String messageColumn = type == 'Join' ? 'welcome_msg' : 'bye_msg';

          await connection.query(
              'UPDATE `guilds` SET `$channelColumn` = ?, `$messageColumn` = ? WHERE `id` = ? LIMIT 1;',
              [channel.id.toString(), message, context.guild?.id.toString()]);

          await replySuccess(context, 'announcements_set',
              replace: {'&type': type});
          return;
        } catch (e) {
          print('Error while setting announcements: $e');
          await replyError(context, 'announcements_error',
              details: codeBlock(e.toString(), 'sh'));
          return;
        } finally {
          await connection.close();
        }
      }),
      ChatCommand('show', 'Show the current welcome and goodbye settings.',
          localizedDescriptions: {
            Locale.da: 'Vis de nuværende indstillinger for velkomst og farvel.',
            Locale.de:
                'Die aktuellen Einstellungen für Willkommen und Abschied anzeigen.',
            Locale.enUs: 'Show the current welcome and goodbye settings.',
            Locale.esEs:
                'Mostrar los ajustes actuales de bienvenida y despedida.',
            Locale.fr:
                'Afficher les paramètres actuels de bienvenue et d\'au revoir.',
            Locale.ru: 'Показать текущие настройки приветствия и прощания.',
            Locale.hi: 'मौजूदा स्वागत और विदाई सेटिंग दिखाएँ।',
            Locale.zhCn: '显示当前的欢迎和告别设置。',
            Locale.ja: '現在の歓迎とお別れの設定を表示します。',
            Locale.ko: '현재 환영 및 작별 설정을 표시합니다.'
          }, (ChatContext context) async {
        final connection = await getMySqlConnection();

        var results = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [context.guild?.id.toString()]);

        Snowflake? welcomeId = results.first['welcome_channel'] != null
            ? Snowflake.parse(results.first['welcome_channel'])
            : null;
        String? welcomeMsg = results.first['welcome_msg']?.toString();

        Snowflake? byeId = results.first['bye_channel'] != null
            ? Snowflake.parse(results.first['bye_channel'])
            : null;
        String? byeMsg = results.first['bye_msg']?.toString();

        bool hasAnySettings =
            [welcomeId, welcomeMsg, byeId, byeMsg].any((v) => v != null);

        if (!hasAnySettings) {
          await replyError(context, 'announcements_none');
          return;
        } else {
          try {
            final channels =
                await (await context.guild?.get())?.fetchChannels();

            final welcomeChannel = welcomeId != null
                ? channels?.firstWhereOrNull((c) => c.id == welcomeId)
                : null;
            final byeChannel = byeId != null
                ? channels?.firstWhereOrNull((c) => c.id == byeId)
                : null;

            await context.respond(MessageBuilder(embeds: [
              EmbedBuilder(
                color: DiscordColor.parseHexString(successColor),
                fields: [
                  EmbedFieldBuilder(
                    name: ' ',
                    value: '''
                      ${welcomeChannel?.mention ?? italic('N/A')}
                      ${welcomeMsg != null ? codeBlock(welcomeMsg) : italic('N/A')}
                      ${byeChannel?.mention ?? italic('N/A')}
                      ${byeMsg != null ? codeBlock(byeMsg) : italic('N/A')}
                    ''',
                    isInline: false,
                  ),
                ],
              ),
            ]));
          } catch (e) {
            print('Error while showing announcements: $e');
            await replyError(context, 'announcements_error',
                details: codeBlock(e.toString(), 'sh'));
            return;
          } finally {
            await connection.close();
          }
        }
      }),
      ChatCommand('reset', 'Reset the welcome or goodbye message.',
          localizedDescriptions: {
            Locale.da: 'Nulstil velkomst- eller farvelbeskeden.',
            Locale.de: 'Die Willkommens- oder Abschiedsnachricht zurücksetzen.',
            Locale.enUs: 'Reset the welcome or goodbye message.',
            Locale.esEs: 'Restablecer el mensaje de bienvenida o despedida.',
            Locale.fr: 'Réinitialiser le message de bienvenue ou d\'au revoir.',
            Locale.ru: 'Сбросить приветствие или прощание.',
            Locale.hi: 'स्वागत या विदाई संदेश रीसेट करें।',
            Locale.zhCn: '重置欢迎或告别消息。',
            Locale.ja: '歓迎メッセージまたはお別れメッセージをリセットします。',
            Locale.ko: '환영 또는 작별 메시지를 초기화합니다.'
          },
          checks: [
            PermissionsCheck(Permissions.manageChannels, allowsOverrides: false)
          ], (ChatContext context,
              @Description('Whether it is about members joining or leaving.', {
                Locale.da: 'Om det gælder medlemmer, der kommer eller går.',
                Locale.de:
                    'Ob es um Mitglieder geht, die beitreten oder gehen.',
                Locale.enUs: 'Whether it is about members joining or leaving.',
                Locale.esEs: 'Si se trata de miembros que llegan o se van.',
                Locale.fr:
                    'S\'il s\'agit des membres qui arrivent ou qui partent.',
                Locale.ru: 'Для входящих или уходящих участников.',
                Locale.hi: 'जुड़ने वाले या जाने वाले सदस्यों के लिए।',
                Locale.zhCn: '针对加入还是离开的成员。',
                Locale.ja: '参加するメンバーか退出するメンバーか。',
                Locale.ko: '입장하는 멤버인지 퇴장하는 멤버인지.'
              })
              @UseConverter(announcementTypeConverter)
              String type) async {
        final connection = await getMySqlConnection();

        try {
          String channelColumn =
              type == 'Join' ? 'welcome_channel' : 'bye_channel';
          String messageColumn = type == 'Join' ? 'welcome_msg' : 'bye_msg';

          await connection.query(
              'UPDATE `guilds` SET `$channelColumn` = ?, `$messageColumn` = ? WHERE `id` = ? LIMIT 1;',
              [null, null, context.guild?.id.toString()]);

          await replySuccess(context, 'announcements_reset',
              replace: {'&type': type});
        } catch (e) {
          print('Error while resetting announcements: $e');
          await replyError(context, 'announcements_error',
              details: codeBlock(e.toString(), 'sh'));
          return;
        } finally {
          await connection.close();
        }
      })
    ]);
