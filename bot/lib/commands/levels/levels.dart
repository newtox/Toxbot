import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/services/economy.dart' as economy;
import 'package:toxbot/services/levels.dart';
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const _scopeConverter = SimpleConverter.fixed(
  elements: ['Global', 'Server'],
  stringify: stringifyOption,
);

final profile = ChatCommand(
  'profile',
  'Show your profile card or someone else\'s.',
  localizedDescriptions: {
    Locale.de: 'Deine Profilkarte oder die von jemand anderem anzeigen.',
    Locale.enUs: 'Show your profile card or someone else\'s.',
    Locale.da: 'Vis dit profilkort eller en andens.',
    Locale.esEs: 'Mostrar tu tarjeta de perfil o la de otra persona.',
    Locale.fr: 'Afficher ta carte de profil ou celle de quelqu\'un d\'autre.',
    Locale.ru: 'Показать свою карточку профиля или чужую.',
    Locale.hi: 'अपना या किसी और का प्रोफ़ाइल कार्ड दिखाएँ।',
    Locale.zhCn: '显示你或其他人的资料卡。',
    Locale.ja: '自分または他の人のプロフィールカードを表示します。',
    Locale.ko: '내 프로필 카드나 다른 사람의 카드를 표시합니다.',
  },
  (
    ChatContext context, [
    @Description('Whose profile you want to see. Defaults to you.', {
      Locale.de: 'Wessen Profil du sehen möchtest. Standardmäßig deins.',
      Locale.enUs: 'Whose profile you want to see. Defaults to you.',
      Locale.da: 'Hvis profil du vil se. Standard er dig.',
      Locale.esEs: 'De quién quieres ver el perfil. Por defecto, el tuyo.',
      Locale.fr: 'De qui tu veux voir le profil. Par défaut, le tien.',
      Locale.ru: 'Чей профиль хочешь увидеть. По умолчанию твой.',
      Locale.hi: 'आप किसकी प्रोफ़ाइल देखना चाहते हैं। डिफ़ॉल्ट रूप से आपकी।',
      Locale.zhCn: '你想查看谁的资料。默认是你自己。',
      Locale.ja: '見たいプロフィールの持ち主。既定は自分。',
      Locale.ko: '보고 싶은 프로필의 주인. 기본값은 나.',
    })
    User? target,
  ]) async {
    final user = context.user;
    final who = target ?? user;
    try {
      if (who.isBot) {
        await replyError(context, 'profile_bot');
        return;
      }

      final card = await fetchProfileCard(who.id, guildId: context.guild?.id);
      if (card != null) {
        await context.respond(
          MessageBuilder(
            allowedMentions: noMentions,
            attachments: [
              AttachmentBuilder(data: card.data, fileName: card.fileName),
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
          allowedMentions: noMentions,
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
      await replyCommandError(context, 'levels_error', 'profile', e);
    }
  },
);

final daily = ChatCommand(
  'daily',
  'Collect your daily credits.',
  localizedDescriptions: {
    Locale.de: 'Deine täglichen Credits abholen.',
    Locale.enUs: 'Collect your daily credits.',
    Locale.da: 'Hent dine daglige credits.',
    Locale.esEs: 'Recoger tus créditos diarios.',
    Locale.fr: 'Récupérer tes crédits quotidiens.',
    Locale.ru: 'Забрать ежедневные кредиты.',
    Locale.hi: 'अपने रोज़ाना क्रेडिट लें।',
    Locale.zhCn: '领取每日积分。',
    Locale.ja: 'デイリークレジットを受け取ります。',
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
        await replyError(context, 'daily_wait', replace: {
          '&time': '<t:${next.millisecondsSinceEpoch ~/ 1000}:R>',
        });
        return;
      }

      await context.respond(
        MessageBuilder(
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(successColor),
              title: await getString(user, 'daily_title'),
              description: await localized(user, 'daily_claimed', {
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
      await replyCommandError(context, 'levels_error', 'daily', e);
    }
  },
);

final credits = ChatCommand(
  'credits',
  'Show your credits, your daily streak and what you earned and spent.',
  localizedDescriptions: {
    Locale.de:
        'Deine Credits, deine Daily-Serie und deine Einnahmen und Ausgaben anzeigen.',
    Locale.enUs:
        'Show your credits, your daily streak and what you earned and spent.',
    Locale.da:
        'Vis dine credits, din daglige stime og hvad du har tjent og brugt.',
    Locale.esEs:
        'Mostrar tus créditos, tu racha diaria y lo que has ganado y gastado.',
    Locale.fr:
        'Afficher tes crédits, ta série quotidienne et ce que tu as gagné et dépensé.',
    Locale.ru: 'Показать кредиты, серию ежедневных наград, доходы и расходы.',
    Locale.hi: 'अपने क्रेडिट, डेली स्ट्रीक और कमाई व खर्च दिखाएँ।',
    Locale.zhCn: '显示你的积分、每日连签以及收支情况。',
    Locale.ja: 'クレジット、デイリー連続日数、収支を表示します。',
    Locale.ko: '크레딧, 일일 연속 기록, 수입과 지출을 표시합니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final overview = await withConnection(
        (connection) => economy.creditsOverview(connection, user.id.toString()),
      );

      final lines = <String>[];
      for (final entry in overview.entries) {
        final sign = entry.amount >= 0 ? '+' : '−';
        final label = await getString(user, 'credits_reason_${entry.reason}');
        final time = entry.createdAt == null
            ? ''
            : ' · <t:${entry.createdAt!.millisecondsSinceEpoch ~/ 1000}:R>';
        lines.add(
            '`$sign${formatNumber(entry.amount.abs())}` ${label.isEmpty ? entry.reason : label}$time');
      }

      final next = overview.nextDaily;
      await context.respond(
        MessageBuilder(
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(user.id),
              title: await getString(user, 'credits_title'),
              description: await localized(user, 'credits_balance',
                  {'&amount': formatCredits(overview.credits)}),
              fields: [
                EmbedFieldBuilder(
                  name: await getString(user, 'credits_streak'),
                  value: await localized(user, 'credits_streak_days',
                      {'&streak': '${overview.streak}'}),
                  isInline: true,
                ),
                EmbedFieldBuilder(
                  name: await getString(user, 'credits_daily'),
                  value: next == null
                      ? await getString(user, 'credits_daily_ready')
                      : '<t:${next.millisecondsSinceEpoch ~/ 1000}:R>',
                  isInline: true,
                ),
                EmbedFieldBuilder(
                  name: await getString(user, 'credits_history'),
                  value: lines.isEmpty
                      ? await getString(user, 'credits_history_empty')
                      : lines.join('\n'),
                  isInline: false,
                ),
              ],
            ),
          ],
        ),
        level: ResponseLevel.hint,
      );
    } catch (e) {
      await replyCommandError(context, 'levels_error', 'credits', e);
    }
  },
);

final pay = ChatCommand(
  'pay',
  'Send some of your credits to another member.',
  localizedDescriptions: {
    Locale.de: 'Einem anderen Mitglied Credits schicken.',
    Locale.enUs: 'Send some of your credits to another member.',
    Locale.da: 'Send nogle af dine credits til et andet medlem.',
    Locale.esEs: 'Enviar parte de tus créditos a otro miembro.',
    Locale.fr: 'Envoyer une partie de tes crédits à un autre membre.',
    Locale.ru: 'Отправить часть своих кредитов другому участнику.',
    Locale.hi: 'अपने कुछ क्रेडिट किसी दूसरे सदस्य को भेजें।',
    Locale.zhCn: '把你的一些积分转给另一位成员。',
    Locale.ja: '自分のクレジットを他のメンバーに送ります。',
    Locale.ko: '내 크레딧을 다른 멤버에게 보냅니다.',
  },
  (
    ChatContext context,
    @Description('Who gets the credits.', {
      Locale.de: 'Wer die Credits bekommt.',
      Locale.enUs: 'Who gets the credits.',
      Locale.da: 'Hvem der får dine credits.',
      Locale.esEs: 'Quién recibe los créditos.',
      Locale.fr: 'Qui reçoit les crédits.',
      Locale.ru: 'Кто получит кредиты.',
      Locale.hi: 'क्रेडिट किसे मिलेंगे।',
      Locale.zhCn: '谁会收到积分。',
      Locale.ja: 'クレジットを受け取る人。',
      Locale.ko: '크레딧을 받을 사람.',
    })
    User target,
    @Description('How many credits you send.', {
      Locale.de: 'Wie viele Credits du schickst.',
      Locale.enUs: 'How many credits you send.',
      Locale.da: 'Hvor mange credits du sender.',
      Locale.esEs: 'Cuántos créditos envías.',
      Locale.fr: 'Combien de crédits tu envoies.',
      Locale.ru: 'Сколько кредитов ты отправляешь.',
      Locale.hi: 'आप कितने क्रेडिट भेजते हैं।',
      Locale.zhCn: '你要转出的积分数量。',
      Locale.ja: '送るクレジットの数。',
      Locale.ko: '보낼 크레딧 수.',
    })
    @UseConverter(IntConverter(min: 1, max: 1000000000))
    int amount,
  ) async {
    final user = context.user;
    try {
      if (target.id == user.id) {
        await replyError(context, 'pay_self');
        return;
      }
      if (target.isBot) {
        await replyError(context, 'pay_bot');
        return;
      }

      final (error, balance) = await withConnection((connection) async {
        final error = await economy.transferCredits(
          connection,
          user.id.toString(),
          target.id.toString(),
          amount,
        );
        return (
          error,
          await economy.getCredits(connection, user.id.toString())
        );
      });

      if (error != null) {
        await replyError(context, error,
            replace: {'&amount': formatCredits(balance)});
        return;
      }

      await context.respond(
        MessageBuilder(
          content: target.mention,
          allowedMentions: AllowedMentions(users: [target.id]),
          embeds: [
            EmbedBuilder(
              color: DiscordColor.parseHexString(successColor),
              description: await localized(user, 'pay_success', {
                '&from': user.mention,
                '&to': target.mention,
                '&amount': formatCredits(amount),
              }),
            ),
          ],
        ),
      );
    } catch (e) {
      await replyCommandError(context, 'levels_error', 'pay', e);
    }
  },
);

final shop = ChatCommand(
  'shop',
  'Browse and buy backgrounds, avatar frames and progress bars.',
  localizedDescriptions: {
    Locale.de:
        'Hintergründe, Avatar-Rahmen und Fortschrittsbalken ansehen und kaufen.',
    Locale.enUs: 'Browse and buy backgrounds, avatar frames and progress bars.',
    Locale.da: 'Se og køb baggrunde, avatarrammer og statuslinjer.',
    Locale.esEs: 'Ver y comprar fondos, marcos de avatar y barras de progreso.',
    Locale.fr:
        'Parcourir et acheter des fonds, cadres d\'avatar et barres de progression.',
    Locale.ru: 'Смотреть и покупать фоны, рамки аватара и полоски прогресса.',
    Locale.hi: 'बैकग्राउंड, अवतार फ़्रेम और प्रोग्रेस बार देखें और खरीदें।',
    Locale.zhCn: '浏览并购买背景、头像框和进度条。',
    Locale.ja: '背景、アバターフレーム、進行バーを見て購入します。',
    Locale.ko: '배경, 아바타 테두리, 진행 막대를 둘러보고 구매합니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final premium = await isUserPremium(user.id, context: context);
      final (items, owned, credits) = await withConnection((
        connection,
      ) async {
        await economy.ensureUser(connection, user.id.toString());
        return (
          await economy.shopItems(connection),
          await economy.ownedItems(connection, user.id.toString()),
          await economy.getCredits(connection, user.id.toString()),
        );
      });

      if (items.isEmpty) {
        await replyError(context, 'shop_empty');
        return;
      }

      final color = await getUserColorFromDatabase(user.id);
      final balance = await localized(user, 'shop_balance', {
        '&amount': formatCredits(credits),
      });
      final types = economy.shopItemTypes
          .where((type) => items.any((item) => item.type == type))
          .toList();
      final typeLabels = {
        for (final type in types)
          type: await getString(user, 'shop_type_$type'),
      };

      final type = types.length == 1
          ? types.first
          : await context.getSelection<String>(
              types,
              MessageBuilder(
                allowedMentions: noMentions,
                embeds: [
                  EmbedBuilder(
                    color: color,
                    title: await getString(user, 'shop_title'),
                    description: await getString(user, 'shop_pick_category'),
                    footer: EmbedFooterBuilder(text: balance),
                  ),
                ],
              ),
              level: ResponseLevel.hint,
              timeout: const Duration(minutes: 2),
              toSelectMenuOption: (type) => SelectMenuOptionBuilder(
                label: typeLabels[type]!,
                value: type,
                description:
                    '${items.where((item) => item.type == type).length}',
              ),
            );

      final choices =
          items.where((item) => item.type == type).take(25).toList();

      bool isOwned(economy.ShopItem item) =>
          owned.contains(item.id) || item.isFree;

      String status(economy.ShopItem item) {
        if (isOwned(item)) return '✅';
        if (item.premiumOnly) return '⭐';
        return formatCredits(item.price);
      }

      final ownedLabel = await getString(user, 'shop_owned');
      final premiumLabel = await getString(user, 'shop_premium');
      final animatedLabel = await getString(user, 'shop_animated');

      final choice = await context.getSelection<economy.ShopItem>(
        choices,
        MessageBuilder(
          allowedMentions: noMentions,
          embeds: [
            EmbedBuilder(
              color: color,
              title:
                  '${await getString(user, 'shop_title')} · ${typeLabels[type]}',
              description: '${await getString(user, 'shop_intro')}\n\n'
                  '${choices.map((item) => '**${item.name}**${item.animated ? ' ✨' : ''} · ${status(item)}').join('\n')}',
              footer: EmbedFooterBuilder(text: balance),
            ),
          ],
        ),
        level: ResponseLevel.hint,
        timeout: const Duration(minutes: 2),
        toSelectMenuOption: (item) => SelectMenuOptionBuilder(
          label: item.name,
          value: item.id.toString(),
          description: [
            isOwned(item)
                ? ownedLabel
                : item.premiumOnly
                    ? premiumLabel
                    : formatCredits(item.price),
            if (item.animated) animatedLabel,
          ].join(' · '),
        ),
      );

      final card = await fetchProfileCard(
        user.id,
        guildId: context.guild?.id,
        preview: choice,
      );

      final String question;
      if (choice.premiumOnly && !premium) {
        question = await getString(user, 'shop_needs_premium');
      } else if (isOwned(choice)) {
        question = await localized(user, 'shop_equip_question', {
          '&name': choice.name,
        });
      } else {
        question = await localized(user, 'shop_buy_question', {
          '&name': choice.name,
          '&price': formatCredits(choice.price),
          '&amount': formatCredits(credits),
        });
      }

      final preview = MessageBuilder(
        allowedMentions: noMentions,
        embeds: [
          EmbedBuilder(
            color: color,
            title: choice.name,
            description: question,
            image: card == null
                ? null
                : EmbedImageBuilder(
                    url: Uri.parse('attachment://${card.fileName}'),
                  ),
          ),
        ],
        attachments: card == null
            ? null
            : [AttachmentBuilder(data: card.data, fileName: card.fileName)],
      );

      if (choice.premiumOnly && !premium) {
        await context.respond(preview, level: ResponseLevel.hint);
        return;
      }

      final confirmed = await context.getConfirmation(
        preview,
        values: {
          true: await getString(
              user, isOwned(choice) ? 'shop_equip' : 'shop_buy'),
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
        await replyError(context, error);
        return;
      }

      await replySuccess(
          context, isOwned(choice) ? 'shop_equipped' : 'shop_bought',
          replace: {'&name': choice.name}, level: ResponseLevel.hint);
    } on InteractionTimeoutException {
      return;
    } catch (e) {
      await replyCommandError(context, 'levels_error', 'shop', e);
    }
  },
);

final background = ChatCommand(
  'background',
  'Change the background of your profile card.',
  localizedDescriptions: {
    Locale.de: 'Den Hintergrund deiner Profilkarte wechseln.',
    Locale.enUs: 'Change the background of your profile card.',
    Locale.da: 'Skift baggrunden på dit profilkort.',
    Locale.esEs: 'Cambiar el fondo de tu tarjeta de perfil.',
    Locale.fr: 'Changer le fond de ta carte de profil.',
    Locale.ru: 'Сменить фон карточки профиля.',
    Locale.hi: 'अपने प्रोफ़ाइल कार्ड का बैकग्राउंड बदलें।',
    Locale.zhCn: '更换资料卡的背景。',
    Locale.ja: 'プロフィールカードの背景を変更します。',
    Locale.ko: '프로필 카드의 배경을 변경합니다.',
  },
  (
    ChatContext context,
    @Description('One of your backgrounds, or the default.', {
      Locale.de: 'Einer deiner Hintergründe oder der Standard.',
      Locale.enUs: 'One of your backgrounds, or the default.',
      Locale.da: 'En af dine baggrunde eller standard.',
      Locale.esEs: 'Uno de tus fondos o el predeterminado.',
      Locale.fr: 'Un de tes fonds ou celui par défaut.',
      Locale.ru: 'Один из твоих фонов или стандартный.',
      Locale.hi: 'आपका कोई बैकग्राउंड या डिफ़ॉल्ट।',
      Locale.zhCn: '你的一个背景，或默认背景。',
      Locale.ja: '持っている背景、または既定のもの。',
      Locale.ko: '보유한 배경 또는 기본값.',
    })
    @Autocomplete(backgroundChoices)
    String name,
  ) =>
      equipItem(context, 'background', name),
);

final frame = ChatCommand(
  'frame',
  'Change the avatar frame of your profile card.',
  localizedDescriptions: {
    Locale.de: 'Den Avatar-Rahmen deiner Profilkarte wechseln.',
    Locale.enUs: 'Change the avatar frame of your profile card.',
    Locale.da: 'Skift avatarrammen på dit profilkort.',
    Locale.esEs: 'Cambiar el marco de avatar de tu tarjeta de perfil.',
    Locale.fr: 'Changer le cadre d\'avatar de ta carte de profil.',
    Locale.ru: 'Сменить рамку аватара на карточке профиля.',
    Locale.hi: 'अपने प्रोफ़ाइल कार्ड का अवतार फ़्रेम बदलें।',
    Locale.zhCn: '更换资料卡的头像框。',
    Locale.ja: 'プロフィールカードのアバターフレームを変更します。',
    Locale.ko: '프로필 카드의 아바타 테두리를 변경합니다.',
  },
  (
    ChatContext context,
    @Description('One of your avatar frames, or none.', {
      Locale.de: 'Einer deiner Avatar-Rahmen oder keiner.',
      Locale.enUs: 'One of your avatar frames, or none.',
      Locale.da: 'En af dine avatarrammer eller ingen.',
      Locale.esEs: 'Uno de tus marcos de avatar o ninguno.',
      Locale.fr: 'Un de tes cadres d\'avatar ou aucun.',
      Locale.ru: 'Одна из твоих рамок аватара или без рамки.',
      Locale.hi: 'आपका कोई अवतार फ़्रेम या कोई नहीं।',
      Locale.zhCn: '你的一个头像框，或不使用。',
      Locale.ja: '持っているアバターフレーム、またはなし。',
      Locale.ko: '보유한 아바타 테두리 또는 없음.',
    })
    @Autocomplete(frameChoices)
    String name,
  ) =>
      equipItem(context, 'frame', name),
);

final bar = ChatCommand(
  'bar',
  'Change the progress bar of your profile card.',
  localizedDescriptions: {
    Locale.de: 'Den Fortschrittsbalken deiner Profilkarte wechseln.',
    Locale.enUs: 'Change the progress bar of your profile card.',
    Locale.da: 'Skift statuslinjen på dit profilkort.',
    Locale.esEs: 'Cambiar la barra de progreso de tu tarjeta de perfil.',
    Locale.fr: 'Changer la barre de progression de ta carte de profil.',
    Locale.ru: 'Сменить полоску прогресса на карточке профиля.',
    Locale.hi: 'अपने प्रोफ़ाइल कार्ड का प्रोग्रेस बार बदलें।',
    Locale.zhCn: '更换资料卡的进度条。',
    Locale.ja: 'プロフィールカードの進行バーを変更します。',
    Locale.ko: '프로필 카드의 진행 막대를 변경합니다.',
  },
  (
    ChatContext context,
    @Description('One of your progress bars, or the default.', {
      Locale.de: 'Einer deiner Fortschrittsbalken oder der Standard.',
      Locale.enUs: 'One of your progress bars, or the default.',
      Locale.da: 'En af dine statuslinjer eller standard.',
      Locale.esEs: 'Una de tus barras de progreso o la predeterminada.',
      Locale.fr: 'Une de tes barres de progression ou celle par défaut.',
      Locale.ru: 'Одна из твоих полосок прогресса или стандартная.',
      Locale.hi: 'आपका कोई प्रोग्रेस बार या डिफ़ॉल्ट।',
      Locale.zhCn: '你的一个进度条，或默认进度条。',
      Locale.ja: '持っている進行バー、または既定のもの。',
      Locale.ko: '보유한 진행 막대 또는 기본값.',
    })
    @Autocomplete(barChoices)
    String name,
  ) =>
      equipItem(context, 'bar', name),
);

final leaderboard = ChatCommand(
  'leaderboard',
  'Show the members with the most XP.',
  localizedDescriptions: {
    Locale.de: 'Die Mitglieder mit den meisten XP anzeigen.',
    Locale.enUs: 'Show the members with the most XP.',
    Locale.da: 'Vis medlemmerne med mest XP.',
    Locale.esEs: 'Mostrar los miembros con más XP.',
    Locale.fr: 'Afficher les membres avec le plus d\'XP.',
    Locale.ru: 'Показать участников с наибольшим XP.',
    Locale.hi: 'सबसे ज़्यादा XP वाले सदस्य दिखाएँ।',
    Locale.zhCn: '显示经验值最多的成员。',
    Locale.ja: 'XP が最も多いメンバーを表示します。',
    Locale.ko: 'XP가 가장 많은 멤버를 표시합니다.',
  },
  (
    ChatContext context, [
    @Description('Global or only this server. Defaults to this server.', {
      Locale.de: 'Global oder nur dieser Server. Standardmäßig dieser Server.',
      Locale.enUs: 'Global or only this server. Defaults to this server.',
      Locale.da: 'Global eller kun denne server. Standard er denne server.',
      Locale.esEs: 'Global o solo este servidor. Por defecto, este servidor.',
      Locale.fr: 'Global ou seulement ce serveur. Par défaut, ce serveur.',
      Locale.ru: 'Глобально или только этот сервер. По умолчанию этот сервер.',
      Locale.hi: 'ग्लोबल या सिर्फ़ यह सर्वर। डिफ़ॉल्ट रूप से यह सर्वर।',
      Locale.zhCn: '全局或仅此服务器。默认是此服务器。',
      Locale.ja: '全体かこのサーバーのみ。既定はこのサーバー。',
      Locale.ko: '전체 또는 이 서버만. 기본값은 이 서버.',
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
          allowedMentions: noMentions,
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
      await replyCommandError(context, 'levels_error', 'leaderboard', e);
    }
  },
);

final boost = ChatCommand(
  'boost',
  'Use an XP boost for 24 hours of extra XP.',
  localizedDescriptions: {
    Locale.de: 'Einen XP-Boost für 24 Stunden extra XP einlösen.',
    Locale.enUs: 'Use an XP boost for 24 hours of extra XP.',
    Locale.da: 'Brug et XP-boost til 24 timers ekstra XP.',
    Locale.esEs: 'Usar un boost de XP para 24 horas de XP extra.',
    Locale.fr: 'Utiliser un boost d\'XP pour 24 heures d\'XP en plus.',
    Locale.ru: 'Использовать буст XP на 24 часа дополнительного XP.',
    Locale.hi: '24 घंटे अतिरिक्त XP के लिए XP बूस्ट इस्तेमाल करें।',
    Locale.zhCn: '使用经验加成，24 小时内获得额外经验。',
    Locale.ja: 'XP ブーストを使い、24 時間 XP を増やします。',
    Locale.ko: 'XP 부스트를 사용해 24시간 동안 추가 XP를 받습니다.',
  },
  (ChatContext context) async {
    final user = context.user;
    try {
      final sku = xpBoostSku;
      if (sku == null || context is! InteractionChatContext) {
        await replyError(context, 'boost_unavailable');
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
              color: DiscordColor.parseHexString(successColor),
              description: await localized(user, 'boost_active', {
                '&time':
                    '<t:${until.toUtc().millisecondsSinceEpoch ~/ 1000}:R>',
              }),
            ),
          ],
        ),
      );
    } catch (e) {
      await replyCommandError(context, 'levels_error', 'boost', e);
    }
  },
);
