import 'package:dotenv/dotenv.dart';
import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/data/economy.dart' as economy;
import 'package:toxbot/database.dart';
import 'package:toxbot/levels.dart';
import 'package:toxbot/premium.dart';
import 'package:toxbot/utils/functions.dart';

var env = DotEnv(includePlatformEnvironment: true)..load();

const _red = '#c41111';
const _green = '#6dbe33';

final _noMentions = AllowedMentions(parse: []);

String? get dashboardUrl {
  final url = (env['dashboard_url'] ?? '').trim();
  return url.isEmpty ? null : url.replaceAll(RegExp(r'/+$'), '');
}

int get dailyReward => int.tryParse(env['daily_reward'] ?? '') ?? 100;
int get dailyStreakBonus => int.tryParse(env['daily_streak_bonus'] ?? '') ?? 10;

String? get xpBoostSku {
  final sku = (env['xp_boost_sku'] ?? '').trim();
  return sku.isEmpty ? null : sku;
}

Future<String> _text(
  User user,
  String key, [
  Map<String, String> replace = const {},
]) async {
  var text = await getString(user, key);
  replace.forEach((from, to) => text = text.replaceAll(from, to));
  return text;
}

Future<void> _error(
  InteractiveContext context,
  User user,
  String key, [
  Map<String, String> replace = const {},
]) async {
  await context.respond(
    MessageBuilder(
      allowedMentions: _noMentions,
      embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(_red),
          title: await getString(user, 'global_error'),
          description: await _text(user, key, replace),
        ),
      ],
    ),
    level: ResponseLevel.hint,
  );
}

Future<void> _unexpected(
  InteractiveContext context,
  User user,
  String command,
  Object e,
) async {
  print('Error in $command command: $e');
  await context.respond(
    MessageBuilder(
      embeds: [
        EmbedBuilder(
          color: DiscordColor.parseHexString(_red),
          title: await getString(user, 'global_error'),
          description:
              await _text(user, 'levels_error', {'&command': command}) +
                  codeBlock(e.toString(), 'sh'),
        ),
      ],
    ),
    level: ResponseLevel.hint,
  );
}

Future<List<int>?> _fetchCard(
  Snowflake userId, {
  Snowflake? guildId,
  int? background,
}) async {
  final base = dashboardUrl;
  if (base == null) return null;
  try {
    final uri = Uri.parse('$base/card/$userId.png').replace(
      queryParameters: {
        if (guildId != null) 'guild': guildId.toString(),
        if (background != null) 'bg': background.toString(),
      },
    );
    final response = await http.get(uri).timeout(const Duration(seconds: 10));
    if (response.statusCode != 200) {
      print('Profile card: dashboard answered ${response.statusCode}');
      return null;
    }
    return response.bodyBytes;
  } catch (e) {
    print('Profile card could not be loaded: $e');
    return null;
  }
}

