import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/models/tag.dart';
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const modalCommands = {'tag create', 'tag edit'};

final _namePattern = RegExp(r'^[a-z0-9_-]{1,32}$');

final _invitePattern = RegExp(
  r'(discord\.gg|discord(app)?\.com/invite|dsc\.gg)/[A-Za-z0-9-]+',
  caseSensitive: false,
);

final _massMentionPattern = RegExp(r'@(everyone|here)');

Future<Tag?> _findTag(String name) => withConnection((connection) async {
      final results = await connection.query(
        'SELECT * FROM `tags` WHERE `name` = ? LIMIT 1;',
        [name.toLowerCase()],
      );
      return results.isEmpty ? null : Tag.fromRow(results.first);
    });

Future<int> _countTags(Snowflake owner) => withConnection((connection) async {
      final results = await connection.query(
        'SELECT COUNT(*) AS `count` FROM `tags` WHERE `owner` = ?;',
        [owner.toString()],
      );
      return results.first['count'] as int;
    });

Future<bool> _isBlacklisted(Snowflake user) =>
    withConnection((connection) async {
      final results = await connection.query(
        'SELECT 1 FROM `blacklist` WHERE `user` = ? LIMIT 1;',
        [user.toString()],
      );
      return results.isNotEmpty;
    });

Future<bool> _isAllowedInGuild(Tag tag, Snowflake? guild) async {
  if (guild == null) return true;
  return withConnection((connection) async {
    final settings = await connection.query(
      'SELECT `tags_enabled` FROM `guilds` WHERE `id` = ? LIMIT 1;',
      [guild.toString()],
    );
    if (settings.isNotEmpty && settings.first['tags_enabled'] == 0) {
      return false;
    }
    final blocked = await connection.query(
      'SELECT 1 FROM `tag_guild_blocks` WHERE `guild` = ? AND `tag_id` = ? LIMIT 1;',
      [guild.toString(), tag.id],
    );
    return blocked.isEmpty;
  });
}

Future<void> _unexpected(
  InteractiveContext context,
  User user,
  String command,
  Object e,
) async {
  print('Error in tag $command command: $e');
  await replyError(context, 'tag_error',
      replace: {'&command': command}, details: codeBlock(e.toString(), 'sh'));
}

String? _contentProblem(String content) {
  if (content.trim().isEmpty) return 'tag_content_empty';
  if (_invitePattern.hasMatch(content)) return 'tag_content_invite';
  if (_massMentionPattern.hasMatch(content)) return 'tag_content_mention';
  return null;
}

bool _isOwnerOrBotOwner(Tag tag, User user) =>
    tag.owner == user.id || user.id == ownerId;

Future<Iterable<CommandOptionChoiceBuilder<String>>> _autocomplete(
  AutocompleteContext context, {
  required bool ownOnly,
}) async {
  try {
    final query = '${context.currentValue.toLowerCase()}%';
    return await withConnection((connection) async {
      final results = await connection.query(
        ownOnly
            ? 'SELECT `name` FROM `tags` WHERE `owner` = ? AND `name` LIKE ? ORDER BY `uses` DESC LIMIT 25;'
            : 'SELECT `name` FROM `tags` WHERE `name` LIKE ? ORDER BY `uses` DESC LIMIT 25;',
        [if (ownOnly) context.user.id.toString(), query],
      );
      return results.map(
        (row) => CommandOptionChoiceBuilder(
          name: row['name'] as String,
          value: row['name'] as String,
        ),
      );
    });
  } catch (e) {
    print('Error in tag autocomplete: $e');
    return const [];
  }
}

Future<String> _fillPlaceholders(String content, ChatContext context) async {
  var result = content
      .replaceAll('&mention', '<@${context.user.id}>')
      .replaceAll('&user', context.user.globalName ?? context.user.username);

  if (context.guild != null &&
      (result.contains('&server') || result.contains('&membercount'))) {
    final guild = await context.client.guilds.fetch(
      context.guild!.id,
      withCounts: true,
    );
    result = result.replaceAll('&server', guild.name).replaceAll(
          '&membercount',
          (guild.approximateMemberCount ?? 0).toString(),
        );
  } else {
    result = result.replaceAll('&server', '').replaceAll('&membercount', '');
  }
  return result;
}

Future<Iterable<CommandOptionChoiceBuilder<String>>> allTags(
  AutocompleteContext context,
) =>
    _autocomplete(context, ownOnly: false);

Future<Iterable<CommandOptionChoiceBuilder<String>>> ownTags(
  AutocompleteContext context,
) =>
    _autocomplete(context, ownOnly: true);

