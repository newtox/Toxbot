import 'dart:math';

import 'package:toxbot/database.dart';
import 'package:toxbot/utils/functions.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';

final eightball = ChatCommand('8ball', 'Ask the magic 8ball a question.',
    localizedDescriptions: {
      Locale.da: 'Spørg den magiske 8ball et spørgsmål.',
      Locale.de: 'Stelle der magischen 8ball eine Frage.',
      Locale.enUs: 'Ask the magic 8ball a question.',
      Locale.esEs: 'Pregunta a la bola 8 mágica.',
      Locale.fr: 'Posez une question à la boule magique 8.',
      Locale.ru: 'Задайте вопрос магическому шару 8.',
      Locale.hi: 'जादुई 8ball से एक सवाल पूछें।',
      Locale.zhCn: '向神奇的8号球提问。',
      Locale.ja: '魔法の8ボールに質問する。',
      Locale.ko: '마법의 8볼에게 질문하세요.'
    }, (ChatContext context,
        @Description('Your question for the 8ball.', {
          Locale.da: 'Dit spørgsmål til 8ball.',
          Locale.de: 'Deine Frage an den 8ball.',
          Locale.enUs: 'Your question for the 8ball.',
          Locale.esEs: 'Tu pregunta para la bola 8.',
          Locale.fr: 'Votre question pour la boule 8.',
          Locale.ru: 'Ваш вопрос для шара 8.',
          Locale.hi: '8ball के लिए आपका प्रश्न।',
          Locale.zhCn: '你要问8号球的问题。',
          Locale.ja: '8ボールへの質問。',
          Locale.ko: '8볼에게 할 질문.'
        })
        String question) async {
  final random = Random();
  final responseNumber = random.nextInt(16) + 1;

  await context.respond(MessageBuilder(embeds: [
    EmbedBuilder(
        color: await getUserColorFromDatabase(context.user.id),
        fields: [
          EmbedFieldBuilder(
              name: await getString(context.user, 'eightball_question'),
              value: question,
              isInline: false),
          EmbedFieldBuilder(
              name: await getString(context.user, 'eightball_answer'),
              value: await getString(context.user, 'eightball_$responseNumber'),
              isInline: false)
        ])
  ]));
});