final profile = ChatCommand(
  'profile',
  'Show your profile card or the one of someone else.',
  localizedDescriptions: {
    Locale.de: 'Zeige deine Profilkarte oder die von jemand anderem.',
    Locale.enUs: 'Show your profile card or the one of someone else.',
    Locale.da: 'Vis dit profilkort eller en andens.',
    Locale.esEs: 'Muestra tu tarjeta de perfil o la de otra persona.',
    Locale.fr:
        'Afficher votre carte de profil ou celle de quelqu\'un d\'autre.',
    Locale.ru: 'Показать вашу карточку профиля или чужую.',
    Locale.hi: 'अपना या किसी और का प्रोफ़ाइल कार्ड दिखाएं।',
    Locale.zhCn: '显示你或别人的个人资料卡。',
    Locale.ja: '自分または他の人のプロフィールカードを表示する。',
    Locale.ko: '내 프로필 카드나 다른 사람의 카드를 표시합니다.',
  },
  (
    ChatContext context, [
    @Description('Whose profile you want to see. Defaults to you.', {
      Locale.de: 'Wessen Profil du sehen möchtest. Standardmäßig deins.',
      Locale.enUs: 'Whose profile you want to see. Defaults to you.',
      Locale.da: 'Hvis profil du vil se. Som standard din egen.',
      Locale.esEs: 'De quién quieres ver el perfil. Por defecto, el tuyo.',
      Locale.fr: 'De qui vous voulez voir le profil. Par défaut, le vôtre.',
      Locale.ru: 'Чей профиль вы хотите увидеть. По умолчанию ваш.',
      Locale.hi: 'आप किसकी प्रोफ़ाइल देखना चाहते हैं। डिफ़ॉल्ट रूप से आपकी।',
      Locale.zhCn: '你想查看谁的资料。默认是你自己。',
      Locale.ja: '誰のプロフィールを見るか。デフォルトはあなた。',
      Locale.ko: '누구의 프로필을 볼지. 기본값은 나.',
    })
    User? target,
  ]) async {
    final user = context.user;
    final who = target ?? user;
    try {
      if (who.isBot) {
        await _error(context, user, 'profile_bot');
        return;
      }

      final card = await _fetchCard(who.id, guildId: context.guild?.id);
      if (card != null) {
        await context.respond(
          MessageBuilder(
            allowedMentions: _noMentions,
            attachments: [
              AttachmentBuilder(data: card, fileName: 'profile.png'),
            ],
          ),
        );
        return;
      }

      final stats = await withConnection(
        (connection) => economy.profileStats(
          connection,
          who.id.toString(),
          guildId: context.guild?.id.toString(),
        ),
      );
      final progress = levelFromXp(stats.xp);
      await context.respond(
        MessageBuilder(
          allowedMentions: _noMentions,
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(who.id),
              title: who.globalName ?? who.username,
              thumbnail: EmbedThumbnailBuilder(url: who.avatar.url),
              fields: [
                EmbedFieldBuilder(
                  name: await getString(user, 'profile_level'),
                  value:
                      '${progress.level} (${formatNumber(progress.current)} / ${formatNumber(progress.needed)} XP)',
                  isInline: true,
                ),
                if (context.guild != null)
                  EmbedFieldBuilder(
                    name: await getString(user, 'profile_server_level'),
                    value: levelFromXp(stats.serverXp ?? 0).level.toString(),
                    isInline: true,
                  ),
                EmbedFieldBuilder(
                  name: await getString(user, 'profile_rank'),
                  value: '#${formatNumber(stats.rank)}',
                  isInline: true,
                ),
                EmbedFieldBuilder(
                  name: await getString(user, 'profile_credits'),
                  value: formatNumber(stats.credits),
                  isInline: true,
                ),
                EmbedFieldBuilder(
                  name: await getString(user, 'profile_commands'),
                  value: formatNumber(stats.commands),
                  isInline: true,
                ),
              ],
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'profile', e);
    }
  },
);

final daily = ChatCommand(
  'daily',
  'Collect your daily credits.',
  localizedDescriptions: {
    Locale.de: 'Hol dir deine täglichen Credits ab.',
    Locale.enUs: 'Collect your daily credits.',
    Locale.da: 'Hent dine daglige credits.',
    Locale.esEs: 'Recoge tus créditos diarios.',
    Locale.fr: 'Récupérez vos crédits quotidiens.',
    Locale.ru: 'Получить ежедневные кредиты.',
    Locale.hi: 'अपने दैनिक क्रेडिट लें।',
    Locale.zhCn: '领取你的每日积分。',
    Locale.ja: 'デイリークレジットを受け取る。',
    Locale.ko: '일일 크레딧을 받습니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final premium = await isUserPremium(user.id, context: context);

      final result = await withConnection(
        (connection) => economy.claimDaily(
          connection,
          user.id.toString(),
          reward: dailyReward,
          streakBonus: dailyStreakBonus,
          premium: premium,
        ),
      );
      final claimed = result.claimed;
      final waitSeconds = result.waitSeconds;
      final amount = result.amount;
      final streak = result.streak;

      if (!claimed) {
        final next = DateTime.now().toUtc().add(Duration(seconds: waitSeconds));
        await _error(context, user, 'daily_wait', {
          '&time': '<t:${next.millisecondsSinceEpoch ~/ 1000}:R>',
        });
        return;
      }

      await context.respond(
        MessageBuilder(
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(_green),
              title: await getString(user, 'daily_title'),
              description: await _text(user, 'daily_claimed', {
                '&amount': formatCredits(amount),
                '&streak': streak.toString(),
              }),
              footer: premium
                  ? EmbedFooterBuilder(
                      text: await getString(user, 'daily_premium'),
                    )
                  : null,
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'daily', e);
    }
  },
);

final shop = ChatCommand(
  'shop',
  'Browse and buy profile backgrounds.',
  localizedDescriptions: {
    Locale.de: 'Profil-Hintergründe ansehen und kaufen.',
    Locale.enUs: 'Browse and buy profile backgrounds.',
    Locale.da: 'Se og køb profilbaggrunde.',
    Locale.esEs: 'Mira y compra fondos de perfil.',
    Locale.fr: 'Parcourir et acheter des fonds de profil.',
    Locale.ru: 'Просмотр и покупка фонов профиля.',
    Locale.hi: 'प्रोफ़ाइल बैकग्राउंड देखें और खरीदें।',
    Locale.zhCn: '浏览并购买个人资料背景。',
    Locale.ja: 'プロフィール背景を見て購入する。',
    Locale.ko: '프로필 배경을 둘러보고 구매합니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final premium = await isUserPremium(user.id, context: context);
      final (backgrounds, owned, credits) = await withConnection((
        connection,
      ) async {
        await economy.ensureUser(connection, user.id.toString());
        return (
          await economy.shopBackgrounds(connection),
          await economy.ownedBackgrounds(connection, user.id.toString()),
          await economy.getCredits(connection, user.id.toString()),
        );
      });

      if (backgrounds.isEmpty) {
        await _error(context, user, 'shop_empty');
        return;
      }

      String status(economy.ShopBackground bg) {
        if (owned.contains(bg.id) || bg.isFree) return '✅';
        if (bg.premiumOnly) return '⭐';
        return formatCredits(bg.price);
      }

      final ownedLabel = await getString(user, 'shop_owned');
      final premiumLabel = await getString(user, 'shop_premium');

      final choice = await context.getSelection<economy.ShopBackground>(
        backgrounds,
        MessageBuilder(
          allowedMentions: _noMentions,
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(user.id),
              title: await getString(user, 'shop_title'),
              description: '${await getString(user, 'shop_intro')}\n\n'
                  '${backgrounds.map((bg) => '**${bg.name}** · ${status(bg)}').join('\n')}',
              footer: EmbedFooterBuilder(
                text: await _text(user, 'shop_balance', {
                  '&amount': formatCredits(credits),
                }),
              ),
            ),
          ],
        ),
        level: ResponseLevel.hint,
        timeout: const Duration(minutes: 2),
        toSelectMenuOption: (bg) => SelectMenuOptionBuilder(
          label: bg.name,
          value: bg.id.toString(),
          description: owned.contains(bg.id) || bg.isFree
              ? ownedLabel
              : bg.premiumOnly
                  ? premiumLabel
                  : formatCredits(bg.price),
        ),
      );

      final isOwned = owned.contains(choice.id) || choice.isFree;
      final card = await _fetchCard(
        user.id,
        guildId: context.guild?.id,
        background: choice.id,
      );

      final String question;
      if (choice.premiumOnly && !premium) {
        question = await getString(user, 'shop_needs_premium');
      } else if (isOwned) {
        question = await _text(user, 'shop_equip_question', {
          '&name': choice.name,
        });
      } else {
        question = await _text(user, 'shop_buy_question', {
          '&name': choice.name,
          '&price': formatCredits(choice.price),
          '&amount': formatCredits(credits),
        });
      }

      final preview = MessageBuilder(
        allowedMentions: _noMentions,
        embeds: [
          EmbedBuilder(
            color: await getUserColorFromDatabase(user.id),
            title: choice.name,
            description: question,
            image: card == null
                ? null
                : EmbedImageBuilder(url: Uri.parse('attachment://preview.png')),
          ),
        ],
        attachments: card == null
            ? null
            : [AttachmentBuilder(data: card, fileName: 'preview.png')],
      );

      if (choice.premiumOnly && !premium) {
        await context.respond(preview, level: ResponseLevel.hint);
        return;
      }

      final confirmed = await context.getConfirmation(
        preview,
        values: {
          true: await getString(user, isOwned ? 'shop_equip' : 'shop_buy'),
          false: await getString(user, 'shop_cancel'),
        },
        styles: {true: ButtonStyle.success, false: ButtonStyle.secondary},
        level: ResponseLevel.hint,
        timeout: const Duration(minutes: 2),
      );
      if (!confirmed) {
        await context.respond(
          MessageBuilder(content: await getString(user, 'shop_cancelled')),
          level: ResponseLevel.hint,
        );
        return;
      }

      final error = await withConnection(
        (connection) => economy.buyOrEquip(
          connection,
          user.id.toString(),
          choice,
          premium: premium,
        ),
      );
      if (error != null) {
        await _error(context, user, error);
        return;
      }

      await context.respond(
        MessageBuilder(
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(_green),
              description: await _text(
                user,
                isOwned ? 'shop_equipped' : 'shop_bought',
                {'&name': choice.name},
              ),
            ),
          ],
        ),
        level: ResponseLevel.hint,
      );
    } on InteractionTimeoutException {
      return;
    } catch (e) {
      await _unexpected(context, user, 'shop', e);
    }
  },
);

