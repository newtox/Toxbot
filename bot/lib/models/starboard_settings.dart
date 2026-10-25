import 'package:nyxx/nyxx.dart';
import 'package:toxbot/models/starboard_emoji.dart';

class StarboardSettings {
  final Snowflake channelId;
  final List<StarboardEmoji> emojis;
  final int threshold;

  const StarboardSettings({
    required this.channelId,
    required this.emojis,
    required this.threshold,
  });
}