final _create = ChatCommand(
  'create',
  'Create a new tag.',
  localizedDescriptions: {
    Locale.de: 'Einen neuen Tag erstellen.',
    Locale.enUs: 'Create a new tag.',
    Locale.da: 'Opret et nyt tag.',
    Locale.esEs: 'Crear un nuevo tag.',
    Locale.fr: 'Créer un nouveau tag.',
    Locale.ru: 'Создать новый тег.',
    Locale.hi: 'एक नया टैग बनाएं।',
    Locale.zhCn: '创建一个新标签。',
    Locale.ja: '新しいタグを作成する。',
    Locale.ko: '새 태그를 만듭니다.',
  },
  (
    ChatContext context,
    @Description('The name of the tag (a-z, 0-9, _ and -).', {
      Locale.de: 'Der Name des Tags (a-z, 0-9, _ und -).',
      Locale.enUs: 'The name of the tag (a-z, 0-9, _ and -).',
      Locale.da: 'Navnet på tagget (a-z, 0-9, _ og -).',
      Locale.esEs: 'El nombre del tag (a-z, 0-9, _ y -).',
      Locale.fr: 'Le nom du tag (a-z, 0-9, _ et -).',
      Locale.ru: 'Имя тега (a-z, 0-9, _ и -).',
      Locale.hi: 'टैग का नाम (a-z, 0-9, _ और -)।',
      Locale.zhCn: '标签名称（a-z、0-9、_ 和 -）。',
      Locale.ja: 'タグの名前（a-z、0-9、_、-）。',
      Locale.ko: '태그 이름 (a-z, 0-9, _ 및 -).',
    })
    String name,
  ) async {
    final user = context.user;
    try {
      name = name.toLowerCase().trim();

      if (await _isBlacklisted(user.id)) {
        await replyError(context, 'tag_blacklisted');
        return;
      }
      if (!_namePattern.hasMatch(name)) {
        await replyError(context, 'tag_name_invalid');
        return;
      }
      if (await _findTag(name) != null) {
        await replyError(context, 'tag_exists', replace: {'&tag': name});
        return;
      }

      final premium = await isUserPremium(user.id, context: context);
      final limit = premium ? premiumTagLimit : freeTagLimit;
      if (await _countTags(user.id) >= limit) {
        await replyError(
          context,
          premium ? 'tag_limit_premium' : 'tag_limit_free',
          replace: {'&limit': limit.toString()},
        );
        return;
      }

      final modal = await (context as InteractionChatContext).getModal(
        title: (await getString(
          user,
          'tag_modal_create',
        ))
            .replaceAll('&tag', name),
        components: [
          TextInputBuilder(
            customId: 'content',
            style: TextInputStyle.paragraph,
            label: await getString(user, 'tag_modal_content'),
            maxLength: 2000,
            isRequired: true,
          ),
        ],
      );

      final content = modal['content'] ?? '';
      final problem = _contentProblem(content);
      if (problem != null) {
        await replyError(modal, problem);
        return;
      }

      try {
        await withConnection(
          (connection) => connection.query(
            'INSERT INTO `tags` (`name`, `owner`, `content`, `created_at`, `updated_at`) '
            'VALUES (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP());',
            [name, user.id.toString(), content],
          ),
        );
      } on MySqlException catch (e) {
        if (e.errorNumber == 1062) {
          await replyError(modal, 'tag_exists', replace: {'&tag': name});
          return;
        }
        rethrow;
      }

      await replySuccess(modal, 'tag_created', replace: {'&tag': name});
    } catch (e) {
      await _unexpected(context, user, 'create', e);
    }
  },
);

final _edit = ChatCommand(
  'edit',
  'Edit one of your tags.',
  localizedDescriptions: {
    Locale.de: 'Einen deiner Tags bearbeiten.',
    Locale.enUs: 'Edit one of your tags.',
    Locale.da: 'Rediger et af dine tags.',
    Locale.esEs: 'Editar uno de tus tags.',
    Locale.fr: 'Modifier un de vos tags.',
    Locale.ru: 'Изменить один из ваших тегов.',
    Locale.hi: 'अपना एक टैग संपादित करें।',
    Locale.zhCn: '编辑你的一个标签。',
    Locale.ja: '自分のタグを編集する。',
    Locale.ko: '내 태그 중 하나를 수정합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to edit.', {
      Locale.de: 'Der Tag, den du bearbeiten möchtest.',
      Locale.enUs: 'The tag you want to edit.',
      Locale.da: 'Det tag, du vil redigere.',
      Locale.esEs: 'El tag que quieres editar.',
      Locale.fr: 'Le tag que vous voulez modifier.',
      Locale.ru: 'Тег, который вы хотите изменить.',
      Locale.hi: 'वह टैग जिसे आप संपादित करना चाहते हैं।',
      Locale.zhCn: '你想编辑的标签。',
      Locale.ja: '編集したいタグ。',
      Locale.ko: '수정할 태그.',
    })
    @Autocomplete(ownTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!_isOwnerOrBotOwner(tag, user)) {
        await replyError(context, 'tag_not_owner');
        return;
      }

      final modal = await (context as InteractionChatContext).getModal(
        title: (await getString(
          user,
          'tag_modal_edit',
        ))
            .replaceAll('&tag', tag.name),
        components: [
          TextInputBuilder(
            customId: 'content',
            style: TextInputStyle.paragraph,
            label: await getString(user, 'tag_modal_content'),
            maxLength: 2000,
            isRequired: true,
            value: tag.content,
          ),
        ],
      );

      final content = modal['content'] ?? '';
      final problem = _contentProblem(content);
      if (problem != null) {
        await replyError(modal, problem);
        return;
      }

      await withConnection(
        (connection) => connection.query(
          'UPDATE `tags` SET `content` = ?, `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;',
          [content, tag.id],
        ),
      );
      await replySuccess(
        modal,
        'tag_edited',
        replace: {'&tag': tag.name},
        level: ResponseLevel.hint,
      );
    } catch (e) {
      await _unexpected(context, user, 'edit', e);
    }
  },
);