Future<Iterable<CommandOptionChoiceBuilder<String>>> ownedBackgroundChoices(
  AutocompleteContext context,
) async {
  try {
    final names = await withConnection(
      (connection) => economy.usableBackgroundNames(
        connection,
        context.user.id.toString(),
        context.currentValue,
      ),
    );
    return names.map(
      (name) => CommandOptionChoiceBuilder(name: name, value: name),
    );
  } catch (e) {
    print('Error in background autocomplete: $e');
    return const [];
  }
}

final background = ChatCommand(
  'background',
  'Change the background of your profile card.',
  localizedDescriptions: {
    Locale.de: 'Den Hintergrund deiner Profilkarte wechseln.',
    Locale.enUs: 'Change the background of your profile card.',
    Locale.da: 'Skift baggrunden på dit profilkort.',
    Locale.esEs: 'Cambia el fondo de tu tarjeta de perfil.',
    Locale.fr: 'Changer le fond de votre carte de profil.',
    Locale.ru: 'Сменить фон вашей карточки профиля.',
    Locale.hi: 'अपने प्रोफ़ाइल कार्ड का बैकग्राउंड बदलें।',
    Locale.zhCn: '更换个人资料卡的背景。',
    Locale.ja: 'プロフィールカードの背景を変更する。',
    Locale.ko: '프로필 카드의 배경을 변경합니다.',
  },
  (
    ChatContext context,
    @Description('One of your backgrounds.', {
      Locale.de: 'Einer deiner Hintergründe.',
      Locale.enUs: 'One of your backgrounds.',
      Locale.da: 'En af dine baggrunde.',
      Locale.esEs: 'Uno de tus fondos.',
      Locale.fr: 'Un de vos fonds.',
      Locale.ru: 'Один из ваших фонов.',
      Locale.hi: 'आपके बैकग्राउंड में से एक।',
      Locale.zhCn: '你的一个背景。',
      Locale.ja: 'あなたの背景の1つ。',
      Locale.ko: '내 배경 중 하나.',
    })
    @Autocomplete(ownedBackgroundChoices)
    String name,
  ) async {
    final user = context.user;
    try {
      final premium = await isUserPremium(user.id, context: context);
      final error = await withConnection((connection) async {
        final found = await economy.shopBackgrounds(connection, name: name);
        if (found.isEmpty) return 'background_not_found';
        return economy.buyOrEquip(
          connection,
          user.id.toString(),
          found.first,
          premium: premium,
          allowBuy: false,
        );
      });

      if (error != null) {
        await _error(context, user, error, {'&name': name});
        return;
      }
      await context.respond(
        MessageBuilder(
          allowedMentions: _noMentions,
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(_green),
              description: await _text(user, 'shop_equipped', {'&name': name}),
            ),
          ],
        ),
        level: ResponseLevel.hint,
      );
    } catch (e) {
      await _unexpected(context, user, 'background', e);
    }
  },
);

