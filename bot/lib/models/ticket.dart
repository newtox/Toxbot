import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/utils/database.dart';

class TicketType {
  final int id;
  final String name;
  final String? emoji;
  final String? description;
  final Set<Snowflake> roles;

  TicketType.fromRow(ResultRow row)
      : id = row['id'] as int,
        name = textValue(row['name']),
        emoji = textOrNull(row['emoji']),
        description = textOrNull(row['description']),
        roles = (textOrNull(row['roles']) ?? '')
            .split(',')
            .map((id) => int.tryParse(id.trim()))
            .whereType<int>()
            .map(Snowflake.new)
            .toSet();

  String get label =>
      emoji == null || emoji!.startsWith('<') ? name : '$emoji $name';
}

class Ticket {
  final int id;
  final String guild;
  final int number;
  final int? typeId;
  final String? typeName;
  final Snowflake userId;
  final String? userName;
  final Snowflake? channelId;
  final String status;
  final DateTime createdAt;

  Ticket.fromRow(ResultRow row)
      : id = row['id'] as int,
        guild = row['guild'] as String,
        number = row['number'] as int,
        typeId = row['type_id'] as int?,
        typeName = textOrNull(row['type_name']),
        userId = Snowflake.parse(row['user_id'] as String),
        userName = textOrNull(row['user_name']),
        channelId = row['channel_id'] == null
            ? null
            : Snowflake.parse(row['channel_id'] as String),
        status = row['status'] as String,
        createdAt = (row['created_at'] as DateTime).toUtc();

  String get paddedNumber => number.toString().padLeft(4, '0');
}
