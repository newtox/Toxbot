import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/database.dart';

final clear = ChatCommand('clear', 'Clear messages in your current channel.',
    localizedDescriptions: {
      Locale.da: 'Ryd op i meddelelser i din nuværende kanal.',
      Locale.de: 'Nachrichten im aktuellen Kanal löschen.',
      Locale.enUs: 'Clear messages in your current channel.',
      Locale.esEs: 'Borrar mensajes en tu canal actual.',
      Locale.fr: 'Effacer les messages dans votre canal actuel.',
      Locale.ru: 'Очистить сообщения в текущем канале.',
      Locale.hi: 'अपने वर्तमान चैनल में संदेश हटाएं।',
      Locale.zhCn: '清除当前频道中的消息。',
      Locale.ja: '現在のチャンネルのメッセージをクリアする。',
      Locale.ko: '현재 채널의 메시지를 지우세요.'
    },
    checks: [
      GuildCheck.all(),
      PermissionsCheck(Permissions.manageMessages)
    ], (ChatContext context,
        @UseConverter(IntConverter(min: 1))
        @Description('The amount of messages to delete.', {
          Locale.da: 'Mængden af beskeder, der skal slettes.',
          Locale.de: 'Die Anzahl der zu löschenden Nachrichten.',
          Locale.enUs: 'The amount of messages to delete.',
          Locale.esEs: 'La cantidad de mensajes a eliminar.',
          Locale.fr: 'La quantité de messages à supprimer.',
          Locale.ru: 'Количество сообщений для удаления.',
          Locale.hi: 'संदेशों की मात्रा हटाने के लिए।',
          Locale.zhCn: '要删除的消息数量。',
          Locale.ja: '削除するメッセージの量。',
          Locale.ko: '삭제할 메시지의 양.'
        })
        int amount,
        [@Description('The user from whom to delete messages.', {
          Locale.da: 'Brugeren, hvis beskeder skal slettes.',
          Locale.de:
              'Der Benutzer, von dem Nachrichten gelöscht werden sollen.',
          Locale.enUs: 'The user from whom to delete messages.',
          Locale.esEs: 'El usuario de quien se deben eliminar los mensajes.',
          Locale.fr: 'L\'utilisateur dont les messages doivent être supprimés.',
          Locale.ru: 'Пользователь, у которого нужно удалить сообщения.',
          Locale.hi: 'उस उपयोगकर्ता से संदेश हटाने के लिए जिनके।',
          Locale.zhCn: '要删除其消息的用户。',
          Locale.ja: 'メッセージを削除するユーザー。',
          Locale.ko: '메시지를 삭제할 사용자.'
        })
        User? user]) async {
  List<Message>? channelMessages;
  Snowflake? last;

  while (
      amount > 0 && (channelMessages == null || channelMessages.isNotEmpty)) {
    channelMessages = await context.channel.messages.fetchMany(
      limit: 100,
      after: last,
    );

    last = channelMessages.last.id;

    Iterable<Message> toRemove;

    if (user == null) {
      toRemove = channelMessages.take(amount);
    } else {
      toRemove = channelMessages
          .where((message) => message.author.id == user.id)
          .take(amount);
    }

    if (toRemove.length == 1) {
      await toRemove.first.delete();
    } else {
      await context.channel.messages.bulkDelete(toRemove.map((m) => m.id));
    }

    amount -= toRemove.length;
  }

  await context.respond(
      MessageBuilder(embeds: [
        EmbedBuilder(
            color: DiscordColor.parseHexString('#6dbe33'),
            description: await getString(context.user, 'clear_cleared'))
      ]),
      level: ResponseLevel.hint);
});
