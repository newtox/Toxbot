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
    'Italiano',
    'Polski',
    'Português (Brasil)',
    'Tiếng Việt',
    'Türkçe',
    'Русский',
    'Українська',
    'हिंदी',
    'ไทย',
    '中文',
    '繁體中文',
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
      Locale.ko: 'Toxbot이 사용할 언어를 설정합니다.',
      Locale.es419: 'Establecer el idioma que Toxbot usa contigo.',
      Locale.ptBr: 'Definir o idioma que o Toxbot usa com você.',
      Locale.tr: 'Toxbot\'un seninle kullandığı dili ayarla.',
      Locale.pl: 'Ustaw język, którego Toxbot używa z tobą.',
      Locale.it: 'Imposta la lingua che Toxbot usa con te.',
      Locale.uk: 'Встановити мову, якою Toxbot спілкується з тобою.',
      Locale.vi: 'Đặt ngôn ngữ Toxbot dùng với bạn.',
      Locale.th: 'ตั้งภาษาที่ Toxbot ใช้กับคุณ',
      Locale.zhTw: '設定 Toxbot 與你對話時使用的語言。'
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
          Locale.ko: '사용할 언어.',
          Locale.es419: 'El idioma que quieres.',
          Locale.ptBr: 'O idioma que você quer.',
          Locale.tr: 'İstediğin dil.',
          Locale.pl: 'Język, który chcesz.',
          Locale.it: 'La lingua che vuoi.',
          Locale.uk: 'Мова, яку ти хочеш.',
          Locale.vi: 'Ngôn ngữ bạn muốn.',
          Locale.th: 'ภาษาที่คุณต้องการ',
          Locale.zhTw: '你想要的語言。'
        })
        String? selectedLanguage]) async {
  selectedLanguage ??= await context.getSelection<String>(
    [
      'Dansk',
      'Deutsch',
      'English',
      'Español',
      'Français',
      'Italiano',
      'Polski',
      'Português (Brasil)',
      'Tiếng Việt',
      'Türkçe',
      'Русский',
      'Українська',
      'हिंदी',
      'ไทย',
      '中文',
      '繁體中文',
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
    'Italiano' => 'it_it',
    'Polski' => 'pl_pl',
    'Português (Brasil)' => 'pt_br',
    'Tiếng Việt' => 'vi_vn',
    'Türkçe' => 'tr_tr',
    'Русский' => 'ru_ru',
    'Українська' => 'uk_ua',
    'हिंदी' => 'hi_hi',
    'ไทย' => 'th_th',
    '中文' => 'zh_cn',
    '繁體中文' => 'zh_tw',
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
        replace: {'&language': selectedLanguage}, level: ResponseLevel.hint);
    return;
  } catch (e) {
    print('Error updating language: $e');
    await replyError(context, 'language_error',
        details: codeBlock(e.toString(), 'sh'));
    return;
  }
});
