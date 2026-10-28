import 'dart:io';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/services/events.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/utils.dart';

final info = ChatCommand('info', 'Show information about Toxbot.',
    localizedDescriptions: {
      Locale.da: 'Vis oplysninger om Toxbot.',
      Locale.de: 'Infos über Toxbot anzeigen.',
      Locale.enUs: 'Show information about Toxbot.',
      Locale.esEs: 'Mostrar información sobre Toxbot.',
      Locale.fr: 'Afficher des informations sur Toxbot.',
      Locale.ru: 'Показать информацию о Toxbot.',
      Locale.hi: 'Toxbot के बारे में जानकारी दिखाएँ।',
      Locale.zhCn: '显示 Toxbot 的信息。',
      Locale.ja: 'Toxbot の情報を表示します。',
      Locale.ko: 'Toxbot 정보를 표시합니다.'
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
