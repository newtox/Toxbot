import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/utils.dart';

final invite = ChatCommand(
  'invite',
  'Get the link to add Toxbot to your server.',
  localizedDescriptions: {
    Locale.da: 'Få linket til at tilføje Toxbot til din server.',
    Locale.de: 'Den Link holen, um Toxbot zu deinem Server hinzuzufügen.',
    Locale.enUs: 'Get the link to add Toxbot to your server.',
    Locale.esEs: 'Obtener el enlace para añadir Toxbot a tu servidor.',
    Locale.fr: 'Obtenir le lien pour ajouter Toxbot à ton serveur.',
    Locale.ru: 'Получить ссылку, чтобы добавить Toxbot на свой сервер.',
    Locale.hi: 'Toxbot को अपने सर्वर में जोड़ने का लिंक पाएँ।',
    Locale.zhCn: '获取将 Toxbot 添加到你服务器的链接。',
    Locale.ja: 'Toxbot をサーバーに追加するリンクを表示します。',
    Locale.ko: 'Toxbot을 서버에 추가하는 링크를 받습니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    final buttons = [
      ButtonBuilder.link(
          label: await getString(user, 'info_invite'),
          url: inviteUrl(context.client.user.id)),
      if (publicDashboardUrl != null)
        ButtonBuilder.link(
            label: await getString(user, 'invite_dashboard'),
            url: Uri.parse(publicDashboardUrl!)),
      if (supportInvite != null)
        ButtonBuilder.link(
            label: await getString(user, 'invite_support'),
            url: Uri.parse(supportInvite!)),
    ];

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
        color: await getUserColorFromDatabase(user.id),
        title: await getString(user, 'info_invite'),
        description: await getString(user, 'invite_text'),
      ),
    ], components: [
      ActionRowBuilder(components: buttons),
    ]));
  },
);
