import 'dart:convert';
import 'dart:math';

import 'package:http/http.dart' as http;
import 'package:nyxx/nyxx.dart';

const invitePermissions = 1099780320438;

Uri inviteUrl(Snowflake botId) => Uri.parse(
    'https://discord.com/oauth2/authorize?client_id=$botId&permissions=$invitePermissions&integration_type=0&scope=bot+applications.commands');

Uri voteUrl(Snowflake botId) => Uri.parse('https://top.gg/bot/$botId/vote');

String numberWithCommas(num numb) {
  return numb.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},');
}

String clean(String text) {
  return text.replaceAll('`', '`\u{200B}').replaceAll('@', '@\u{200B}');
}

String mock(String text) {
  final random = Random();
  var result = StringBuffer();
  var next = random.nextInt(3) + 1;

  for (var i = 0; i < text.length; i++) {
    if (i == next) {
      result.write(text[i].toUpperCase());
      next += random.nextInt(3) + 1;
    } else {
      result.write(text[i]);
    }
  }
  return result.toString();
}

int compare(Role a, Role b) {
  if (a.position > b.position) {
    return -1;
  } else if (a.position < b.position) {
    return 1;
  } else {
    return 0;
  }
}

Future<String> uploadToHastebin(String content) async {
  final uri = Uri.https('hastebin.newtox.de', '/documents');

  try {
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'text/plain'},
      body: content,
    );

    if (response.statusCode == 200) {
      final jsonResponse = jsonDecode(response.body);
      if (jsonResponse.containsKey('key')) {
        return 'https://hastebin.newtox.de/${jsonResponse['key']}';
      } else {
        throw Exception('Unexpected response from Hastebin: $jsonResponse');
      }
    } else {
      throw Exception(
          'Failed to upload to Hastebin. Status code: ${response.statusCode}');
    }
  } catch (e) {
    throw Exception('Error uploading to Hastebin: $e');
  }
}
