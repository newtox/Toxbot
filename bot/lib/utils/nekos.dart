import 'dart:convert';

import 'package:http/http.dart' as http;

const _host = 'nekos.best';

const _timeout = Duration(seconds: 5);

const _headers = {'User-Agent': 'Toxbot (https://github.com/newtox/Toxbot)'};

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

Future<Uri> fetchNekos(String category) async {
  final response = await _get(Uri.https(_host, 'api/v2/$category'));
  try {
    final body = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
    final result = (body['results'] as List).first as Map;
    final url = Uri.parse(result['url'] as String);
    if (url.host != _host) {
      throw NekosUnavailableException('Unexpected host ${url.host}');
    }
    return url;
  } on NekosUnavailableException {
    rethrow;
  } catch (e) {
    throw NekosUnavailableException(e.toString());
  }
}

Future<List<int>> downloadNekos(Uri url) async => (await _get(url)).bodyBytes;
