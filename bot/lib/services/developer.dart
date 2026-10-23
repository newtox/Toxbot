import 'dart:async';
import 'dart:convert';
import 'dart:isolate';

import 'package:mysql1/mysql1.dart';
import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/models/eval_result.dart';
import 'package:toxbot/utils/database.dart';
import 'package:toxbot/utils/reply.dart';
import 'package:toxbot/utils/utils.dart';

export 'package:toxbot/models/eval_result.dart';

const _evalImports = [
  'dart:async',
  'dart:convert',
  'dart:io',
  'dart:math',
  'package:mysql1/mysql1.dart',
  'package:toxbot/utils/config.dart',
  'package:toxbot/utils/database.dart',
];

const _evalTimeout = Duration(seconds: 30);

const _outputLimit = 1900;

const _maxRows = 50;

const _maxCellWidth = 40;

const _readOnlyStatements = {
  'select',
  'show',
  'describe',
  'desc',
  'explain',
  'with',
};

String _evalSource(String code) {
  final lines = code.trim().split('\n');
  bool isImport(String line) => line.trimLeft().startsWith('import ');
  final imports = lines.where(isImport).join('\n');
  var body = lines.where((line) => !isImport(line)).join('\n').trim();
  if (!body.contains(';')) {
    body = 'return $body;';
  }

  return '''
import 'dart:isolate';
${_evalImports.map((uri) => "import '$uri';").join('\n')}
$imports

Future<Object?> _run() async {
$body
}

Future<void> main(List<String> args, SendPort port) async {
  final output = StringBuffer();
  try {
    final result = await runZoned(
      _run,
      zoneSpecification: ZoneSpecification(
        print: (self, parent, zone, line) => output.writeln(line),
      ),
    );
    port.send([true, output.toString(), result?.toString()]);
  } catch (e, s) {
    port.send([false, output.toString(), '\$e\\n\$s']);
  }
}
''';
}

String _cell(Object? value) {
  final text = switch (value) {
    null => 'NULL',
    DateTime date => date.toIso8601String(),
    _ => textValue(value),
  }
      .replaceAll('\n', ' ');
  return text.length > _maxCellWidth
      ? '${text.substring(0, _maxCellWidth - 1)}…'
      : text;
}

String _table(Results results) {
  final columns = results.fields.map((field) => field.name ?? '?').toList();
  final rows = results
      .take(_maxRows)
      .map((row) => [for (final value in row.values ?? const []) _cell(value)])
      .toList();

  final widths = [
    for (var i = 0; i < columns.length; i++)
      [columns[i], ...rows.map((row) => row[i])]
          .map((text) => text.length)
          .fold(0, (a, b) => a > b ? a : b),
  ];
  String line(List<String> cells) => [
        for (var i = 0; i < cells.length; i++) cells[i].padRight(widths[i])
      ].join(' | ');

  final count = results.length;
  final footer = count > _maxRows
      ? '$count rows, showing the first $_maxRows'
      : '$count ${count == 1 ? 'row' : 'rows'}';

  return [
    line(columns),
    widths.map((width) => '-' * width).join('-+-'),
    ...rows.map(line),
    '',
    footer,
  ].join('\n');
}

Future<EvalResult> evaluate(String code) async {
  final port = ReceivePort();
  final errors = ReceivePort();
  final uri = Uri.dataFromString(
    _evalSource(code),
    mimeType: 'application/dart',
    encoding: utf8,
  );
  Isolate? isolate;

  try {
    isolate = await Isolate.spawnUri(
      uri,
      const [],
      port.sendPort,
      onError: errors.sendPort,
      packageConfig: await Isolate.packageConfig,
    );
    final message = await Future.any([
      port.first,
      errors.first.then((error) => [false, '', (error as List).join('\n')]),
    ]).timeout(_evalTimeout);
    final [success, output, value] = message as List;
    return EvalResult(success as bool, output as String, value as String?);
  } on IsolateSpawnException catch (e) {
    return EvalResult.error(e.message
        .replaceAll('Unable to spawn isolate: ', '')
        .replaceAll(uri.toString(), 'eval'));
  } on TimeoutException {
    return EvalResult.error(
        'Timed out after ${_evalTimeout.inSeconds} seconds.');
  } finally {
    isolate?.kill(priority: Isolate.immediate);
    port.close();
    errors.close();
  }
}

Future<String> runReadOnlyQuery(String query) async {
  final statement = query.trim().replaceFirst(RegExp(r';\s*$'), '');
  final keyword = statement.split(RegExp(r'\s+')).first.toLowerCase();
  if (!_readOnlyStatements.contains(keyword) || statement.contains(';')) {
    throw ArgumentError(
        'Only single SELECT, SHOW, DESCRIBE, EXPLAIN or WITH statements are allowed.');
  }

  return withConnection((connection) async {
    await connection.query('START TRANSACTION READ ONLY;');
    try {
      return _table(await connection.query(statement));
    } finally {
      await connection.query('ROLLBACK;');
    }
  });
}

Future<void> respondWithOutput(
  InteractiveContext context,
  String content, {
  String language = '',
  bool hastebin = false,
}) async {
  if (hastebin) {
    await context.respond(
      MessageBuilder(content: await uploadToHastebin(content)),
      level: ResponseLevel.hint,
    );
    return;
  }

  final text = content.length > _outputLimit
      ? '${content.substring(0, _outputLimit)}…'
      : content;
  await context.respond(
    MessageBuilder(
        content: codeBlock(text, language), allowedMentions: noMentions),
    level: ResponseLevel.hint,
  );
}
