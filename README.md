[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: http://creativecommons.org/licenses/by-nc-sa/4.0/
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

# Toxbot

A Discord bot written in Dart with [`nyxx`](https://pub.dev/packages/nyxx) and `nyxx_commands`, plus a web dashboard to configure it.

| Part | Folder | Stack |
| --- | --- | --- |
| Bot | `bot/` | Dart, nyxx, MySQL |
| Dashboard | [`dashboard/`](dashboard/README.md) | Laravel, Livewire, Tailwind |

Both share the same MySQL database. Whatever you change in the dashboard (welcome and goodbye messages, autorole, level-up messages, tags, language, embed color) is picked up by the bot right away.

## Features

- **Roleplay, image and fun commands** like `/hug`, `/pat` or `/8ball`
- **Generators**: `/generate password`, `/generate hex` (like `openssl rand -hex`) and `/generate color` with a preview
- **Server setup**: welcome and goodbye messages, autorole, level-up messages
- **Levels**: XP for messages and commands, with a global level and a separate level per server, plus `/leaderboard`
- **Credits and shop**: `/daily`, credits for level-ups and Top.gg votes, profile backgrounds to buy with `/shop`
- **Profile cards**: `/profile` shows level, rank, credits and the chosen background as an image
- **Tags**: global custom commands with `/tag`, reportable and blockable per server
- **Stream notifications** for Twitch and YouTube: a message with role ping when a streamer goes live or uploads a video
- **Live role**: members who stream on Discord get a role while they are live
- **Moderation**: `/mod warn`, `timeout`, `untimeout`, `kick`, `ban` (also temporary) and `unban`, numbered cases in a modlog channel, DMs to members, automatic actions after a number of warnings, and bans, kicks and timeouts done in Discord or by other bots are logged too
- **Protection**: verification of new members with a captcha in the dashboard, a link filter against invites to other servers, scam links and blocked domains (or only allowed ones), anti-raid that raises the verification level and kicks new joins, and a minimum account age
- **Premium** through Discord subscriptions: double XP and daily reward, premium backgrounds, more tags and tags as embeds, plus a 24 hour XP boost to buy
- **Server Premium** for one server of the subscriber's choice, included in Premium: more streamers, a custom message and ping role per streamer, game and title filters, and editing or deleting the notification when the stream ends

## Usage

Toxbot uses slash commands. Type `/` in Discord to see all of them, for example `/ping` to check whether the bot is online, `/profile` for your profile card or `/tag show` to use a tag.

## Code structure

The bot lives in `bot/lib`:

- `commands/`: one file per command or command group. These files only hold option descriptions, converters and the command definitions; every function they use lives in `services/` or `utils/`
- `models/`: data classes such as `Tag`, `ModCase` or the economy results
- `services/`: the logic behind the commands and the event handlers, for example levels, moderation, protection, premium and the live role; `events.dart` registers the commands and handlers
- `utils/`: shared helpers: `config.dart` for every setting from the environment, `database.dart` for the connection and translated texts, `reply.dart` for command replies, and the translations in `languages.yaml`

Every Dart file is ordered the same way: imports, then constants and top-level variables, then private functions and getters, then public functions and getters, then commands, with the exported command or `setup…Handler` function last. All of `bin/` and `lib/` is formatted with `dart format`. Subcommands of a group are private (`_warn`) and listed before the group (`mod`) that uses them.

## Contributing

- Fork the repository.
- Create a branch: `git checkout -b feature/your-feature-name`
- Commit your changes: `git commit -m 'Add your feature'`
- Push the branch: `git push origin feature/your-feature-name`
- Open a pull request.

## License

This project is licensed under the [Creative Commons Attribution-NonCommercial-ShareAlike 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/) license. By contributing, you agree that your contributions are licensed under the same terms.

## Contact

Found a bug or have a feature request? Open an issue on GitHub or write to [contact@placeholder.de](mailto:contact@placeholder.de).
