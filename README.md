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
- **Server setup** in Discord (`/announcements`, `/autorole`) and in the dashboard: welcome and goodbye messages, autorole, level-up messages in a channel and as a DM, and a server language for messages to the whole server; every setting has its own page in the dashboard
- **Automatic setup** in the dashboard: creates the channels, roles and permissions for tickets, moderation, protection and verification in one go
- **Data control**: server admins can reset all settings, the server owner can delete all data and remove Toxbot, and users can delete their own data
- **Levels**: XP for messages and commands, with a global level and a separate level per server, plus `/leaderboard`
- **Credits and shop**: `/daily`, `/credits` for the balance, streak and recent activity, credits for level-ups and Top.gg votes, backgrounds, avatar frames and progress bars for the profile card to buy with `/shop` and switch with `/background`, `/frame` and `/bar` or on the dashboard profile, some of them animated; `/pay` sends credits to another member; `/slots` is a slot machine for credits
- **Profile cards**: `/profile` shows level, rank and credits as an image with the chosen background, avatar frame and progress bar; with an animated item or an animated Discord avatar the card is a GIF
- **Tags**: global custom commands with `/tag`, reportable and blockable per server, plus server tags that admins create in the dashboard and that `/tag show` prefers on their server
- **Starboard**: messages with enough reactions of a chosen emoji are reposted to a starboard channel, and every member can browse them in the dashboard
- **Dashboard for members**: members without "Manage Server" can open the member list with profiles and the starboard of their servers
- **Stream notifications** for Twitch and YouTube: a message with role ping when a streamer goes live or uploads a video
- **Live role**: members who stream on Discord get a role while they are live
- **Moderation**: `/clear`, `/mod warn`, `timeout`, `untimeout`, `kick`, `ban` (also temporary) and `unban`, numbered cases in a modlog channel, DMs to members, automatic actions after a number of warnings, and bans, kicks and timeouts done in Discord or by other bots are logged too
- **Protection**: verification of new members with a captcha in the dashboard, a link filter against invites to other servers, scam links and blocked domains (or only allowed ones), anti-raid that raises the verification level and kicks new joins, and a minimum account age
- **Tickets and unban appeals**: ticket types with their own button, text and team roles, private ticket channels, `/ticket close`, `add` and `remove`, transcripts saved on close and shown in the dashboard, and a dashboard page where banned members can ask to be unbanned
- **Premium** through Discord subscriptions: double XP and daily reward, premium backgrounds, more tags and tags as embeds, plus a 24 hour XP boost to buy
- **Server Premium** for one server of the subscriber's choice, included in Premium: more streamers, a custom message and ping role per streamer, game and title filters, a separate choice per streamer for what happens to the notification when the stream ends, and 100 server tags instead of 20

## Usage

Toxbot uses slash commands. Type `/` in Discord to see all of them, for example `/ping` to check whether the bot is online, `/profile` for your profile card or `/tag show` to use a tag.

## Code structure

The bot lives in `bot/lib`:

- `commands/`: one file per command or command group. These files only hold option descriptions, converters and the command definitions; every function they use lives in `services/` or `utils/`
- `models/`: data classes such as `Tag`, `ModCase` or the economy results
- `services/`: the logic behind the commands and the event handlers, for example levels, moderation, protection, tickets, premium, the live role, the starboard and the status heartbeat; `events.dart` registers the commands and handlers
- `utils/`: shared helpers: `config.dart` for every setting from the environment, `database.dart` for the connection and translated texts, `reply.dart` for command replies, and the translations in `languages.yaml`

The dashboard is a Laravel app with Livewire in `dashboard/`:

- `routes/web.php`: every page and webhook URL; `routes/console.php`: the scheduled stream commands (`streams:sync` every five minutes, `streams:check-upcoming` every two minutes, `streams:maintain` hourly)
- `app/Http/Controllers/`: plain pages and webhooks, for example `ServerController` (overview, members, modlog, tickets, data), `InfoController` (info, commands, system), `VerifyController`, `AppealController` and the `TwitchWebhookController`, `YouTubeWebhookController` and `TopggWebhookController`
- `app/Livewire/`: the interactive parts. `GuildSettings` holds every settings section of a server; smaller components such as `StreamList`, `TicketTypes`, `WarnRules`, `AutoSetup`, `AppealList`, `ServerData` and `DeleteAccount` sit inside the pages
- `app/Services/`: the logic behind them. `Discord` wraps every call to the Discord API, `GuildAccess` decides who may manage a server, `AutoSetup` and `SetupMessages` create channels and post the ticket and verify messages, `DataDeletion` resets and deletes data, and `Streams/` handles Twitch and YouTube (`Twitch`, `YouTube`, `SourceManager` for following and webhooks, `Announcer` for the messages and the live status)
- `app/Models/`: one model per table, most of them shared with the bot; `database/migrations/` creates the tables
- `app/Support/`: small helpers such as `BotLocale`, `DomainList` or `Timezone`, plus `Navigation`, which builds the sidebar for servers, user settings and info, and `CommandCatalog` for the command list
- `resources/views/`: Blade templates, with the Livewire views in `livewire/` and the server pages in `servers/`
- `lang/<locale>/`: one file per area (`guild`, `tickets`, `setup`, `data`, …) in all ten languages
- `tests/Feature/`: one test file per area

The bot and the dashboard share the MySQL database. The dashboard writes settings and queues moderation actions in `mod_actions`; the bot reads them and does everything that needs a live gateway connection.

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

Found a bug or have a feature request? Open an issue on GitHub or write to [justin.lys26@gmail.com](mailto:justin.lys26@gmail.com).
