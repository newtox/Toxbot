import 'package:nyxx/nyxx.dart';

class StarboardSettings {
  final Snowflake channelId;
  final String emoji;
  final String emojiName;
  final Snowflake? emojiId;
  final int threshold;

  const StarboardSettings({
    required this.channelId,
    required this.emoji,
    required this.emojiName,
    required this.emojiId,
    required this.threshold,
  });
}
