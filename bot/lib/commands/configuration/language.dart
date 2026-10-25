import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:toxbot/utils/reply.dart';

const languageCodeConverter = SimpleConverter.fixed(
  elements: [
    'Dansk',
    'Deutsch',
    'English',
    'Español',
    'Français',
    'Русский',
    'हिंदी',
    '中文',
    '日本語',
    '한국어'
  ],
  stringify: stringifyOption,
);

final language = ChatCommand(
    'language', 'Set the language Toxbot uses with you.',
    localizedDescriptions: {
      Locale.da: 'Indstil det sprog, Toxbot bruger med dig.',
      Locale.de: 'Die Sprache festlegen, in der Toxbot mit dir spricht.',
      Locale.enUs: 'Set the language Toxbot uses with you.',
      Locale.esEs: 'Establecer el idioma que Toxbot usa contigo.',
      Locale.fr: 'Définir la langue que Toxbot utilise avec toi.',
      Locale.ru: 'Задать язык, на котором Toxbot общается с тобой.',
      Locale.hi: 'वह भाषा सेट करें जिसमें Toxbot आपसे बात करे।',
      Locale.zhCn: '设置 Toxbot 与你交流时使用的语言。',
      Locale.ja: 'Toxbot があなたに使う言語を設定します。',
      Locale.ko: 'Toxbot이 사용할 언어를 설정합니다.'
    }, (ChatContext context,
        [@UseConverter(languageCodeConverter)
        @Description('The language you want.', {
          Locale.da: 'Det sprog, du vil have.',
          Locale.de: 'Die Sprache, die du möchtest.',
          Locale.enUs: 'The language you want.',
          Locale.esEs: 'El idioma que quieres.',
          Locale.fr: 'La langue que tu veux.',
          Locale.ru: 'Язык, который тебе нужен.',
          Locale.hi: 'वह भाषा जो आप चाहते हैं।',
          Locale.zhCn: '你想使用的语言。',
          Locale.ja: '使いたい言語。',
          Locale.ko: '사용할 언어.'
        })
        String? selectedLanguage]) async {
  selectedLanguage ??= await context.getSelection<String>(
    [
      'Dansk',
      'Deutsch',
      'English',
      'Español',
      'Français',
      'Русский',
      'हिंदी',
      '中文',
      '日本語',
      '한국어'
    ],
    MessageBuilder(content: await getString(context.user, 'language_select')),
  );

  final connection = await getMySqlConnection();

  String languageCode = switch (selectedLanguage) {
    'Dansk' => 'da_dk',
    'Deutsch' => 'de_de',
    'English' => 'en_us',
    'Español' => 'es_es',
    'Français' => 'fr_fr',
    'Русский' => 'ru_ru',
    'हिंदी' => 'hi_hi',
    '中文' => 'zh_cn',
    '日本語' => 'ja_jp',
    '한국어' => 'ko_ko',
    _ => throw ArgumentError('Unexpected language selection $selectedLanguage.')
  };

  try {
    var results = await connection.query(
        'SELECT * FROM `users` WHERE `id` = ? LIMIT 1;',
        [context.user.id.toString()]);

    if (results.isNotEmpty) {
      var user = results.first;
      if (user['language'] != null) {
        await connection.query(
            'UPDATE `users` SET `language` = ? WHERE `id` = ? LIMIT 1;',
            [languageCode, context.user.id.toString()]);
      }
    }

    await replySuccess(context, 'language_changed',
        replace: {'&language': selectedLanguage});
    return;
  } catch (e) {
    print('Error updating language: $e');
    await replyError(context, 'language_error',
        details: codeBlock(e.toString(), 'sh'));
    return;
  }
});
