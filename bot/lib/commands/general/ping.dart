import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/functions.dart';

const latencyTypeConverter = SimpleConverter.fixed(
  elements: ['Basic', 'Real', 'Gateway'],
  stringify: stringifyOption,
);

final ping =
    ChatCommand('ping', 'Check Toxbot\'s latency.', localizedDescriptions: {
  Locale.da: 'Tjek Toxbots forsinkelse.',
  Locale.de: 'Die Latenz von Toxbot prüfen.',
  Locale.enUs: 'Check Toxbot\'s latency.',
  Locale.esEs: 'Comprobar la latencia de Toxbot.',
  Locale.fr: 'Vérifier la latence de Toxbot.',
  Locale.ru: 'Проверить задержку Toxbot.',
  Locale.hi: 'Toxbot की लेटेंसी जाँचें।',
  Locale.zhCn: '检查 Toxbot 的延迟。',
  Locale.ja: 'Toxbot の遅延を確認します。',
  Locale.ko: 'Toxbot의 지연 시간을 확인합니다.'
}, (ChatContext context,
        [@UseConverter(latencyTypeConverter)
        @Description('Which latency you want to see.', {
          Locale.da: 'Hvilken forsinkelse du vil se.',
          Locale.de: 'Welche Latenz du sehen möchtest.',
          Locale.enUs: 'Which latency you want to see.',
          Locale.esEs: 'Qué latencia quieres ver.',
          Locale.fr: 'Quelle latence tu veux voir.',
          Locale.ru: 'Какую задержку хочешь увидеть.',
          Locale.hi: 'आप कौन सी लेटेंसी देखना चाहते हैं।',
          Locale.zhCn: '你想查看的延迟类型。',
          Locale.ja: '確認したい遅延の種類。',
          Locale.ko: '확인할 지연 시간 종류.'
        })
        String? selection]) async {
  selection ??= await context.getSelection<String>(
    ['Basic', 'Real', 'Gateway'],
    MessageBuilder(content: await getString(context.user, 'ping_latency')),
  );

  final latency = switch (selection) {
    'Basic' => context.client.httpHandler.latency,
    'Real' => context.client.httpHandler.realLatency,
    'Gateway' => context.client.gateway.latency,
    _ => throw StateError('Unexpected selection $selection.'),
  };

  final formattedLatency =
      (latency.inMicroseconds / Duration.microsecondsPerMillisecond)
          .toStringAsFixed(0);

  await context.respond(MessageBuilder(content: '${formattedLatency}ms'));
});
