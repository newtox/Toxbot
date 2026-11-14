import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';

const clientLanguageCheckName = 'client-language';

const _defaultLanguage = 'en_us';

final Set<Snowflake> _handled = {};

final clientLanguageCheck = Check(
  (CommandContext context) async {
    await _applyClientLanguage(context);
    return true;
  },
  name: clientLanguageCheckName,
);

Future<void> _applyClientLanguage(CommandContext context) async {
  if (context is! InteractionContextData) return;

  final userId = context.user.id;
  if (!_handled.add(userId)) return;

  final language =
      botLanguageFor((context as InteractionContextData).interaction.locale);
  if (language == null || language == _defaultLanguage) return;

  try {
    await withConnection((connection) => connection.query(
          'INSERT INTO `users` (`id`, `language`, `color`) VALUES (?, ?, ?) '
          'ON DUPLICATE KEY UPDATE `language` = IF(`language` = ? AND `commands_used` = 0, ?, `language`);',
          [userId.toString(), language, '#7289da', _defaultLanguage, language],
        ));
  } catch (e) {
    _handled.remove(userId);
    print('Setting the client language failed: $e');
  }
}

String? botLanguageFor(Locale? locale) => switch (locale) {
      Locale.da => 'da_dk',
      Locale.de => 'de_de',
      Locale.enUs || Locale.enGb => 'en_us',
      Locale.esEs || Locale.es419 => 'es_es',
      Locale.fr => 'fr_fr',
      Locale.it => 'it_it',
      Locale.pl => 'pl_pl',
      Locale.ptBr => 'pt_br',
      Locale.ru => 'ru_ru',
      Locale.tr => 'tr_tr',
      Locale.uk => 'uk_ua',
      Locale.vi => 'vi_vn',
      Locale.hi => 'hi_hi',
      Locale.th => 'th_th',
      Locale.zhCn => 'zh_cn',
      Locale.zhTw => 'zh_tw',
      Locale.ja => 'ja_jp',
      Locale.ko => 'ko_ko',
      _ => null,
    };
