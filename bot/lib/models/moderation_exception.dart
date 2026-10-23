class ModerationException implements Exception {
  final String key;
  final Map<String, String> replace;

  ModerationException(this.key, [this.replace = const {}]);

  @override
  String toString() => 'ModerationException($key)';
}
