import 'package:collection/collection.dart';
import 'package:dotenv/dotenv.dart';
import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

String announcementTypeToString(String type) => type;

const announcementTypeConverter = SimpleConverter.fixed(
  elements: ['Join', 'Leave'],
  stringify: announcementTypeToString,
);

var env = DotEnv(includePlatformEnvironment: true)..load();

final announcements = ChatGroup('announcements', 'Manage server announcements.',
    localizedDescriptions: {
      Locale.da: 'Administrer servermeddelelser.',
      Locale.de: 'Server-Ankündigungen verwalten.',
      Locale.enUs: 'Manage server announcements.',
      Locale.esEs: 'Gestionar anuncios del servidor.',
      Locale.fr: 'Gérer les annonces du serveur.',
      Locale.ru: 'Управление объявлениями сервера.',
      Locale.hi: 'सर्वर घोषणाओं का प्रबंधन करें।',
      Locale.zhCn: '管理服务器公告。',
      Locale.ja: 'サーバーのお知らせを管理する。',
      Locale.ko: '서버 공지사항 관리.'
    },
    checks: [
      GuildCheck.all(),
    ],
    children: [
      ChatCommand(
          'set', 'Set up or update the announcement channel and message.',
          localizedDescriptions: {
            Locale.da: 'Opsæt eller opdater meddelelseskanal og besked.',
            Locale.de:
                'Ankündigungskanal und Nachricht einrichten oder aktualisieren.',
            Locale.enUs:
                'Set up or update the announcement channel and message.',
            Locale.esEs:
                'Configurar o actualizar el canal de anuncios y el mensaje.',
            Locale.fr:
                'Configurer ou mettre à jour le canal d\'annonce et le message.',
            Locale.ru:
                'Настройка или обновление канала для объявлений и сообщения.',
            Locale.hi: 'घोषणा चैनल और संदेश सेट अप करें या अपडेट करें।',
            Locale.zhCn: '设置或更新公告频道和消息。',
            Locale.ja: 'アナウンスチャンネルとメッセージを設定または更新する。',
            Locale.ko: '공지 채널 및 메시지를 설정하거나 업데이트합니다.'
          },
          checks: [
            PermissionsCheck(Permissions.manageChannels, allowsOverrides: false)
          ], (ChatContext context,
              @Description(
                  'Whether this setting is for members joining or leaving.', {
                Locale.da:
                    'Om denne indstilling gælder for medlemmer, der tiltræder eller forlader.',
                Locale.de:
                    'Ob diese Einstellung für Mitglieder gilt, die beitreten oder verlassen.',
                Locale.enUs:
                    'Whether this setting is for members joining or leaving.',
                Locale.esEs:
                    'Si esta configuración es para miembros que se unen o se van.',
                Locale.fr:
                    'Si ce paramètre concerne les membres qui rejoignent ou qui quittent.',
                Locale.ru:
                    'Является ли эта настройка для членов, присоединяющихся или покидающих.',
                Locale.hi:
                    'यह सेटिंग सदस्यों के जुड़ने या छोड़ने के लिए है या नहीं।',
                Locale.zhCn: '这个设置是否适用于加入或离开的成员。',
                Locale.ja: 'この設定がメンバー加入または離脱に関係するかどうか。',
                Locale.ko: '이 설정이 회원 가입 또는 탈퇴에 해당하는지 여부.'
              })
              @UseConverter(announcementTypeConverter)
              String type,
              @Description('The channel where announcements will be sent.', {
                Locale.da: 'Kanal, hvor meddelelser vil blive sendt.',
                Locale.de: 'Der Kanal, in dem Ankündigungen gesendet werden.',
                Locale.enUs: 'The channel where announcements will be sent.',
                Locale.esEs: 'El canal donde se enviarán los anuncios.',
                Locale.fr: 'Le canal où les annonces seront envoyées.',
                Locale.ru: 'Канал, куда будут отправляться объявления.',
                Locale.hi: 'वह चैनल जहाँ घोषणाएँ भेजी जाएँगी।',
                Locale.zhCn: '发送公告的频道。',
                Locale.ja: 'お知らせが送られるチャンネル。',
                Locale.ko: '공지가 전송될 채널.'
              })
              GuildTextChannel channel,
              @Description('Announce msg. &user, &server, &mention repl.', {
                Locale.da: 'Medd til annonc. &user, &server, &mention erst.',
                Locale.de: 'Nachricht f. Ankünd. &user, &server, &mention.',
                Locale.enUs: 'Announce msg. &user, &server, &mention repl.',
                Locale.esEs: 'Msg anuncio. &user, &server, &mention cambiados.',
                Locale.fr: 'Msg annonce. &user, &server, &mention remplacés.',
                Locale.ru: 'Объявление. &user, &server, &mention заменены.',
                Locale.hi: 'घोषणा संदेश. &user, &server, &mention बदले.',
                Locale.zhCn: '公告消息。&user, &server, &mention 替换。',
                Locale.ja: '発表メッセージ。&user, &server, &mention置換。',
                Locale.ko: '공지 메시지. &user, &server, &mention 대체.'
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

          await context.respond(MessageBuilder(embeds: [
            EmbedBuilder(
                color: DiscordColor.parseHexString('#6dbe33'),
                description:
                    (await getString(context.user, 'announcements_set'))
                        .replaceAll('&type', type))
          ]));
          return;
        } catch (e) {
          print('Error while setting announcements: $e');
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'announcements_error') +
                            codeBlock(e.toString(), 'sh'))
              ]),
              level: ResponseLevel.hint);
          return;
        } finally {
          await connection.close();
        }
      }),
      ChatCommand('show', 'Displays the current announcement settings.',
          localizedDescriptions: {
            Locale.da: 'Vis de nuværende indstillinger for meddelelser.',
            Locale.de:
                'Zeigt die aktuellen Einstellungen für Ankündigungen an.',
            Locale.enUs: 'Displays the current announcement settings.',
            Locale.esEs: 'Mostrar la configuración actual de anuncios.',
            Locale.fr: 'Afficher les paramètres actuels des annonces.',
            Locale.ru: 'Показать текущие настройки объявлений.',
            Locale.hi: 'वर्तमान घोषणा सेटिंग्स दिखाएं।',
            Locale.zhCn: '显示当前的公告设置。',
            Locale.ja: '現在の告知設定を表示する。',
            Locale.ko: '현재 공지 설정 표시.'
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
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'announcements_none'))
              ]),
              level: ResponseLevel.hint);
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
                color: DiscordColor.parseHexString('#6dbe33'),
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
            await context.respond(
                MessageBuilder(embeds: [
                  EmbedBuilder(
                      color: DiscordColor.parseHexString('#c41111'),
                      title: await getString(context.user, 'global_error'),
                      description:
                          await getString(context.user, 'announcements_error') +
                              codeBlock(e.toString(), 'sh'))
                ]),
                level: ResponseLevel.hint);
            return;
          } finally {
            await connection.close();
          }
        }
      }),
      ChatCommand('reset', 'Reset the announcement settings.',
          localizedDescriptions: {
            Locale.da: 'Nulstil indstillingerne for meddelelser.',
            Locale.de: 'Setze die Ankündigungseinstellungen zurück.',
            Locale.enUs: 'Reset the announcement settings.',
            Locale.esEs: 'Restablecer la configuración de anuncios.',
            Locale.fr: 'Réinitialiser les paramètres des annonces.',
            Locale.ru: 'Сбросить настройки объявлений.',
            Locale.hi: 'घोषणा सेटिंग्स रीसेट करें।',
            Locale.zhCn: '重置公告设置。',
            Locale.ja: '告知設定をリセットする。',
            Locale.ko: '공지 설정 초기화.'
          },
          checks: [
            PermissionsCheck(Permissions.manageChannels, allowsOverrides: false)
          ], (ChatContext context,
              @Description(
                  'Whether this setting is for members joining or leaving.', {
                Locale.da:
                    'Om denne indstilling gælder for medlemmer, der tiltræder eller forlader.',
                Locale.de:
                    'Ob diese Einstellung für Mitglieder gilt, die beitreten oder verlassen.',
                Locale.enUs:
                    'Whether this setting is for members joining or leaving.',
                Locale.esEs:
                    'Si esta configuración es para miembros que se unen o se van.',
                Locale.fr:
                    'Si ce paramètre concerne les membres qui rejoignent ou qui quittent.',
                Locale.ru:
                    'Является ли эта настройка для членов, присоединяющихся или покидающих.',
                Locale.hi:
                    'यह सेटिंग सदस्यों के जुड़ने या छोड़ने के लिए है या नहीं।',
                Locale.zhCn: '这个设置是否适用于加入或离开的成员。',
                Locale.ja: 'この設定がメンバー加入または離脱に関係するかどうか。',
                Locale.ko: '이 설정이 회원 가입 또는 탈퇴에 해당하는지 여부.'
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

          await context.respond(MessageBuilder(embeds: [
            EmbedBuilder(
                color: DiscordColor.parseHexString('#6dbe33'),
                description:
                    (await getString(context.user, 'announcements_reset'))
                        .replaceAll('&type', type))
          ]));
        } catch (e) {
          print('Error while resetting announcements: $e');
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'announcements_error') +
                            codeBlock(e.toString(), 'sh'))
              ]),
              level: ResponseLevel.hint);
          return;
        } finally {
          await connection.close();
        }
      })
    ]);
