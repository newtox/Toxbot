import 'package:mysql1/mysql1.dart';
import 'package:toxbot/utils/database.dart';

class ServerTag {
  final int id;
  final String name;
  final String content;

  ServerTag.fromRow(ResultRow row)
      : id = row['id'] as int,
        name = row['name'] as String,
        content = textValue(row['content']);
}
