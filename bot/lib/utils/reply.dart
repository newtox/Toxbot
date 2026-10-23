import 'package:nyxx/nyxx.dart';
import 'package:nyxx_commands/nyxx_commands.dart';
import 'package:nyxx_extensions/nyxx_extensions.dart';
import 'package:toxbot/utils/database.dart';

const successColor = '#6dbe33';

const errorColor = '#c41111';

final noMentions = AllowedMentions(parse: []);

User _user(InteractiveContext context) => (context as ContextData).user;

Future<String> localized(User user, String key,
    [Map<String, String> replace = const {}]) async {
  var text = await getString(user, key);
  replace.forEach((from, to) => text = text.replaceAll(from, to));
  return text;
}

Future<void> replySuccess(
  InteractiveContext context,
  String key, {
  Map<String, String> replace = const {},
  ResponseLevel level = ResponseLevel.public,
}) async {
  await context.respond(
    MessageBuilder(allowedMentions: noMentions, embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(successColor),
        description: await localized(_user(context), key, replace),
      )
    ]),
    level: level,
  );
}

Future<void> replyError(
  InteractiveContext context,
  String key, {
  Map<String, String> replace = const {},
  String? details,
}) async {
  final text = await localized(_user(context), key, replace);
  await context.respond(
    MessageBuilder(allowedMentions: noMentions, embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(errorColor),
        title: await getString(_user(context), 'global_error'),
        description: details == null ? text : '$text$details',
      )
    ]),
    level: ResponseLevel.hint,
  );
}

Future<void> replyException(InteractiveContext context, Object error) =>
    replyErrorText(context, codeBlock(error.toString(), 'sh'));

Future<void> replyErrorText(
    InteractiveContext context, String description) async {
  await context.respond(
    MessageBuilder(allowedMentions: noMentions, embeds: [
      EmbedBuilder(
        color: DiscordColor.parseHexString(errorColor),
        title: await getString(_user(context), 'global_error'),
        description: description,
      )
    ]),
    level: ResponseLevel.hint,
  );
}

Future<void> replyCommandError(
  InteractiveContext context,
  String key,
  String command,
  Object error,
) async {
  print('Error in $command command: $error');
  await replyError(context, key,
      replace: {'&command': command},
      details: codeBlock(error.toString(), 'sh'));
}
