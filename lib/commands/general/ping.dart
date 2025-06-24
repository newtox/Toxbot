import 'package:toxbot/database.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';

String latencyTypeToString(String type) => type;

const latencyTypeConverter = SimpleConverter.fixed(
  elements: ['Basic', 'Real', 'Gateway'],
  stringify: latencyTypeToString,
);

final ping = ChatCommand(
    'ping', 'A simple ping command to check the bot\'s latency.',
    localizedDescriptions: {
      Locale.da: 'En simpel ping kommando for at tjekke bot\'ens responstid.',
      Locale.de:
          'Ein einfacher Ping-Befehl, um die Latenz des Bots zu überprüfen.',
      Locale.enUs: 'A simple ping command to check the bot\'s latency.',
      Locale.esEs:
          'Un comando de ping simple para verificar la latencia del bot.',
      Locale.fr: 'Une commande ping simple pour vérifier la latence du bot.',
      Locale.ru: 'Простая команда пинг для проверки задержки бота.',
      Locale.hi: 'बॉट की लेटेंसी की जांच करने के लिए एक सरल पिंग कमांड।',
      Locale.zhCn: '一个简单的 ping 命令来检查机器人的延迟。',
      Locale.ja: 'ボットの遅延を確認するためのシンプルなピンのコマンド。',
      Locale.ko: '봇의 지연 시간을 확인하기 위한 간단한 핑 명령어.'
    }, (ChatContext context,
        [@UseConverter(latencyTypeConverter)
        @Description('The type of latency to view.', {
          Locale.da: 'Den type responstid, der skal vises.',
          Locale.de: 'Der Typ der Latenz, die angezeigt werden soll.',
          Locale.enUs: 'The type of latency to view.',
          Locale.esEs: 'El tipo de latencia que se desea ver.',
          Locale.fr: 'Le type de latence à afficher.',
          Locale.ru: 'Тип задержки для просмотра.',
          Locale.hi: 'देखने के लिए लेटेंसी का प्रकार।',
          Locale.zhCn: '要查看的延迟类型。',
          Locale.ja: '表示する遅延の種類。',
          Locale.ko: '보기 위한 지연 유형.'
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
