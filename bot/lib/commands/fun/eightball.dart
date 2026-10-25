import 'dart:math';

import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';

final eightball = ChatCommand('8ball', 'Ask the magic 8ball a question.',
    localizedDescriptions: {
      Locale.da: 'Stil den magiske 8ball et spørgsmål.',
      Locale.de: 'Der magischen 8ball eine Frage stellen.',
      Locale.enUs: 'Ask the magic 8ball a question.',
      Locale.esEs: 'Hacerle una pregunta a la bola 8 mágica.',
      Locale.fr: 'Poser une question à la boule magique 8.',
      Locale.ru: 'Задать вопрос магическому шару 8.',
      Locale.hi: 'जादुई 8ball से सवाल पूछें।',
      Locale.zhCn: '向神奇 8 号球提问。',
      Locale.ja: '魔法の 8ball に質問します。',
      Locale.ko: '마법의 8볼에게 질문합니다.'
    }, (ChatContext context,
        @Description('Your question for the 8ball.', {
          Locale.da: 'Dit spørgsmål til 8ball.',
          Locale.de: 'Deine Frage an die 8ball.',
          Locale.enUs: 'Your question for the 8ball.',
          Locale.esEs: 'Tu pregunta para la bola 8.',
          Locale.fr: 'Ta question pour la boule 8.',
          Locale.ru: 'Твой вопрос шару 8.',
          Locale.hi: '8ball के लिए आपका सवाल।',
          Locale.zhCn: '你要问 8 号球的问题。',
          Locale.ja: '8ball への質問。',
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
