import 'package:nyxx/nyxx.dart';
import 'package:shiori/utils/database.dart';

const japaneseNickname = 'しおり';
const _replaceableNicknames = {'Toxbot', 'Shiori'};

Future<void> applyJapaneseNickname(
    NyxxGateway client, GuildCreateEvent event) async {
  final connection = await getMySqlConnection();

  try {
    final guildId = event.guild.id.toString();
    final result = await connection.query(
        'SELECT `bot_nickname_set` FROM `guilds` WHERE `id` = ? LIMIT 1;',
        [guildId]);
    if (result.isEmpty || result.first['bot_nickname_set'] == 1) return;

    final botId = client.user.id;
    final member =
        event.members.where((member) => member.id == botId).firstOrNull ??
            await event.guild.members.get(botId);
    final nick = member.nick;

    if (nick == null || _replaceableNicknames.contains(nick)) {
      await event.guild.members.updateCurrentMember(
          CurrentMemberUpdateBuilder(nick: japaneseNickname));
    }

    await connection.query(
        'UPDATE `guilds` SET `bot_nickname_set` = 1 WHERE `id` = ?;',
        [guildId]);
  } catch (e) {
    print('Error in applyJapaneseNickname: $e');
  } finally {
    await connection.close();
  }
}