final _delete = ChatCommand(
  'delete',
  'Delete one of your tags.',
  localizedDescriptions: {
    Locale.de: 'Einen deiner Tags löschen.',
    Locale.enUs: 'Delete one of your tags.',
    Locale.da: 'Slet et af dine tags.',
    Locale.esEs: 'Eliminar uno de tus tags.',
    Locale.fr: 'Supprimer un de vos tags.',
    Locale.ru: 'Удалить один из ваших тегов.',
    Locale.hi: 'अपना एक टैग हटाएं।',
    Locale.zhCn: '删除你的一个标签。',
    Locale.ja: '自分のタグを削除する。',
    Locale.ko: '내 태그 중 하나를 삭제합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to delete.', {
      Locale.de: 'Der Tag, den du löschen möchtest.',
      Locale.enUs: 'The tag you want to delete.',
      Locale.da: 'Det tag, du vil slette.',
      Locale.esEs: 'El tag que quieres eliminar.',
      Locale.fr: 'Le tag que vous voulez supprimer.',
      Locale.ru: 'Тег, который вы хотите удалить.',
      Locale.hi: 'वह टैग जिसे आप हटाना चाहते हैं।',
      Locale.zhCn: '你想删除的标签。',
      Locale.ja: '削除したいタグ。',
      Locale.ko: '삭제할 태그.',
    })
    @Autocomplete(ownTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!_isOwnerOrBotOwner(tag, user)) {
        await replyError(context, 'tag_not_owner');
        return;
      }

      await withConnection(
        (connection) =>
            connection.query('DELETE FROM `tags` WHERE `id` = ?;', [tag.id]),
      );
      await replySuccess(
        context,
        'tag_deleted',
        replace: {'&tag': tag.name},
        level: ResponseLevel.hint,
      );
    } catch (e) {
      await _unexpected(context, user, 'delete', e);
    }
  },
);

final _show = ChatCommand(
  'show',
  'Show a tag.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag anzeigen.',
    Locale.enUs: 'Show a tag.',
    Locale.da: 'Vis et tag.',
    Locale.esEs: 'Mostrar un tag.',
    Locale.fr: 'Afficher un tag.',
    Locale.ru: 'Показать тег.',
    Locale.hi: 'एक टैग दिखाएं।',
    Locale.zhCn: '显示一个标签。',
    Locale.ja: 'タグを表示する。',
    Locale.ko: '태그를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to show.', {
      Locale.de: 'Der Tag, den du anzeigen möchtest.',
      Locale.enUs: 'The tag you want to show.',
      Locale.da: 'Det tag, du vil vise.',
      Locale.esEs: 'El tag que quieres mostrar.',
      Locale.fr: 'Le tag que vous voulez afficher.',
      Locale.ru: 'Тег, который вы хотите показать.',
      Locale.hi: 'वह टैग जिसे आप दिखाना चाहते हैं।',
      Locale.zhCn: '你想显示的标签。',
      Locale.ja: '表示したいタグ。',
      Locale.ko: '표시할 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!await _isAllowedInGuild(tag, context.guild?.id)) {
        await replyError(context, 'tag_blocked_here');
        return;
      }

      final content = await _fillPlaceholders(tag.content, context);

      final asEmbed =
          tag.embed && await isUserPremium(tag.owner, context: context);

      if (asEmbed) {
        final color = tag.embedColor != null
            ? DiscordColor.parseHexString(tag.embedColor!)
            : await getUserColorFromDatabase(tag.owner);
        await context.respond(
          MessageBuilder(
            allowedMentions: noMentions,
            embeds: [
              EmbedBuilder(
                color: color,
                description: content,
                image: tag.embedImage == null
                    ? null
                    : EmbedImageBuilder(url: Uri.parse(tag.embedImage!)),
              ),
            ],
          ),
        );
      } else {
        await context.respond(
          MessageBuilder(allowedMentions: noMentions, content: content),
        );
      }

      await withConnection(
        (connection) => connection.query(
          'UPDATE `tags` SET `uses` = `uses` + 1 WHERE `id` = ?;',
          [tag.id],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'show', e);
    }
  },
);

