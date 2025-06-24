import 'package:dotenv/dotenv.dart';
import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

var env = DotEnv(includePlatformEnvironment: true)..load();

final autorole = ChatGroup('autorole', 'Set an autorole for your server.',
    localizedDescriptions: {
      Locale.da: 'Sæt en automatisk rolle for din server.',
      Locale.de: 'Stelle eine Autorolle für deinen Server ein.',
      Locale.enUs: 'Set an autorole for your server.',
      Locale.esEs: 'Establece un autorol para tu servidor.',
      Locale.fr: 'Définir un rôle automatique pour votre serveur.',
      Locale.ru: 'Установите автороль для вашего сервера.',
      Locale.hi: 'अपने सर्वर के लिए ऑटोरोल सेट करें।',
      Locale.zhCn: '为您的服务器设置自动角色。',
      Locale.ja: 'サーバーのオートロールを設定する。',
      Locale.ko: '서버에 대한 자동 역할 설정하기.'
    },
    checks: [
      GuildCheck.all(),
    ],
    children: [
      ChatCommand('set', 'Set an autorole for your server.',
          localizedDescriptions: {
            Locale.da: 'Sæt en automatisk rolle for din server.',
            Locale.de: 'Stelle eine Autorolle für deinen Server ein.',
            Locale.enUs: 'Set an autorole for your server.',
            Locale.esEs: 'Establece un autorol para tu servidor.',
            Locale.fr: 'Définir un rôle automatique pour votre serveur.',
            Locale.ru: 'Установите автороль для вашего сервера.',
            Locale.hi: 'अपने सर्वर के लिए ऑटोरोल सेट करें।',
            Locale.zhCn: '为您的服务器设置自动角色。',
            Locale.ja: 'サーバーのオートロールを設定する。',
            Locale.ko: '서버에 대한 자동 역할 설정하기.'
          },
          checks: [
            PermissionsCheck(Permissions.manageGuild, allowsOverrides: false)
          ], (ChatContext context,
              @Description('The role you want to set as autorole.', {
                Locale.da:
                    'Den rolle, du ønsker at sætte som automatisk rolle.',
                Locale.de:
                    'Die Rolle, die du als Autorolle festlegen möchtest.',
                Locale.enUs: 'The role you want to set as autorole.',
                Locale.esEs: 'El rol que deseas establecer como autorol.',
                Locale.fr:
                    'Le rôle que vous souhaitez définir comme rôle automatique.',
                Locale.ru:
                    'Роль, которую вы хотите установить в качестве автороли.',
                Locale.hi:
                    'वह भूमिका जिसे आप ऑटोरोल के रूप में सेट करना चाहते हैं।',
                Locale.zhCn: '你想设置为自动分配的角色。',
                Locale.ja: 'オートロールとして設定したいロール。',
                Locale.ko: '자동 역할로 설정하고자 하는 역할.'
              })
              Role? role) async {
        final connection = await getMySqlConnection();

        try {
          var results = await connection.query(
              'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
              [context.guild?.id.toString()]);

          if (results.first['autorole'] == null && role == null) {
            await context.respond(
                MessageBuilder(embeds: [
                  EmbedBuilder(
                      color: DiscordColor.parseHexString('#c41111'),
                      title: await getString(context.user, 'global_error'),
                      description:
                          await getString(context.user, 'autorole_norole'))
                ]),
                level: ResponseLevel.hint);
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

                await context.respond(MessageBuilder(embeds: [
                  EmbedBuilder(
                      color: DiscordColor.parseHexString('#6dbe33'),
                      description:
                          (await getString(context.user, 'autorole_set'))
                              .replaceAll('&autorole', '<@&${role.id}>'))
                ]));
                return;
              } else {
                await context.respond(
                    MessageBuilder(embeds: [
                      EmbedBuilder(
                          color: DiscordColor.parseHexString('#c41111'),
                          title: await getString(context.user, 'global_error'),
                          description:
                              await getString(context.user, 'autorole_highest'))
                    ]),
                    level: ResponseLevel.hint);
                return;
              }
            }
          }
        } catch (e) {
          print('Error in autorole command: $e');
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'autorole_error') +
                            codeBlock(e.toString(), 'sh'))
              ]),
              level: ResponseLevel.hint);
          return;
        }
      }),
      ChatCommand('show', 'Displays the current autorole.',
          localizedDescriptions: {
            Locale.da: 'Viser den nuværende automatisk rolle.',
            Locale.de: 'Zeigt die aktuelle Autorolle an.',
            Locale.enUs: 'Displays the current autorole.',
            Locale.esEs: 'Muestra el autorol actual.',
            Locale.fr: 'Affiche le rôle automatique actuel.',
            Locale.ru: 'Показывает текущую автороль.',
            Locale.hi: 'वर्तमान ऑटोरोल प्रदर्शित करता है।',
            Locale.zhCn: '显示当前的自动角色。',
            Locale.ja: '現在の自動ロールを表示します。',
            Locale.ko: '현재 자동 역할을 표시합니다.'
          }, (ChatContext context) async {
        final connection = await getMySqlConnection();

        var results = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [context.guild?.id.toString()]);

        if (results.first['autorole'] == null) {
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'autorole_norole'))
              ]),
              level: ResponseLevel.hint);
          return;
        } else {
          if (results.first['autorole'] != null) {
            final autoroleId = results.first['autorole'];
            final autoroleIdAsSnowflake = Snowflake(int.tryParse(autoroleId)!);
            final autorole =
                await context.guild?.roles.get(autoroleIdAsSnowflake);

            await context.respond(MessageBuilder(embeds: [
              EmbedBuilder(
                  color: DiscordColor.parseHexString('#6dbe33'),
                  description:
                      (await getString(context.user, 'autorole_current'))
                          .replaceAll(
                    '&autorole',
                    '<@&${autorole?.id}>',
                  ))
            ]));
            return;
          }
        }
      }),
      ChatCommand(
          'reset', 'Use this option if you want to reset the current autorole.',
          localizedDescriptions: {
            Locale.da:
                'Brug denne mulighed, hvis du ønsker at nulstille den nuværende automatisk rolle.',
            Locale.de:
                'Verwende diese Option, wenn du die aktuelle Autorolle zurücksetzen möchtest.',
            Locale.enUs:
                'Use this option if you want to reset the current autorole.',
            Locale.esEs:
                'Usa esta opción si quieres restablecer el autorol actual.',
            Locale.fr:
                'Utilisez cette option si vous souhaitez réinitialiser l\'autorole actuelle.',
            Locale.ru:
                'Используйте эту опцию, если хотите сбросить текущую автороль.',
            Locale.hi:
                'यदि आप वर्तमान ऑटोरोल को रीसेट करना चाहते हैं तो इस विकल्प का उपयोग करें।',
            Locale.zhCn: '如果您想重置当前的自动角色，请使用此选项。',
            Locale.ja: 'このオプションを使用して、現在の自動ロールをリセットします。',
            Locale.ko: '현재 자동 역할을 초기화하려면 이 옵션을 사용하세요.'
          }, (ChatContext context) async {
        final connection = await getMySqlConnection();

        var results = await connection.query(
            'SELECT * FROM `guilds` WHERE `id` = ? LIMIT 1;',
            [context.guild?.id.toString()]);

        if (results.first['autorole'] == null) {
          await context.respond(
              MessageBuilder(embeds: [
                EmbedBuilder(
                    color: DiscordColor.parseHexString('#c41111'),
                    title: await getString(context.user, 'global_error'),
                    description:
                        await getString(context.user, 'autorole_norole'))
              ]),
              level: ResponseLevel.hint);
          return;
        } else {
          try {
            await connection.query(
                'UPDATE `guilds` SET `autorole` = ? WHERE `id` = ? LIMIT 1;',
                [null, context.guild?.id.toString()]);

            await context.respond(MessageBuilder(embeds: [
              EmbedBuilder(
                  color: DiscordColor.parseHexString('#6dbe33'),
                  description: await getString(context.user, 'autorole_reset'))
            ]));
            return;
          } catch (e) {
            print('Error in autorole command: $e');
            await context.respond(
                MessageBuilder(embeds: [
                  EmbedBuilder(
                      color: DiscordColor.parseHexString('#c41111'),
                      title: await getString(context.user, 'global_error'),
                      description:
                          await getString(context.user, 'autorole_error') +
                              codeBlock(e.toString(), 'sh'))
                ]),
                level: ResponseLevel.hint);
            return;
          }
        }
      }, checks: [
        PermissionsCheck(Permissions.manageGuild, allowsOverrides: false)
      ])
    ]);
