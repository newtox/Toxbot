import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/utils/database.dart';

Snowflake? _id(Object? value) {
  final text = textOrNull(value);
  return text == null ? null : Snowflake.parse(text);
}

class GuildEvent {
  final int id;
  final Snowflake guild;
  final String name;
  final Snowflake? roleId;
  final Snowflake? categoryId;
  final Snowflake? textChannelId;
  final Snowflake? voiceChannelId;

  GuildEvent.fromRow(ResultRow row)
      : id = row['id'] as int,
        guild = Snowflake.parse(textValue(row['guild'])),
        name = textValue(row['name']),
        roleId = _id(row['role_id']),
        categoryId = _id(row['category_id']),
        textChannelId = _id(row['text_channel_id']),
        voiceChannelId = _id(row['voice_channel_id']);
}