final _info = ChatCommand(
  'info',
  'Show information about a tag.',
  localizedDescriptions: {
    Locale.de: 'Informationen über einen Tag anzeigen.',
    Locale.enUs: 'Show information about a tag.',
    Locale.da: 'Vis information om et tag.',
    Locale.esEs: 'Mostrar información sobre un tag.',
    Locale.fr: 'Afficher des informations sur un tag.',
    Locale.ru: 'Показать информацию о теге.',
    Locale.hi: 'किसी टैग की जानकारी दिखाएं।',
    Locale.zhCn: '显示标签的信息。',
    Locale.ja: 'タグの情報を表示する。',
    Locale.ko: '태그 정보를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to know more about.', {
      Locale.de: 'Der Tag, über den du mehr wissen möchtest.',
      Locale.enUs: 'The tag you want to know more about.',
      Locale.da: 'Det tag, du vil vide mere om.',
      Locale.esEs: 'El tag del que quieres saber más.',
      Locale.fr: 'Le tag sur lequel vous voulez en savoir plus.',
      Locale.ru: 'Тег, о котором вы хотите узнать больше.',
      Locale.hi: 'वह टैग जिसके बारे में आप और जानना चाहते हैं।',
      Locale.zhCn: '你想了解更多的标签。',
      Locale.ja: '詳しく知りたいタグ。',
      Locale.ko: '자세히 알고 싶은 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }

      await context.respond(
        MessageBuilder(
          allowedMentions: noMentions,
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(user.id),
              title: tag.name,
              fields: [
                EmbedFieldBuilder(
                  name: await getString(user, 'tag_info_owner'),
                  value: '<@${tag.owner}>',
                  isInline: true,
                ),
                EmbedFieldBuilder(
                  name: await getString(user, 'tag_info_uses'),
                  value: tag.uses.toString(),
                  isInline: true,
                ),
                if (tag.createdAt != null)
                  EmbedFieldBuilder(
                    name: await getString(user, 'tag_info_created'),
                    value:
                        '<t:${tag.createdAt!.millisecondsSinceEpoch ~/ 1000}:D>',
                    isInline: true,
                  ),
              ],
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'info', e);
    }
  },
);

final _list = ChatCommand(
  'list',
  'List the tags of a user.',
  localizedDescriptions: {
    Locale.de: 'Die Tags eines Nutzers auflisten.',
    Locale.enUs: 'List the tags of a user.',
    Locale.da: 'Vis en brugers tags.',
    Locale.esEs: 'Listar los tags de un usuario.',
    Locale.fr: 'Lister les tags d\'un utilisateur.',
    Locale.ru: 'Показать теги пользователя.',
    Locale.hi: 'किसी उपयोगकर्ता के टैग की सूची दिखाएं।',
    Locale.zhCn: '列出某个用户的标签。',
    Locale.ja: 'ユーザーのタグを一覧表示する。',
    Locale.ko: '사용자의 태그 목록을 봅니다.',
  },
  (
    ChatContext context, [
    @Description('Whose tags you want to see. Defaults to you.', {
      Locale.de: 'Wessen Tags du sehen möchtest. Standardmäßig deine.',
      Locale.enUs: 'Whose tags you want to see. Defaults to you.',
      Locale.da: 'Hvis tags du vil se. Som standard dine egne.',
      Locale.esEs: 'De quién quieres ver los tags. Por defecto, los tuyos.',
      Locale.fr: 'De qui vous voulez voir les tags. Par défaut, les vôtres.',
      Locale.ru: 'Чьи теги вы хотите увидеть. По умолчанию ваши.',
      Locale.hi: 'आप किसके टैग देखना चाहते हैं। डिफ़ॉल्ट रूप से आपके।',
      Locale.zhCn: '你想查看谁的标签。默认是你自己。',
      Locale.ja: '誰のタグを見るか。デフォルトはあなた。',
      Locale.ko: '누구의 태그를 볼지. 기본값은 나.',
    })
    User? target,
  ]) async {
    final user = context.user;
    try {
      target ??= user;
      final names = await withConnection((connection) async {
        final results = await connection.query(
          'SELECT `name` FROM `tags` WHERE `owner` = ? ORDER BY `name`;',
          [target!.id.toString()],
        );
        return results.map((row) => row['name'] as String).toList();
      });

      final premium = target.id == user.id
          ? await isUserPremium(user.id, context: context)
          : null;

      var description = names.isEmpty
          ? await getString(user, 'tag_list_empty')
          : names.map((name) => '`$name`').join(', ');
      if (description.length > 4000) {
        description = '${description.substring(0, 3990)}…';
      }

      await context.respond(
        MessageBuilder(
          allowedMentions: noMentions,
          embeds: [
            EmbedBuilder(
              color: await getUserColorFromDatabase(user.id),
              title: (await getString(
                user,
                'tag_list_title',
              ))
                  .replaceAll('&user', target.globalName ?? target.username),
              description: description,
              footer: premium == null
                  ? null
                  : EmbedFooterBuilder(
                      text: (await getString(user, 'tag_list_footer'))
                          .replaceAll('&count', names.length.toString())
                          .replaceAll(
                            '&limit',
                            (premium ? premiumTagLimit : freeTagLimit)
                                .toString(),
                          ),
                    ),
            ),
          ],
        ),
      );
    } catch (e) {
      await _unexpected(context, user, 'list', e);
    }
  },
);

final _transfer = ChatCommand(
  'transfer',
  'Give one of your tags to someone else.',
  localizedDescriptions: {
    Locale.de: 'Einen deiner Tags an jemand anderen übergeben.',
    Locale.enUs: 'Give one of your tags to someone else.',
    Locale.da: 'Giv et af dine tags til en anden.',
    Locale.esEs: 'Dar uno de tus tags a otra persona.',
    Locale.fr: 'Donner un de vos tags à quelqu\'un d\'autre.',
    Locale.ru: 'Передать один из ваших тегов другому.',
    Locale.hi: 'अपना एक टैग किसी और को दें।',
    Locale.zhCn: '把你的一个标签交给别人。',
    Locale.ja: '自分のタグを他の人に渡す。',
    Locale.ko: '내 태그 중 하나를 다른 사람에게 넘깁니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to give away.', {
      Locale.de: 'Der Tag, den du abgeben möchtest.',
      Locale.enUs: 'The tag you want to give away.',
      Locale.da: 'Det tag, du vil give væk.',
      Locale.esEs: 'El tag que quieres regalar.',
      Locale.fr: 'Le tag que vous voulez donner.',
      Locale.ru: 'Тег, который вы хотите отдать.',
      Locale.hi: 'वह टैग जिसे आप देना चाहते हैं।',
      Locale.zhCn: '你想送出的标签。',
      Locale.ja: '渡したいタグ。',
      Locale.ko: '넘겨줄 태그.',
    })
    @Autocomplete(ownTags)
    String name,
    @Description('The new owner of the tag.', {
      Locale.de: 'Der neue Besitzer des Tags.',
      Locale.enUs: 'The new owner of the tag.',
      Locale.da: 'Taggets nye ejer.',
      Locale.esEs: 'El nuevo propietario del tag.',
      Locale.fr: 'Le nouveau propriétaire du tag.',
      Locale.ru: 'Новый владелец тега.',
      Locale.hi: 'टैग का नया मालिक।',
      Locale.zhCn: '标签的新所有者。',
      Locale.ja: 'タグの新しい所有者。',
      Locale.ko: '태그의 새 소유자.',
    })
    User target,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!_isOwnerOrBotOwner(tag, user)) {
        await replyError(context, 'tag_not_owner');
        return;
      }
      if (target.isBot || target.id == tag.owner) {
        await replyError(context, 'tag_transfer_invalid');
        return;
      }

      final targetLimit =
          await isUserPremium(target.id) ? premiumTagLimit : freeTagLimit;
      if (await _countTags(target.id) >= targetLimit) {
        await replyError(
          context,
          'tag_transfer_full',
          replace: {'&user': target.globalName ?? target.username},
        );
        return;
      }

      await withConnection(
        (connection) => connection.query(
          'UPDATE `tags` SET `owner` = ?, `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;',
          [target.id.toString(), tag.id],
        ),
      );
      await replySuccess(
        context,
        'tag_transferred',
        replace: {
          '&tag': tag.name,
          '&user': target.globalName ?? target.username,
        },
      );
    } catch (e) {
      await _unexpected(context, user, 'transfer', e);
    }
  },
);

