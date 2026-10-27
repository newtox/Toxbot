import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/services/premium.dart';
import 'package:toxbot/services/tags.dart';
import 'package:toxbot/utils/config.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

final _create = ChatCommand(
  'create',
  'Create a new tag.',
  localizedDescriptions: {
    Locale.de: 'Einen neuen Tag erstellen.',
    Locale.enUs: 'Create a new tag.',
    Locale.da: 'Opret et nyt tag.',
    Locale.esEs: 'Crear un tag nuevo.',
    Locale.fr: 'Créer un nouveau tag.',
    Locale.ru: 'Создать новый тег.',
    Locale.hi: 'नया टैग बनाएँ।',
    Locale.zhCn: '创建一个新标签。',
    Locale.ja: '新しいタグを作成します。',
    Locale.ko: '새 태그를 만듭니다.',
  },
  (
    ChatContext context,
    @Description('The name of the tag (a-z, 0-9, _ and -).', {
      Locale.de: 'Der Name des Tags (a-z, 0-9, _ und -).',
      Locale.enUs: 'The name of the tag (a-z, 0-9, _ and -).',
      Locale.da: 'Taggets navn (a-z, 0-9, _ og -).',
      Locale.esEs: 'El nombre del tag (a-z, 0-9, _ y -).',
      Locale.fr: 'Le nom du tag (a-z, 0-9, _ et -).',
      Locale.ru: 'Название тега (a-z, 0-9, _ и -).',
      Locale.hi: 'टैग का नाम (a-z, 0-9, _ और -)।',
      Locale.zhCn: '标签名称（a-z、0-9、_ 和 -）。',
      Locale.ja: 'タグの名前（a-z、0-9、_、-）。',
      Locale.ko: '태그 이름 (a-z, 0-9, _, -).',
    })
    String name,
  ) async {
    final user = context.user;
    try {
      name = name.toLowerCase().trim();

      if (!tagNamePattern.hasMatch(name)) {
        await replyError(context, 'tag_name_invalid');
        return;
      }
      if (await findTag(name) != null) {
        await replyError(context, 'tag_exists', replace: {'&tag': name});
        return;
      }

      final premium = await isUserPremium(user.id, context: context);
      final limit = premium ? premiumTagLimit : freeTagLimit;
      if (await countTags(user.id) >= limit) {
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
      final problem = tagContentProblem(content);
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
      await replyCommandError(context, 'tag_error', 'create', e);
    }
  },
);

final _edit = ChatCommand(
  'edit',
  'Edit one of your tags.',
  localizedDescriptions: {
    Locale.de: 'Einen deiner Tags bearbeiten.',
    Locale.enUs: 'Edit one of your tags.',
    Locale.da: 'Redigér et af dine tags.',
    Locale.esEs: 'Editar uno de tus tags.',
    Locale.fr: 'Modifier un de tes tags.',
    Locale.ru: 'Изменить один из своих тегов.',
    Locale.hi: 'अपना कोई टैग संपादित करें।',
    Locale.zhCn: '编辑你的一个标签。',
    Locale.ja: '自分のタグを編集します。',
    Locale.ko: '내 태그를 수정합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to edit.', {
      Locale.de: 'Der Tag, den du bearbeiten möchtest.',
      Locale.enUs: 'The tag you want to edit.',
      Locale.da: 'Det tag, du vil redigere.',
      Locale.esEs: 'El tag que quieres editar.',
      Locale.fr: 'Le tag que tu veux modifier.',
      Locale.ru: 'Тег, который хочешь изменить.',
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
      final tag = await findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!isTagOwnerOrBotOwner(tag, user)) {
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
      final problem = tagContentProblem(content);
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
      await replyCommandError(context, 'tag_error', 'edit', e);
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
    Locale.esEs: 'Borrar uno de tus tags.',
    Locale.fr: 'Supprimer un de tes tags.',
    Locale.ru: 'Удалить один из своих тегов.',
    Locale.hi: 'अपना कोई टैग हटाएँ।',
    Locale.zhCn: '删除你的一个标签。',
    Locale.ja: '自分のタグを削除します。',
    Locale.ko: '내 태그를 삭제합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to delete.', {
      Locale.de: 'Der Tag, den du löschen möchtest.',
      Locale.enUs: 'The tag you want to delete.',
      Locale.da: 'Det tag, du vil slette.',
      Locale.esEs: 'El tag que quieres borrar.',
      Locale.fr: 'Le tag que tu veux supprimer.',
      Locale.ru: 'Тег, который хочешь удалить.',
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
      final tag = await findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!isTagOwnerOrBotOwner(tag, user)) {
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
      await replyCommandError(context, 'tag_error', 'delete', e);
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
    Locale.hi: 'कोई टैग दिखाएँ।',
    Locale.zhCn: '显示一个标签。',
    Locale.ja: 'タグを表示します。',
    Locale.ko: '태그를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to show.', {
      Locale.de: 'Der Tag, den du anzeigen möchtest.',
      Locale.enUs: 'The tag you want to show.',
      Locale.da: 'Det tag, du vil vise.',
      Locale.esEs: 'El tag que quieres mostrar.',
      Locale.fr: 'Le tag que tu veux afficher.',
      Locale.ru: 'Тег, который хочешь показать.',
      Locale.hi: 'वह टैग जिसे आप दिखाना चाहते हैं।',
      Locale.zhCn: '你想显示的标签。',
      Locale.ja: '表示したいタグ。',
      Locale.ko: '표시할 태그.',
    })
    @Autocomplete(shownTags)
    String name,
  ) async {
    try {
      final guild = context.guild;
      final serverTag =
          guild == null ? null : await findServerTag(guild.id, name);
      if (serverTag != null) {
        await context.respond(
          MessageBuilder(
            allowedMentions: noMentions,
            content: await fillTagPlaceholders(serverTag.content, context),
          ),
        );
        await countServerTagUse(serverTag);
        return;
      }

      final tag = await findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!await isTagAllowedInGuild(tag, context.guild?.id)) {
        await replyError(context, 'tag_blocked_here');
        return;
      }

      final content = await fillTagPlaceholders(tag.content, context);

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
      await replyCommandError(context, 'tag_error', 'show', e);
    }
  },
);

final _info = ChatCommand(
  'info',
  'Show information about a tag.',
  localizedDescriptions: {
    Locale.de: 'Infos über einen Tag anzeigen.',
    Locale.enUs: 'Show information about a tag.',
    Locale.da: 'Vis oplysninger om et tag.',
    Locale.esEs: 'Mostrar información sobre un tag.',
    Locale.fr: 'Afficher des informations sur un tag.',
    Locale.ru: 'Показать информацию о теге.',
    Locale.hi: 'किसी टैग की जानकारी दिखाएँ।',
    Locale.zhCn: '显示标签的信息。',
    Locale.ja: 'タグの情報を表示します。',
    Locale.ko: '태그 정보를 표시합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to know more about.', {
      Locale.de: 'Der Tag, über den du mehr wissen möchtest.',
      Locale.enUs: 'The tag you want to know more about.',
      Locale.da: 'Det tag, du vil vide mere om.',
      Locale.esEs: 'El tag del que quieres saber más.',
      Locale.fr: 'Le tag sur lequel tu veux en savoir plus.',
      Locale.ru: 'Тег, о котором хочешь узнать больше.',
      Locale.hi: 'वह टैग जिसके बारे में आप और जानना चाहते हैं।',
      Locale.zhCn: '你想了解的标签。',
      Locale.ja: '詳しく知りたいタグ。',
      Locale.ko: '자세히 알고 싶은 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    final user = context.user;
    try {
      final tag = await findTag(name);
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
      await replyCommandError(context, 'tag_error', 'info', e);
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
    Locale.hi: 'किसी उपयोगकर्ता के टैग की सूची दिखाएँ।',
    Locale.zhCn: '列出某个用户的标签。',
    Locale.ja: 'ユーザーのタグを一覧表示します。',
    Locale.ko: '사용자의 태그 목록을 표시합니다.',
  },
  (
    ChatContext context, [
    @Description('Whose tags you want to see. Defaults to you.', {
      Locale.de: 'Wessen Tags du sehen möchtest. Standardmäßig deine.',
      Locale.enUs: 'Whose tags you want to see. Defaults to you.',
      Locale.da: 'Hvis tags du vil se. Standard er dig.',
      Locale.esEs: 'De quién quieres ver los tags. Por defecto, los tuyos.',
      Locale.fr: 'De qui tu veux voir les tags. Par défaut, les tiens.',
      Locale.ru: 'Чьи теги хочешь увидеть. По умолчанию твои.',
      Locale.hi: 'आप किसके टैग देखना चाहते हैं। डिफ़ॉल्ट रूप से आपके।',
      Locale.zhCn: '你想查看谁的标签。默认是你自己。',
      Locale.ja: '見たいタグの持ち主。既定は自分。',
      Locale.ko: '태그를 볼 사용자. 기본값은 나.',
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
      await replyCommandError(context, 'tag_error', 'list', e);
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
    Locale.esEs: 'Darle uno de tus tags a otra persona.',
    Locale.fr: 'Donner un de tes tags à quelqu\'un d\'autre.',
    Locale.ru: 'Передать один из своих тегов другому.',
    Locale.hi: 'अपना कोई टैग किसी और को दें।',
    Locale.zhCn: '把你的一个标签转给别人。',
    Locale.ja: '自分のタグを他の人に譲ります。',
    Locale.ko: '내 태그를 다른 사람에게 넘깁니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to give away.', {
      Locale.de: 'Der Tag, den du abgeben möchtest.',
      Locale.enUs: 'The tag you want to give away.',
      Locale.da: 'Det tag, du vil give væk.',
      Locale.esEs: 'El tag que quieres regalar.',
      Locale.fr: 'Le tag que tu veux donner.',
      Locale.ru: 'Тег, который хочешь отдать.',
      Locale.hi: 'वह टैग जिसे आप देना चाहते हैं।',
      Locale.zhCn: '你想转让的标签。',
      Locale.ja: '譲りたいタグ。',
      Locale.ko: '넘길 태그.',
    })
    @Autocomplete(ownTags)
    String name,
    @Description('The new owner of the tag.', {
      Locale.de: 'Der neue Besitzer des Tags.',
      Locale.enUs: 'The new owner of the tag.',
      Locale.da: 'Taggets nye ejer.',
      Locale.esEs: 'El nuevo dueño del tag.',
      Locale.fr: 'Le nouveau propriétaire du tag.',
      Locale.ru: 'Новый владелец тега.',
      Locale.hi: 'टैग का नया मालिक।',
      Locale.zhCn: '标签的新主人。',
      Locale.ja: 'タグの新しい持ち主。',
      Locale.ko: '태그의 새 주인.',
    })
    User target,
  ) async {
    final user = context.user;
    try {
      final tag = await findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!isTagOwnerOrBotOwner(tag, user)) {
        await replyError(context, 'tag_not_owner');
        return;
      }
      if (target.isBot || target.id == tag.owner) {
        await replyError(context, 'tag_transfer_invalid');
        return;
      }

      final targetLimit =
          await isUserPremium(target.id) ? premiumTagLimit : freeTagLimit;
      if (await countTags(target.id) >= targetLimit) {
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
      await replyCommandError(context, 'tag_error', 'transfer', e);
    }
  },
);

final _embed = ChatCommand(
  'embed',
  'Show a tag as an embed (Premium).',
  localizedDescriptions: {
    Locale.de: 'Einen Tag als Embed anzeigen (Premium).',
    Locale.enUs: 'Show a tag as an embed (Premium).',
    Locale.da: 'Vis et tag som embed (Premium).',
    Locale.esEs: 'Mostrar un tag como embed (Premium).',
    Locale.fr: 'Afficher un tag en embed (Premium).',
    Locale.ru: 'Показать тег как эмбед (Премиум).',
    Locale.hi: 'टैग को एम्बेड के रूप में दिखाएँ (प्रीमियम)।',
    Locale.zhCn: '以嵌入形式显示标签（高级版）。',
    Locale.ja: 'タグを埋め込みで表示します（プレミアム）。',
    Locale.ko: '태그를 임베드로 표시합니다(프리미엄).',
  },
  (
    ChatContext context,
    @Description('The tag you want to change.', {
      Locale.de: 'Der Tag, den du ändern möchtest.',
      Locale.enUs: 'The tag you want to change.',
      Locale.da: 'Det tag, du vil ændre.',
      Locale.esEs: 'El tag que quieres cambiar.',
      Locale.fr: 'Le tag que tu veux changer.',
      Locale.ru: 'Тег, который хочешь изменить.',
      Locale.hi: 'वह टैग जिसे आप बदलना चाहते हैं।',
      Locale.zhCn: '你想修改的标签。',
      Locale.ja: '変更したいタグ。',
      Locale.ko: '변경할 태그.',
    })
    @Autocomplete(ownTags)
    String name,
    @Description('Whether the tag is shown as an embed.', {
      Locale.de: 'Ob der Tag als Embed angezeigt wird.',
      Locale.enUs: 'Whether the tag is shown as an embed.',
      Locale.da: 'Om tagget vises som embed.',
      Locale.esEs: 'Si el tag se muestra como embed.',
      Locale.fr: 'Si le tag est affiché en embed.',
      Locale.ru: 'Показывать ли тег как эмбед.',
      Locale.hi: 'टैग एम्बेड के रूप में दिखे या नहीं।',
      Locale.zhCn: '标签是否以嵌入形式显示。',
      Locale.ja: 'タグを埋め込みで表示するか。',
      Locale.ko: '태그를 임베드로 표시할지 여부.',
    })
    bool enabled, [
    @Description('Hex color of the embed, e.g. #abc123.', {
      Locale.de: 'Hex-Farbe des Embeds, z. B. #abc123.',
      Locale.enUs: 'Hex color of the embed, e.g. #abc123.',
      Locale.da: 'Embeddets hex-farve, f.eks. #abc123.',
      Locale.esEs: 'Color hex del embed, p. ej. #abc123.',
      Locale.fr: 'Couleur hex de l\'embed, ex. #abc123.',
      Locale.ru: 'Hex-цвет эмбеда, например #abc123.',
      Locale.hi: 'एम्बेड का हेक्स रंग, जैसे #abc123।',
      Locale.zhCn: '嵌入的十六进制颜色，例如 #abc123。',
      Locale.ja: '埋め込みの 16 進カラー（例: #abc123）。',
      Locale.ko: '임베드의 16진수 색상, 예: #abc123.',
    })
    String? color,
    @Description('Link to an image shown in the embed.', {
      Locale.de: 'Link zu einem Bild, das im Embed angezeigt wird.',
      Locale.enUs: 'Link to an image shown in the embed.',
      Locale.da: 'Link til et billede, der vises i embeddet.',
      Locale.esEs: 'Enlace a una imagen que se muestra en el embed.',
      Locale.fr: 'Lien vers une image affichée dans l\'embed.',
      Locale.ru: 'Ссылка на картинку в эмбеде.',
      Locale.hi: 'एम्बेड में दिखने वाली तस्वीर का लिंक।',
      Locale.zhCn: '嵌入中显示的图片链接。',
      Locale.ja: '埋め込みに表示する画像のリンク。',
      Locale.ko: '임베드에 표시할 이미지 링크.',
    })
    String? image,
  ]) async {
    final user = context.user;
    try {
      final tag = await findTag(name);
      if (tag == null) {
        await replyError(context, 'tag_not_found', replace: {'&tag': name});
        return;
      }
      if (!isTagOwnerOrBotOwner(tag, user)) {
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
      await replyCommandError(context, 'tag_error', 'embed', e);
    }
  },
);

final _report = ChatCommand(
  'report',
  'Report a tag with inappropriate content.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag mit unangemessenem Inhalt melden.',
    Locale.enUs: 'Report a tag with inappropriate content.',
    Locale.da: 'Anmeld et tag med upassende indhold.',
    Locale.esEs: 'Denunciar un tag con contenido inapropiado.',
    Locale.fr: 'Signaler un tag au contenu inapproprié.',
    Locale.ru: 'Пожаловаться на тег с неприемлемым содержимым.',
    Locale.hi: 'अनुचित सामग्री वाले टैग की रिपोर्ट करें।',
    Locale.zhCn: '举报内容不当的标签。',
    Locale.ja: '不適切な内容のタグを報告します。',
    Locale.ko: '부적절한 태그를 신고합니다.',
  },
  (
    ChatContext context,
    @Description('The tag you want to report.', {
      Locale.de: 'Der Tag, den du melden möchtest.',
      Locale.enUs: 'The tag you want to report.',
      Locale.da: 'Det tag, du vil anmelde.',
      Locale.esEs: 'El tag que quieres denunciar.',
      Locale.fr: 'Le tag que tu veux signaler.',
      Locale.ru: 'Тег, на который хочешь пожаловаться.',
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
      Locale.da: 'Hvorfor du anmelder dette tag.',
      Locale.esEs: 'Por qué denuncias este tag.',
      Locale.fr: 'Pourquoi tu signales ce tag.',
      Locale.ru: 'Почему ты жалуешься на этот тег.',
      Locale.hi: 'आप इस टैग की रिपोर्ट क्यों कर रहे हैं।',
      Locale.zhCn: '你举报此标签的原因。',
      Locale.ja: 'このタグを報告する理由。',
      Locale.ko: '이 태그를 신고하는 이유.',
    })
    String reason,
  ) async {
    final user = context.user;
    try {
      final tag = await findTag(name);
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
      await replyCommandError(context, 'tag_error', 'report', e);
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
    Locale.hi: 'इस सर्वर पर कोई टैग ब्लॉक करें।',
    Locale.zhCn: '在此服务器屏蔽一个标签。',
    Locale.ja: 'このサーバーでタグをブロックします。',
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
      Locale.fr: 'Le tag que tu veux bloquer.',
      Locale.ru: 'Тег, который хочешь заблокировать.',
      Locale.hi: 'वह टैग जिसे आप ब्लॉक करना चाहते हैं।',
      Locale.zhCn: '你想屏蔽的标签。',
      Locale.ja: 'ブロックしたいタグ。',
      Locale.ko: '차단할 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    try {
      final tag = await findTag(name);
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
      await replyCommandError(context, 'tag_error', 'block', e);
    }
  },
);

final _unblock = ChatCommand(
  'unblock',
  'Unblock a tag on this server.',
  localizedDescriptions: {
    Locale.de: 'Einen Tag auf diesem Server wieder freigeben.',
    Locale.enUs: 'Unblock a tag on this server.',
    Locale.da: 'Ophæv blokeringen af et tag på denne server.',
    Locale.esEs: 'Desbloquear un tag en este servidor.',
    Locale.fr: 'Débloquer un tag sur ce serveur.',
    Locale.ru: 'Разблокировать тег на этом сервере.',
    Locale.hi: 'इस सर्वर पर कोई टैग अनब्लॉक करें।',
    Locale.zhCn: '在此服务器解除屏蔽一个标签。',
    Locale.ja: 'このサーバーでタグのブロックを解除します。',
    Locale.ko: '이 서버에서 태그 차단을 해제합니다.',
  },
  checks: [
    GuildCheck.all(),
    PermissionsCheck(Permissions.manageGuild, allowsOverrides: false),
  ],
  (
    ChatContext context,
    @Description('The tag you want to unblock.', {
      Locale.de: 'Der Tag, den du wieder freigeben möchtest.',
      Locale.enUs: 'The tag you want to unblock.',
      Locale.da: 'Det tag, du vil fjerne blokeringen af.',
      Locale.esEs: 'El tag que quieres desbloquear.',
      Locale.fr: 'Le tag que tu veux débloquer.',
      Locale.ru: 'Тег, который хочешь разблокировать.',
      Locale.hi: 'वह टैग जिसे आप अनब्लॉक करना चाहते हैं।',
      Locale.zhCn: '你想解除屏蔽的标签。',
      Locale.ja: 'ブロックを解除したいタグ。',
      Locale.ko: '차단을 해제할 태그.',
    })
    @Autocomplete(allTags)
    String name,
  ) async {
    try {
      final tag = await findTag(name);
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
      await replyCommandError(context, 'tag_error', 'unblock', e);
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
    Locale.ru: 'Включить или выключить теги на этом сервере.',
    Locale.hi: 'इस सर्वर के लिए टैग चालू या बंद करें।',
    Locale.zhCn: '为此服务器开启或关闭标签。',
    Locale.ja: 'このサーバーのタグをオンまたはオフにします。',
    Locale.ko: '이 서버의 태그를 켜거나 끕니다.',
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
      Locale.hi: 'इस सर्वर पर टैग इस्तेमाल हो सकते हैं या नहीं।',
      Locale.zhCn: '此服务器是否可以使用标签。',
      Locale.ja: 'このサーバーでタグを使えるか。',
      Locale.ko: '이 서버에서 태그를 쓸 수 있는지 여부.',
    })
    bool enabled,
  ) async {
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
      await replyCommandError(context, 'tag_error', 'toggle', e);
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
    Locale.esEs: 'Crear y usar tags, tus propios comandos.',
    Locale.fr: 'Créer et utiliser des tags, tes propres commandes.',
    Locale.ru: 'Создавать и использовать теги, свои команды.',
    Locale.hi: 'टैग बनाएँ और इस्तेमाल करें, आपके अपने कमांड।',
    Locale.zhCn: '创建和使用标签，也就是你自己的命令。',
    Locale.ja: 'タグ（自分だけのコマンド）を作成して使います。',
    Locale.ko: '나만의 명령어인 태그를 만들고 사용합니다.',
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
