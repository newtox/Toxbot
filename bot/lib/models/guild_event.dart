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
  final Snowflake? teamRoleId;
  final Snowflake? categoryId;
  final Snowflake? infoChannelId;
  final Snowflake? textChannelId;
  final Snowflake? voiceChannelId;
  final List<Snowflake> extraVoiceChannelIds;

  GuildEvent.fromRow(ResultRow row)
      : id = row['id'] as int,
        guild = Snowflake.parse(textValue(row['guild'])),
        name = textValue(row['name']),
        roleId = _id(row['role_id']),
        teamRoleId = _id(row['team_role_id']),
        categoryId = _id(row['category_id']),
        infoChannelId = _id(row['info_channel_id']),
        textChannelId = _id(row['text_channel_id']),
        voiceChannelId = _id(row['voice_channel_id']),
        extraVoiceChannelIds =
            (textOrNull(row['extra_voice_channel_ids']) ?? '')
                .split(',')
                .where((id) => id.isNotEmpty)
                .map(Snowflake.parse)
                .toList();

  List<Snowflake> get voiceChannelIds =>
      [if (voiceChannelId != null) voiceChannelId!, ...extraVoiceChannelIds];
}