final _embed = ChatCommand(
  'embed',
  'Show a tag as embed (Premium).',
  localizedDescriptions: {
    Locale.de: 'Einen Tag als Embed anzeigen (Premium).',
    Locale.enUs: 'Show a tag as embed (Premium).',
    Locale.da: 'Vis et tag som embed (Premium).',
    Locale.esEs: 'Mostrar un tag como embed (Premium).',
    Locale.fr: 'Afficher un tag en embed (Premium).',
    Locale.ru: 'Показывать тег как embed (Premium).',
    Locale.hi: 'टैग को एम्बेड के रूप में दिखाएं (Premium)।',
    Locale.zhCn: '以嵌入形式显示标签（Premium）。',
    Locale.ja: 'タグを埋め込みで表示する（Premium）。',
    Locale.ko: '태그를 임베드로 표시합니다 (Premium).',
  },
  (
    ChatContext context,
    @Description('The tag you want to change.', {
      Locale.de: 'Der Tag, den du ändern möchtest.',
      Locale.enUs: 'The tag you want to change.',
      Locale.da: 'Det tag, du vil ændre.',
      Locale.esEs: 'El tag que quieres cambiar.',
      Locale.fr: 'Le tag que vous voulez changer.',
      Locale.ru: 'Тег, который вы хотите изменить.',
      Locale.hi: 'वह टैग जिसे आप बदलना चाहते हैं।',
      Locale.zhCn: '你想更改的标签。',
      Locale.ja: '変更したいタグ。',
      Locale.ko: '변경할 태그.',
    })
    @Autocomplete(ownTags)
    String name,
    @Description('Whether the tag is shown as embed.', {
      Locale.de: 'Ob der Tag als Embed angezeigt wird.',
      Locale.enUs: 'Whether the tag is shown as embed.',
      Locale.da: 'Om tagget vises som embed.',
      Locale.esEs: 'Si el tag se muestra como embed.',
      Locale.fr: 'Si le tag est affiché en embed.',
      Locale.ru: 'Показывать ли тег как embed.',
      Locale.hi: 'क्या टैग एम्बेड के रूप में दिखाया जाए।',
      Locale.zhCn: '标签是否以嵌入形式显示。',
      Locale.ja: 'タグを埋め込みで表示するかどうか。',
      Locale.ko: '태그를 임베드로 표시할지 여부.',
    })
    bool enabled, [
    @Description('Hex color of the embed, e.g. #abc123.', {
      Locale.de: 'Hex-Farbe des Embeds, z. B. #abc123.',
      Locale.enUs: 'Hex color of the embed, e.g. #abc123.',
      Locale.da: 'Hex-farve for embed, f.eks. #abc123.',
      Locale.esEs: 'Color hex del embed, p. ej. #abc123.',
      Locale.fr: 'Couleur hex de l\'embed, ex. #abc123.',
      Locale.ru: 'Hex-цвет embed, например #abc123.',
      Locale.hi: 'एम्बेड का हेक्स रंग, जैसे #abc123।',
      Locale.zhCn: '嵌入的十六进制颜色，例如 #abc123。',
      Locale.ja: '埋め込みの16進カラー（例: #abc123）。',
      Locale.ko: '임베드의 헥스 색상, 예: #abc123.',
    })
    String? color,
    @Description('Link to an image shown in the embed.', {
      Locale.de: 'Link zu einem Bild, das im Embed angezeigt wird.',
      Locale.enUs: 'Link to an image shown in the embed.',
      Locale.da: 'Link til et billede, der vises i embed.',
      Locale.esEs: 'Enlace a una imagen que se muestra en el embed.',
      Locale.fr: 'Lien vers une image affichée dans l\'embed.',
      Locale.ru: 'Ссылка на изображение в embed.',
      Locale.hi: 'एम्बेड में दिखने वाली छवि का लिंक।',
      Locale.zhCn: '嵌入中显示的图片链接。',
      Locale.ja: '埋め込みに表示する画像のリンク。',
      Locale.ko: '임베드에 표시할 이미지 링크.',
    })
    String? image,
  ]) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!_isOwnerOrBotOwner(tag, user)) {
        await replyError(context, 'tag_not_owner');
        return;
      }
      if (!await isUserPremium(user.id, context: context)) {
        await replyError(context, 'tag_premium_only');
        return;
      }
      if (color != null &&
          !RegExp(r'^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$').hasMatch(color)) {
        await replyError(context, 'color_invalid_format');
        return;
      }
      if (image != null) {
        final uri = Uri.tryParse(image);
        if (uri == null ||
            uri.scheme != 'https' ||
            !allowedImageHosts.contains(uri.host.toLowerCase())) {
          await replyError(
            context,
            'tag_image_invalid',
            replace: {'&hosts': allowedImageHosts.join(', ')},
          );
          return;
        }
      }

      await withConnection(
        (connection) => connection.query(
          'UPDATE `tags` SET `embed` = ?, `embed_color` = COALESCE(?, `embed_color`), '
          '`embed_image` = COALESCE(?, `embed_image`), `updated_at` = UTC_TIMESTAMP() WHERE `id` = ?;',
          [enabled ? 1 : 0, color, image, tag.id],
        ),
      );
      await replySuccess(
        context,
        enabled ? 'tag_embed_on' : 'tag_embed_off',
        replace: {'&tag': tag.name},
        level: ResponseLevel.hint,
      );
    } catch (e) {
      await _unexpected(context, user, 'embed', e);
    }
  },
);

