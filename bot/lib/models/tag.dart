import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:toxbot/database.dart';

class Tag {
  final int id;
  final String name;
  final Snowflake owner;
  final String content;
  final bool embed;
  final String? embedColor;
  final String? embedImage;
  final int uses;
  final DateTime? createdAt;

  Tag.fromRow(ResultRow row)
      : id = row['id'] as int,
        name = row['name'] as String,
        owner = Snowflake(int.parse(row['owner'].toString())),
        content = textValue(row['content']),
        embed = row['embed'] == 1 || row['embed'] == true,
        embedColor = row['embed_color'] as String?,
        embedImage = row['embed_image'] as String?,
        uses = row['uses'] as int,
        createdAt = row['created_at'] as DateTime?;
}
