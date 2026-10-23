class EvalResult {
  final bool success;
  final String output;
  final String? value;

  EvalResult(this.success, this.output, this.value);

  factory EvalResult.error(String message) => EvalResult(false, '', message);

  String format() {
    final parts = [
      if (output.trim().isNotEmpty) output.trimRight(),
      success ? '=> ${value ?? 'null'}' : '!! ${value ?? 'Unknown error'}',
    ];
    return parts.join('\n\n');
  }
}