final _report = ChatCommand(
  'report',
  'Report a tag with inappropriate content.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag mit unangemessenem Inhalt melden.',
    Locale.enUs: 'Report a tag with inappropriate content.',
    Locale.da: 'Rapporter et tag med upassende indhold.',
    Locale.esEs: 'Reportar un tag con contenido inapropiado.',
    Locale.fr: 'Signaler un tag au contenu inapproprié.',
    Locale.ru: 'Пожаловаться на тег с неприемлемым содержимым.',
    Locale.hi: 'अनुचित सामग्री वाले टैग की रिपोर्ट करें।',
    Locale.zhCn: '举报含有不当内容的标签。',
    Locale.ja: '不適切な内容のタグを報告する。',
    Locale.ko: '부적절한 내용의 태그를 신고합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to report.', {
      Locale.de: 'Der Tag, den du melden möchtest.',
      Locale.enUs: 'The tag you want to report.',
      Locale.da: 'Det tag, du vil rapportere.',
      Locale.esEs: 'El tag que quieres reportar.',
      Locale.fr: 'Le tag que vous voulez signaler.',
      Locale.ru: 'Тег, на который вы хотите пожаловаться.',
      Locale.hi: 'वह टैग जिसकी आप रिपोर्ट करना चाहते हैं।',
      Locale.zhCn: '你想举报的标签。',
      Locale.ja: '報告したいタグ。',
      Locale.ko: '신고할 태그.',
    })
    @Autocomplete(allTags)
    String name,
    @Description('Why you are reporting this tag.', {
      Locale.de: 'Warum du diesen Tag meldest.',
      Locale.enUs: 'Why you are reporting this tag.',
      Locale.da: 'Hvorfor du rapporterer dette tag.',
      Locale.esEs: 'Por qué reportas este tag.',
      Locale.fr: 'Pourquoi vous signalez ce tag.',
      Locale.ru: 'Почему вы жалуетесь на этот тег.',
      Locale.hi: 'आप इस टैग की रिपोर्ट क्यों कर रहे हैं।',
      Locale.zhCn: '你举报这个标签的原因。',
      Locale.ja: 'このタグを報告する理由。',
      Locale.ko: '이 태그를 신고하는 이유.',
    })
    String reason,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (reason.length > 500) reason = reason.substring(0, 500);

      final inserted = await withConnection((connection) async {
        final result = await connection.query(
          'INSERT IGNORE INTO `tag_reports` (`tag_id`, `reporter`, `guild`, `reason`, `created_at`, `updated_at`) '
          'VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP());',
          [tag.id, user.id.toString(), context.guild?.id.toString(), reason],
        );
        return (result.affectedRows ?? 0) > 0;
      });

      if (!inserted) {
        await replyError(context, 'tag_already_reported');
        return;
      }

      final reportChannel = tagReportChannel;
      if (reportChannel != null) {
        final channel =
            await context.client.channels[Snowflake(reportChannel)].get();
        if (channel is TextChannel) {
          await channel.sendMessage(
            MessageBuilder(
              allowedMentions: noMentions,
              embeds: [
                EmbedBuilder(
                  color: DiscordColor.parseHexString(errorColor),
                  title: 'Tag report: ${tag.name}',
                  timestamp: DateTime.now().toUtc(),
                  fields: [
                    EmbedFieldBuilder(
                      name: 'Owner',
                      value: '<@${tag.owner}> (${tag.owner})',
                      isInline: true,
                    ),
                    EmbedFieldBuilder(
                      name: 'Reporter',
                      value: '<@${user.id}> (${user.id})',
                      isInline: true,
                    ),
                    EmbedFieldBuilder(
                      name: 'Reason',
                      value: reason,
                      isInline: false,
                    ),
                    EmbedFieldBuilder(
                      name: 'Content',
                      value: tag.content.length > 1000
                          ? '${tag.content.substring(0, 1000)}…'
                          : tag.content,
                      isInline: false,
                    ),
                  ],
                ),
              ],
            ),
          );
        }
      }

      await replySuccess(context, 'tag_reported', level: ResponseLevel.hint);
    } catch (e) {
      await _unexpected(context, user, 'report', e);
    }
  },
);

