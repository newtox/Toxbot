import 'package:mysql1/mysql1.dart';
import 'package:toxbot/utils/database.dart';

class ModCase {
  final int id;
  final String guild;
  final int number;
  final String type;
  final String userId;
  final String? userName;
  final String? moderatorId;
  final String? moderatorName;
  final String? reason;
  final int? duration;
  final DateTime? expiresAt;
  final bool active;
  final String source;
  final DateTime createdAt;

  ModCase.fromRow(ResultRow row)
      : id = row['id'] as int,
        guild = row['guild'] as String,
        number = row['number'] as int,
        type = row['type'] as String,
        userId = row['user_id'] as String,
        userName = row['user_name'] as String?,
        moderatorId = row['moderator_id'] as String?,
        moderatorName = row['moderator_name'] as String?,
        reason = textOrNull(row['reason']),
        duration = row['duration'] as int?,
        expiresAt = (row['expires_at'] as DateTime?)?.toUtc(),
        active = (row['active'] as int? ?? 0) == 1,
        source = row['source'] as String,
        createdAt = (row['created_at'] as DateTime).toUtc();
}