String _scopeToString(String scope) => scope;

const _scopeConverter = SimpleConverter.fixed(
  elements: ['Global', 'Server'],
  stringify: _scopeToString,
);

final leaderboard = ChatCommand(
  'leaderboard',
  'Show the users with the most XP.',
  localizedDescriptions: {
    Locale.de: 'Die Nutzer mit den meisten XP anzeigen.',
    Locale.enUs: 'Show the users with the most XP.',
    Locale.da: 'Vis brugerne med mest XP.',
    Locale.esEs: 'Muestra los usuarios con más XP.',
    Locale.fr: 'Afficher les utilisateurs avec le plus d\'XP.',
    Locale.ru: 'Показать пользователей с наибольшим XP.',
    Locale.hi: 'सबसे ज़्यादा XP वाले उपयोगकर्ता दिखाएं।',
    Locale.zhCn: '显示 XP 最多的用户。',
    Locale.ja: 'XPが最も多いユーザーを表示する。',
    Locale.ko: 'XP가 가장 많은 사용자를 표시합니다.',
  },
  (
    ChatContext context, [
    @Description('Global or only this server. Defaults to this server.', {
      Locale.de: 'Global oder nur dieser Server. Standardmäßig dieser Server.',
      Locale.enUs: 'Global or only this server. Defaults to this server.',
      Locale.da: 'Global eller kun denne server. Som standard denne server.',
      Locale.esEs: 'Global o solo este servidor. Por defecto, este servidor.',
      Locale.fr: 'Global ou seulement ce serveur. Par défaut, ce serveur.',
      Locale.ru: 'Глобально или только этот сервер. По умолчанию этот сервер.',
      Locale.hi: 'ग्लोबल या केवल यह सर्वर। डिफ़ॉल्ट रूप से यह सर्वर।',
      Locale.zhCn: '全球或仅本服务器。默认是本服务器。',
      Locale.ja: 'グローバルまたはこのサーバーのみ。デフォルトはこのサーバー。',
      Locale.ko: '글로벌 또는 이 서버만. 기본값은 이 서버.',
    })
    @UseConverter(_scopeConverter)
    String? scope,
  ]) async {
    final user = context.user;
    try {
      final global = scope == 'Global' || context.guild == null;
      final rows = await withConnection(
        (connection) => economy.leaderboard(
          connection,
          guildId: global ? null : context.guild!.id.toString(),
        ),
      );

      final medals = ['🥇', '🥈', '🥉'];
      final lines = <String>[];
      for (final (place, (userId, xp)) in rows.indexed) {
        final prefix = place < 3 ? medals[place] : '`#${place + 1}`';
        lines.add(
          '$prefix <@$userId> · Level ${levelFromXp(xp).level} · ${formatNumber(xp)} XP',
        );
      }

      await context.respond(
        MessageBuilder(
          allowedMentions: _noMentions,
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(user.id),
              title: await getString(
                user,
                global ? 'leaderboard_global' : 'leaderboard_server',
              ),
              description: lines.isEmpty
                  ? await getString(user, 'leaderboard_empty')
                  : lines.join('\n'),
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'leaderboard', e);
    }
  },
);

final boost = ChatCommand(
  'boost',
  'Use an XP boost for 24 hours of extra XP.',
  localizedDescriptions: {
    Locale.de: 'Einen XP-Boost für 24 Stunden extra XP einlösen.',
    Locale.enUs: 'Use an XP boost for 24 hours of extra XP.',
    Locale.da: 'Brug et XP-boost for ekstra XP i 24 timer.',
    Locale.esEs: 'Usa un boost de XP para tener XP extra durante 24 horas.',
    Locale.fr: 'Utiliser un boost d\'XP pour 24 heures d\'XP en plus.',
    Locale.ru: 'Использовать XP-буст для дополнительного XP на 24 часа.',
    Locale.hi: '24 घंटे के अतिरिक्त XP के लिए XP बूस्ट इस्तेमाल करें।',
    Locale.zhCn: '使用 XP 加成，获得 24 小时额外 XP。',
    Locale.ja: 'XPブーストを使って24時間XPを増やす。',
    Locale.ko: 'XP 부스트를 사용해 24시간 동안 추가 XP를 받습니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final sku = xpBoostSku;
      if (sku == null || context is! InteractionChatContext) {
        await _error(context, user, 'boost_unavailable');
        return;
      }

      final entitlement = context.interaction.entitlements
          .where(
            (e) =>
                e.skuId.toString() == sku &&
                e.userId == user.id &&
                !e.isConsumed &&
                !e.isDeleted,
          )
          .firstOrNull;

      if (entitlement == null) {
        await context.respond(
          MessageBuilder(
            embeds: [
              EmbedBuilder(
                color: await getUserColorFromDatabase(user.id),
                description: await getString(user, 'boost_none'),
              ),
            ],
            components: [
              ActionRowBuilder(
                components: [
                  ButtonBuilder.premium(skuId: Snowflake.parse(sku)),
                ],
              ),
            ],
          ),
          level: ResponseLevel.hint,
        );
        return;
      }

      await entitlement.consume();

      final until = await withConnection(
        (connection) => economy.extendXpBoost(connection, user.id.toString()),
      );

      await context.respond(
        MessageBuilder(
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(_green),
              description: await _text(user, 'boost_active', {
                '&time':
                    '<t:${until.toUtc().millisecondsSinceEpoch ~/ 1000}:R>',
              }),
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'boost', e);
    }
  },
);