final _block = ChatCommand(
  'block',
  'Block a tag on this server.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag auf diesem Server sperren.',
    Locale.enUs: 'Block a tag on this server.',
    Locale.da: 'Bloker et tag på denne server.',
    Locale.esEs: 'Bloquear un tag en este servidor.',
    Locale.fr: 'Bloquer un tag sur ce serveur.',
    Locale.ru: 'Заблокировать тег на этом сервере.',
    Locale.hi: 'इस सर्वर पर एक टैग ब्लॉक करें।',
    Locale.zhCn: '在本服务器上屏蔽一个标签。',
    Locale.ja: 'このサーバーでタグをブロックする。',
    Locale.ko: '이 서버에서 태그를 차단합니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context,
    @Description('The tag you want to block.', {
      Locale.de: 'Der Tag, den du sperren möchtest.',
      Locale.enUs: 'The tag you want to block.',
      Locale.da: 'Det tag, du vil blokere.',
      Locale.esEs: 'El tag que quieres bloquear.',
      Locale.fr: 'Le tag que vous voulez bloquer.',
      Locale.ru: 'Тег, который вы хотите заблокировать.',
      Locale.hi: 'वह टैग जिसे आप ब्लॉक करना चाहते हैं।',
      Locale.zhCn: '你想屏蔽的标签。',
      Locale.ja: 'ブロックしたいタグ。',
      Locale.ko: '차단할 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      await withConnection(
        (connection) => connection.query(
          'INSERT IGNORE INTO `tag_guild_blocks` (`guild`, `tag_id`) VALUES (?, ?);',
          [context.guild!.id.toString(), tag.id],
        ),
      );
      await replySuccess(context, 'tag_blocked', replace: {'&tag': tag.name});
    } catch (e) {
      await _unexpected(context, user, 'block', e);
    }
  },
);

