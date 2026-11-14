import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:shiori/services/events.dart';
import 'package:shiori/utils/database.dart';
import 'package:shiori/utils/functions.dart';
import 'package:shiori/utils/utils.dart';

final info = ChatCommand('info', 'Show information about Shiori.',
    localizedDescriptions: {
      Locale.da: 'Vis oplysninger om Shiori.',
      Locale.de: 'Infos über Shiori anzeigen.',
      Locale.enUs: 'Show information about Shiori.',
      Locale.esEs: 'Mostrar información sobre Shiori.',
      Locale.fr: 'Afficher des informations sur Shiori.',
      Locale.ru: 'Показать информацию о Shiori.',
      Locale.hi: 'Shiori के बारे में जानकारी दिखाएँ।',
      Locale.zhCn: '显示 Shiori 的信息。',
      Locale.ja: 'Shiori の情報を表示します。',
      Locale.ko: 'Shiori 정보를 표시합니다.',
      Locale.es419: 'Mostrar información sobre Shiori.',
      Locale.ptBr: 'Mostrar informações sobre a Shiori.',
      Locale.tr: 'Shiori hakkında bilgi göster.',
      Locale.pl: 'Pokaż informacje o Toxbocie.',
      Locale.it: 'Mostra informazioni su Shiori.',
      Locale.uk: 'Показати інформацію про Shiori.',
      Locale.vi: 'Xem thông tin về Shiori.',
      Locale.th: 'แสดงข้อมูลเกี่ยวกับ Shiori',
      Locale.zhTw: '顯示 Shiori 的資訊。'
    }, (ChatContext context) async {
  String getCurrentMemoryString() {
    final current = (ProcessInfo.currentRss / 1024 / 1024).toStringAsFixed(2);
    final rss = (ProcessInfo.maxRss / 1024 / 1024).toStringAsFixed(2);
    return '$current/$rss MB';
  }

  await context.respond(MessageBuilder(embeds: [
    EmbedBuilder(
        color: await getUserColorFromDatabase(context.user.id),
        fields: [
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_guilds'),
              value: numberWithCommas(context.client.guilds.cache.length),
              isInline: true),
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_users'),
              value: numberWithCommas(context.client.users.cache.length),
              isInline: true),
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_channels'),
              value: numberWithCommas(context.client.channels.cache.length),
              isInline: true),
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_messages'),
              value: numberWithCommas(context.client.channels.cache.values
                  .whereType<TextChannel>()
                  .map((c) => c.messages.cache.length)
                  .fold<num>(0, (value, element) => value + element)),
              isInline: true),
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_memory'),
              value: getCurrentMemoryString(),
              isInline: true),
          EmbedFieldBuilder(
              name: await getString(context.user, 'info_uptime'),
              value: botStartTime.format(TimestampStyle.relativeTime),
              isInline: true)
        ],
        footer: EmbedFooterBuilder(
            text: 'Dart SDK ${Platform.version.split('(').first}'))
  ], components: [
    ActionRowBuilder(components: [
      ButtonBuilder.link(
          label: await getString(context.user, 'info_invite'),
          url: inviteUrl(context.client.user.id))
    ])
  ]));
});
