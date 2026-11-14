import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:shiori/utils/config.dart';
import 'package:shiori/utils/database.dart';
import 'package:shiori/utils/functions.dart';
import 'package:shiori/utils/utils.dart';

final invite = ChatCommand(
  'invite',
  'Get the link to add Shiori to your server.',
  localizedDescriptions: {
    Locale.da: 'Få linket til at tilføje Shiori til din server.',
    Locale.de: 'Den Link holen, um Shiori zu deinem Server hinzuzufügen.',
    Locale.enUs: 'Get the link to add Shiori to your server.',
    Locale.esEs: 'Obtener el enlace para añadir Shiori a tu servidor.',
    Locale.fr: 'Obtenir le lien pour ajouter Shiori à ton serveur.',
    Locale.ru: 'Получить ссылку, чтобы добавить Shiori на свой сервер.',
    Locale.hi: 'Shiori को अपने सर्वर में जोड़ने का लिंक पाएँ।',
    Locale.zhCn: '获取将 Shiori 添加到你服务器的链接。',
    Locale.ja: 'Shiori をサーバーに追加するリンクを表示します。',
    Locale.ko: 'Shiori를 서버에 추가하는 링크를 받습니다.',
    Locale.es419: 'Obtener el enlace para añadir Shiori a tu servidor.',
    Locale.ptBr: 'Pegar o link para adicionar a Shiori ao seu servidor.',
    Locale.tr: 'Shiori\'u sunucuna eklemek için bağlantıyı al.',
    Locale.pl: 'Weź link, żeby dodać Shiori na swój serwer.',
    Locale.it: 'Ottieni il link per aggiungere Shiori al tuo server.',
    Locale.uk: 'Отримати посилання, щоб додати Shiori на свій сервер.',
    Locale.vi: 'Lấy liên kết để thêm Shiori vào máy chủ của bạn.',
    Locale.th: 'รับลิงก์สำหรับเพิ่ม Shiori เข้าเซิร์ฟเวอร์ของคุณ',
    Locale.zhTw: '取得把 Shiori 加到你伺服器的連結。',
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
