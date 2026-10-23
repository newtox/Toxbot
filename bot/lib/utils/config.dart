import 'package:dotenv/dotenv.dart';
import 'package:nyxx/nyxx.dart';

final env = DotEnv(includePlatformEnvironment: true)..load();

int _int(String key, int fallback) => int.tryParse(env[key] ?? '') ?? fallback;

String? _string(String key) {
  final value = (env[key] ?? '').trim();
  return value.isEmpty ? null : value;
}

Set<String> _list(String key, [String fallback = '']) => (env[key] ?? fallback)
    .split(',')
    .map((entry) => entry.trim())
    .where((entry) => entry.isNotEmpty)
    .toSet();

String get botToken => env['token']!;

Snowflake get ownerId =>
    Snowflake(int.tryParse(_string('owner_id') ?? '') ?? 0);

String? get dashboardUrl =>
    _string('dashboard_url')?.replaceAll(RegExp(r'/+$'), '');

String get currency => _string('currency') ?? 'Credits';

int get xpCooldown => _int('xp_cooldown', 60);

int get xpMin => _int('xp_min', 15);

int get xpMax => _int('xp_max', 25);

int get levelUpReward => _int('levelup_reward', 25);

int get dailyReward => _int('daily_reward', 100);

int get dailyStreakBonus => _int('daily_streak_bonus', 10);

String? get xpBoostSku => _string('xp_boost_sku');

int get freeTagLimit => _int('tag_free_limit', 10);

int get premiumTagLimit => _int('tag_premium_limit', 250);

Set<String> get allowedImageHosts => _list('tag_image_hosts',
        'cdn.discordapp.com,media.discordapp.net,i.imgur.com')
    .map((host) => host.toLowerCase())
    .toSet();

int? get tagReportChannel => int.tryParse(_string('tag_report_channel') ?? '');

Set<String> get premiumUserSkus => _list('premium_user_skus');

Set<String> get premiumUserIds => _list('premium_users');