final _unblock = ChatCommand(
  'unblock',
  'Unblock a tag on this server.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag auf diesem Server wieder freigeben.',
    Locale.enUs: 'Unblock a tag on this server.',
    Locale.da: 'Fjern blokeringen af et tag på denne server.',
    Locale.esEs: 'Desbloquear un tag en este servidor.',
    Locale.fr: 'Débloquer un tag sur ce serveur.',
    Locale.ru: 'Разблокировать тег на этом сервере.',
    Locale.hi: 'इस सर्वर पर एक टैग अनब्लॉक करें।',
    Locale.zhCn: '在本服务器上取消屏蔽一个标签。',
    Locale.ja: 'このサーバーでタグのブロックを解除する。',
    Locale.ko: '이 서버에서 태그 차단을 해제합니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context,
    @Description('The tag you want to unblock.', {
      Locale.de: 'Der Tag, den du freigeben möchtest.',
      Locale.enUs: 'The tag you want to unblock.',
      Locale.da: 'Det tag, du vil fjerne blokeringen af.',
      Locale.esEs: 'El tag que quieres desbloquear.',
      Locale.fr: 'Le tag que vous voulez débloquer.',
      Locale.ru: 'Тег, который вы хотите разблокировать.',
      Locale.hi: 'वह टैग जिसे आप अनब्लॉक करना चाहते हैं।',
      Locale.zhCn: '你想取消屏蔽的标签。',
      Locale.ja: 'ブロックを解除したいタグ。',
      Locale.ko: '차단 해제할 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await _findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      await withConnection(
        (connection) => connection.query(
          'DELETE FROM `tag_guild_blocks` WHERE `guild` = ? AND `tag_id` = ?;',
          [context.guild!.id.toString(), tag.id],
        ),
      );
      await replySuccess(
        context,
        'tag_unblocked',
        replace: {'&tag': tag.name},
      );
    } catch (e) {
      await _unexpected(context, user, 'unblock', e);
    }
  },
);

final _toggle = ChatCommand(
  'toggle',
  'Turn tags on or off for this server.',
  localizedDescriptions: {
    Locale.de: 'Tags auf diesem Server an- oder ausschalten.',
    Locale.enUs: 'Turn tags on or off for this server.',
    Locale.da: 'Slå tags til eller fra på denne server.',
    Locale.esEs: 'Activar o desactivar los tags en este servidor.',
    Locale.fr: 'Activer ou désactiver les tags sur ce serveur.',
    Locale.ru: 'Включить или отключить теги на этом сервере.',
    Locale.hi: 'इस सर्वर पर टैग चालू या बंद करें।',
    Locale.zhCn: '在本服务器上开启或关闭标签。',
    Locale.ja: 'このサーバーでタグをオン・オフする。',
    Locale.ko: '이 서버에서 태그를 켜거나 끕니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context,
    @Description('Whether tags can be used on this server.', {
      Locale.de: 'Ob Tags auf diesem Server benutzt werden können.',
      Locale.enUs: 'Whether tags can be used on this server.',
      Locale.da: 'Om tags kan bruges på denne server.',
      Locale.esEs: 'Si los tags se pueden usar en este servidor.',
      Locale.fr: 'Si les tags peuvent être utilisés sur ce serveur.',
      Locale.ru: 'Можно ли использовать теги на этом сервере.',
      Locale.hi: 'क्या इस सर्वर पर टैग इस्तेमाल किए जा सकते हैं।',
      Locale.zhCn: '本服务器上是否可以使用标签。',
      Locale.ja: 'このサーバーでタグを使えるかどうか。',
      Locale.ko: '이 서버에서 태그를 사용할 수 있는지 여부.',
    })
    bool enabled,
  ) async {
    final user = context.user;
    try {
      await withConnection(
        (connection) => connection.query(
          'UPDATE `guilds` SET `tags_enabled` = ? WHERE `id` = ?;',
          [enabled ? 1 : 0, context.guild!.id.toString()],
        ),
      );
      await replySuccess(
        context,
        enabled ? 'tag_toggle_on' : 'tag_toggle_off',
      );
    } catch (e) {
      await _unexpected(context, user, 'toggle', e);
    }
  },
);

final tag = ChatGroup(
  'tag',
  'Create and use tags, your own commands.',
  localizedDescriptions: {
    Locale.de: 'Tags erstellen und benutzen, deine eigenen Befehle.',
    Locale.enUs: 'Create and use tags, your own commands.',
    Locale.da: 'Opret og brug tags, dine egne kommandoer.',
    Locale.esEs: 'Crea y usa tags, tus propios comandos.',
    Locale.fr: 'Créez et utilisez des tags, vos propres commandes.',
    Locale.ru: 'Создавайте и используйте теги, свои собственные команды.',
    Locale.hi: 'टैग बनाएं और इस्तेमाल करें, आपके अपने कमांड।',
    Locale.zhCn: '创建和使用标签，你自己的命令。',
    Locale.ja: 'タグを作成して使う、あなただけのコマンド。',
    Locale.ko: '태그를 만들고 사용하세요, 나만의 명령어.',
  },
  children: [
    _create,
    _edit,
    _delete,
    _show,
    _info,
    _list,
    _transfer,
    _embed,
    _report,
    _block,
    _unblock,
    _toggle,
  ],
);
