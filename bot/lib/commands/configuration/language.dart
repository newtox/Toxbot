import 'package:dotenv/dotenv.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/database.dart';

String languageCodeToString(String code) => code;

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
  stringify: languageCodeToString,
);

var env = DotEnv(includePlatformEnvironment: true)..load();

final language = ChatCommand('language', 'Set your preferred language.',
    localizedDescriptions: {
      Locale.da: 'Sæt dit foretrukne sprog.',
      Locale.de: 'Setze deine bevorzugte Sprache.',
      Locale.enUs: 'Set your preferred language.',
      Locale.esEs: 'Establezca su idioma preferido.',
      Locale.fr: 'Définissez votre langue préférée.',
      Locale.ru: 'Установите предпочтительный язык.',
      Locale.hi: 'अपनी पसंदीदा भाषा सेट करें।',
      Locale.zhCn: '设置您偏好的语言。',
      Locale.ja: '好みの言語を設定してください。',
      Locale.ko: '선호하는 언어를 설정하세요.'
    }, (ChatContext context,
        [@UseConverter(languageCodeConverter)
        @Description('The language you want to use.', {
          Locale.da: 'Det sprog, du ønsker at bruge.',
          Locale.de: 'Die Sprache, die du verwenden möchtest.',
          Locale.enUs: 'The language you want to use.',
          Locale.esEs: 'El idioma que desea usar.',
          Locale.fr: 'La langue que vous souhaitez utiliser.',
          Locale.ru: 'Язык, который вы хотите использовать.',
          Locale.hi: 'आप जिस भाषा का उपयोग करना चाहते हैं।',
          Locale.zhCn: '您想要使用的语言。',
          Locale.ja: '使用したい言語。',
          Locale.ko: '사용하고자 하는 언어입니다.'
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

    await context.respond(MessageBuilder(embeds: [
      EmbedBuilder(
          color: DiscordColor.parseHexString('#6dbe33'),
          description: (await getString(context.user, 'language_changed'))
              .replaceAll('&language', selectedLanguage))
    ]));
    return;
  } catch (e) {
    print('Error updating language: $e');
    await context.respond(
        MessageBuilder(embeds: [
          EmbedBuilder(
              color: DiscordColor.parseHexString('#c41111'),
              title: await getString(context.user, 'global_error'),
              description: await getString(context.user, 'language_error') +
                  codeBlock(e.toString(), 'sh'))
        ]),
        level: ResponseLevel.hint);
    return;
  }
});
