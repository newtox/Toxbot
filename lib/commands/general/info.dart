import 'dart:io';

import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/utils.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';

final info = ChatCommand('info', 'Get generic information about the bot.',
    localizedDescriptions: {
      Locale.da: 'Få generel information om botten.',
      Locale.de: 'Infos über den Bot holen.',
      Locale.enUs: 'Get generic information about the bot.',
      Locale.esEs: 'Obtener información general sobre el bot.',
      Locale.fr: 'Obtenir des informations générales sur le bot.',
      Locale.ru: 'Получить общую информацию о боте.',
      Locale.hi: 'बॉट के बारे में सामान्य जानकारी प्राप्त करें।',
      Locale.zhCn: '获取关于机器人的一般信息。',
      Locale.ja: 'ボットに関する一般的な情報を取得する。',
      Locale.ko: '봇에 대한 일반 정보 얻기.'
    }, (ChatContext context) async {
  String getCurrentMemoryString() {
    final current = (ProcessInfo.currentRss / 1024 / 1024).toStringAsFixed(2);
    final rss = (ProcessInfo.maxRss / 1024 / 1024).toStringAsFixed(2);
    return '$current/$rss MB';
  }

  // Shard ${(context.guild?.shard.id ?? 0) + 1} of ${(context.client as INyxxWebsocket).shards

  final invite =
      'https://discord.com/oauth2/authorize?client_id=925509327329058827&permissions=1757019580661079&integration_type=0&scope=bot+applications.commands';

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
              value: ComponentId.currentSessionStartTime
                  .format(TimestampStyle.relativeTime),
              isInline: true)
        ],
        footer: EmbedFooterBuilder(
            text: 'Dart SDK ${Platform.version.split('(').first}'))
  ], components: [
    ActionRowBuilder(components: [
      ButtonBuilder.link(
          label: await getString(context.user, 'info_invite'),
          url: Uri.parse(invite))
    ])
  ]));
});
