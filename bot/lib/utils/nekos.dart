import 'dart:convert';

import 'package:http/http.dart' as http;

const _host = 'nekos.best';

const _timeout = Duration(seconds: 5);

const _headers = {'User-Agent': 'Toxbot (https://github.com/newtox/Toxbot)'};

const _batchSize = 10;

const _historySize = 8;

final _recent = <String, List<String>>{};

class NekosUnavailableException implements Exception {
  NekosUnavailableException(this.reason);

  final String reason;

  @override
  String toString() => 'NekosUnavailableException: $reason';
}

Future<http.Response> _get(Uri url) async {
  try {
    final response = await http.get(url, headers: _headers).timeout(_timeout);
    if (response.statusCode != 200) {
      throw NekosUnavailableException('HTTP ${response.statusCode} for $url');
    }
    return response;
  } on NekosUnavailableException {
    rethrow;
  } catch (e) {
    throw NekosUnavailableException(e.toString());
  }
}

List<Uri> _parse(http.Response response) {
  try {
    final body = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
    final urls = [
      for (final result in body['results'] as List)
        Uri.parse((result as Map)['url'] as String)
    ];
    if (urls.isEmpty || urls.any((candidate) => candidate.host != _host)) {
      throw NekosUnavailableException('Unexpected response');
    }
    return urls;
  } on NekosUnavailableException {
    rethrow;
  } catch (e) {
    throw NekosUnavailableException(e.toString());
  }
}

Uri _pickFresh(String category, List<Uri> urls) {
  final recent = _recent.putIfAbsent(category, () => []);
  final picked = urls.firstWhere(
      (candidate) => !recent.contains(candidate.toString()),
      orElse: () => urls.first);
  recent.add(picked.toString());
  if (recent.length > _historySize) {
    recent.removeAt(0);
  }
  return picked;
}

Future<Uri> fetchNekos(String category) async {
  final url = Uri.https(_host, 'api/v2/$category', {'amount': '$_batchSize'});
  return _pickFresh(category, _parse(await _get(url)));
}

Future<List<int>> downloadNekos(Uri url) async => (await _get(url)).bodyBytes;
