import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/reply.dart';

final clear = ChatCommand('clear', 'Delete messages in this channel.',
    localizedDescriptions: {
      Locale.da: 'Slet beskeder i denne kanal.',
      Locale.de: 'Nachrichten in diesem Kanal löschen.',
      Locale.enUs: 'Delete messages in this channel.',
      Locale.esEs: 'Borrar mensajes en este canal.',
      Locale.fr: 'Supprimer des messages dans ce salon.',
      Locale.ru: 'Удалить сообщения в этом канале.',
      Locale.hi: 'इस चैनल में संदेश हटाएँ।',
      Locale.zhCn: '删除此频道中的消息。',
      Locale.ja: 'このチャンネルのメッセージを削除します。',
      Locale.ko: '이 채널의 메시지를 삭제합니다.'
    },
    checks: [
      GuildCheck.all(),
      PermissionsCheck(Permissions.manageMessages)
    ], (ChatContext context,
        @UseConverter(IntConverter(min: 1))
        @Description('How many messages you want to delete.', {
          Locale.da: 'Hvor mange beskeder du vil slette.',
          Locale.de: 'Wie viele Nachrichten du löschen möchtest.',
          Locale.enUs: 'How many messages you want to delete.',
          Locale.esEs: 'Cuántos mensajes quieres borrar.',
          Locale.fr: 'Combien de messages tu veux supprimer.',
          Locale.ru: 'Сколько сообщений хочешь удалить.',
          Locale.hi: 'आप कितने संदेश हटाना चाहते हैं।',
          Locale.zhCn: '你想删除的消息数量。',
          Locale.ja: '削除したいメッセージの数。',
          Locale.ko: '삭제할 메시지 수.'
        })
        int amount,
        [@Description('Only delete messages from this user.', {
          Locale.da: 'Slet kun beskeder fra denne bruger.',
          Locale.de: 'Nur Nachrichten von diesem Nutzer löschen.',
          Locale.enUs: 'Only delete messages from this user.',
          Locale.esEs: 'Borrar solo los mensajes de este usuario.',
          Locale.fr: 'Supprimer seulement les messages de cet utilisateur.',
          Locale.ru: 'Удалять только сообщения этого пользователя.',
          Locale.hi: 'सिर्फ़ इस उपयोगकर्ता के संदेश हटाएँ।',
          Locale.zhCn: '只删除此用户的消息。',
          Locale.ja: 'このユーザーのメッセージだけを削除します。',
          Locale.ko: '이 사용자의 메시지만 삭제합니다.'
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

  await replySuccess(context, 'clear_cleared', level: ResponseLevel.hint);
});
