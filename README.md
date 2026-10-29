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

## Running locally

### Bot

1. Copy `bot/.env.example` to `bot/.env` and fill in your bot token, your Discord user ID as `owner_id` and the database credentials. Set `dashboard_url` to the dashboard (locally `http://localhost:8000`), the bot loads the profile cards from there. All other values are optional and have defaults.
2. Start the bot:
   ```bash
   cd bot
   dart pub get
   dart run bin/main.dart
   ```

With Nix, `bot/start.sh` does the same inside the dev shell. `nix develop` opens that shell in `bot/`.

### Dashboard

```bash
nix develop .#dashboard
```

This shell comes with PHP, Composer and Node and opens in `dashboard/`. Setup and usage are described in the [dashboard README](dashboard/README.md).

## Deployment

On every push to `main`, the GitHub Action in `.github/workflows/docker.yml` builds two Docker images from `bot/` and `dashboard/` and publishes them to the GitHub Container Registry:

- `ghcr.io/newtox/toxbot`
- `ghcr.io/newtox/toxbot-dashboard`

The server runs both from `docker-compose.yml`, deployed as a Portainer stack. Secrets are not part of the images; they are set as stack environment variables. See `stack.env.example` for the full list. `CONTACT_EMAIL` (defaults to justin.lys26@gmail.com) and the optional `SUPPORT_INVITE` show up on the dashboard's info page; `/invite` also links the support server.

Both images get the commit and build time as `GIT_COMMIT` and `BUILD_DATE`. The bot writes its status (commit, uptime, ping, servers, memory) to the `bot_status` table once a minute, and the dashboard shows it together with its own version under Info → System.

Shop items (backgrounds, avatar frames and progress bars, including the starter set the dashboard adds on its first start) are stored in `/docker_volumes/toxbot-dashboard/backgrounds` on the host. Create the folder once and give it to the container user:

```bash
mkdir -p /docker_volumes/toxbot-dashboard/backgrounds
chown 33:33 /docker_volumes/toxbot-dashboard/backgrounds
```

### Discord application

- Under **Installation**, keep only **Guild Install**. Toxbot's commands work on servers, so a user install is not needed.
- Use the link provided by Discord with the scopes `bot` and `applications.commands` and the same permissions as the invite links in the dashboard, `/info` and `/invite`: View Channels, Send Messages, Embed Links, Attach Files, Read Message History, Mention Everyone, Manage Messages, Manage Channels, Manage Roles, Manage Server, View Audit Log, Kick Members, Ban Members and Timeout Members (permission integer `1099780320438`).
- Mention Everyone is only used so that ticket team roles and stream ping roles are pinged even when they are not mentionable; Toxbot never pings `@everyone` or `@here`.

### Premium and Top.gg

- Create the SKUs in the Developer Portal under **Monetization**: a user subscription for Premium and a consumable for the XP boost. Put their IDs into `PREMIUM_USER_SKUS` and `XP_BOOST_SKU`.
- As the bot owner you can give Premium without a subscription on the **Premium** page of the administration in the dashboard: Toxbot Premium for a user or Server Premium for a server, either without an end date or until a chosen day, after which it runs out on its own. `PREMIUM_USERS` and `PREMIUM_GUILDS` still work for fixed IDs, for example for testing.
- For vote rewards, add `https://<dashboard>/webhooks/topgg` as webhook on Top.gg and put the generated secret into `TOPGG_WEBHOOK_SECRET`. `TOPGG_VOTE_REWARD` sets the credits per vote (200 by default, doubled on weekends). `/vote` links to the vote page and shows when a user can vote again.
- Server Premium comes with the user subscription: subscribers pick one server on the Premium page of their user settings in the dashboard. Discord only allows one published subscription per app, so a separate server subscription is optional; if you ever sell one, put its SKU ID into `PREMIUM_GUILD_SKUS`. `PREMIUM_GUILDS` gives Server Premium to the listed server IDs without a subscription.

### Automatic setup

The server overview and the Moderation, Verification, Link filter, Anti-raid and Tickets pages have an automatic setup. Pick the team roles and the areas, and Toxbot creates what is missing: a private `#mod-log`, a `Tickets` category with `#tickets` and a private `#ticket-log`, a `Support` ticket type and the ticket buttons, the link filter and anti-raid, and for verification an `Unverified` role with a `#verify` channel that hides every other channel from that role. Channels and roles that are already set or have the same name are reused. It needs **Manage Channels** and **Manage Roles**.

Messages for the whole server (ticket and verify buttons, ticket logs, raid alarms, automatic reasons) use the server language from the Language page. It is taken from the dashboard language of whoever first saves a setting and can be changed there.

### Resetting and deleting data

- Every server has a **Data** page under General. Anyone who can manage the server can reset all settings: modules are turned off, ticket types, warning rules, followed creators, blocked tags and server tags are removed, and the ticket and verify messages are deleted. Levels, cases, tickets and appeals stay.
- Every settings page can also be reset on its own with the button at the bottom of the page.
- Only the server owner can delete all data of the server (settings, levels, cases, tickets with transcripts, appeals, verifications, starboard posts and its Server Premium slot). Toxbot then leaves the server.
- Users can delete their own data on the Data page of their user settings. Their starboard posts are removed, their ticket messages are kept without name, ID and avatar; moderation cases and purchase records stay.

### Slots

`/slots bet` spins three reels for 10 to 10,000 credits, `/slots` alone shows the payout table. Three of a kind pay 8× (🍒) up to 500× (7️⃣), two of a kind return the bet. On average about 93 % of the bets come back, so slots slowly take credits out of the economy. Every member can spin once every five seconds. Credits cannot be bought with real money, so slots are play money only. Server admins can turn slots off on the Levels page of the dashboard.

### Blacklist

The bot owner (`OWNER_ID` / `TOXBOT_OWNER_ID`) manages the blacklist on the dashboard's administration page. Blocked users get an error for every command, earn no XP, cannot open tickets and cannot log in to the dashboard. Toxbot leaves blocked servers right away and whenever it is invited again. The bot reloads the list once a minute.

### Credits and items

The bot owner can add or remove credits and give or take away shop items, in Discord with `/system credits`, `/system give-item` and `/system take-item` or on the dashboard's administration page under **Credits & items**. The balance never drops below 0; items that are taken away are unequipped. Retired and Premium items can be given too. Every change shows up in the user's `/credits` history.

### Moderation

For moderation, Toxbot needs the permissions **Kick Members**, **Ban Members**, **Moderate Members** and **View Audit Log**. New invites ask for them; on servers where Toxbot already is, give them to its role. Its role has to be above the roles of the members it should moderate.

Actions in the dashboard are written to the `mod_actions` table and carried out by the bot within a few seconds, so DMs, modlog and warning levels work the same as with `/mod`.

### Protection

- Verification gives new members a role and a link to `https://<dashboard>/verify/<server>`. Set up the role in Discord so that it only sees one channel, or let the automatic setup create the role and the channel and hide everything else from it; the dashboard can post a message with a button there. After solving the captcha, the role is removed and the autorole is given instead.
- The captcha is [Cloudflare Turnstile](https://dash.cloudflare.com/?to=/:account/turnstile). Create a widget for the dashboard domain and put its keys into `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`. Without them, the page only asks for the Discord login.
- The bot sends the verification link by DM using `public_dashboard_url`, which `docker-compose.yml` sets to `APP_URL`.
- Toxbot needs **Manage Roles** for verification, **Manage Messages** for the link filter, **Kick Members** for anti-raid and the minimum account age, and **Manage Server** to raise the verification level during a raid. Its role has to be above the verification role.
- Scam links are checked against [DSP Scam Links](https://github.com/Discord-AntiScam/scam-links) and [discord-phishing-links](https://github.com/nikolaischunk/discord-phishing-links), reloaded every six hours. The dashboard can check a single domain against them. Roles can be excluded from the link filter.

### Starboard and members

- On the Starboard page, pick a channel, the emojis that count (⭐ by default, up to ten, any Unicode emoji or the server's own) and how many reactions a message needs. Every person counts once per message, whichever of the emojis they use; reactions from the author and from bots don't count. The first emoji is shown on the post. When the count drops below the threshold again or the message is deleted, the post is removed from the starboard. Messages from NSFW channels only go to an NSFW starboard channel.
- Toxbot needs **View Channel**, **Send Messages**, **Embed Links** and **Read Message History** in the starboard channel and in the channels it watches.
- Members without "Manage Server" see the servers with Toxbot they are on. They get the member list and profiles with roles, levels and join date, plus the starboard; cases, timeouts and moderation actions stay hidden from them.
- Moderators don't need "Manage Server": with **Kick Members**, **Ban Members**, **Timeout Members** or **View Audit Log** they see the modlog and the cases in member profiles, and the profile only offers the actions their permissions allow. **Ban Members** opens the unban appeals. The ticket history shows the tickets of every ticket type whose team roles they have.

### Tickets

- In the server settings under Tickets, pick a category for the ticket channels, a log channel and the channel for the buttons, add ticket types and post the buttons.
- Toxbot needs **Manage Channels** to create and delete ticket channels, **Manage Roles** for their permissions, **Read Message History** for the transcript and **Attach Files** to post it in the log channel.
- When a ticket is closed, up to 2000 messages are saved and shown under Tickets in the dashboard; the log channel gets the transcript as a text file.
- Texts in the ticket channel (welcome, close button, closing message) and the log use the server language.
- A ticket channel deleted by hand closes its ticket, but without a transcript, because Discord deletes the messages with the channel. Close tickets with the button or `/ticket close` to keep them.
- Banned members find the appeal form at `https://<dashboard>/appeal/<server>`. With appeals turned on, ban DMs contain this link. Accepting an appeal unbans the member through the bot.

### Stream notifications

- Create an application at [dev.twitch.tv/console](https://dev.twitch.tv/console) (OAuth redirect `http://localhost`, category "Chat Bot" or "Other") and put its client ID and secret into `TWITCH_CLIENT_ID` and `TWITCH_CLIENT_SECRET`.
- Set `STREAM_WEBHOOK_SECRET` to a random string with 10 to 100 characters, for example `openssl rand -hex 32`. It signs the Twitch and YouTube webhooks.
- `YOUTUBE_API_KEY` is optional (Google Cloud → YouTube Data API v3). Without it, YouTube channels have to be added by channel ID or `/channel/UC…` link, and live streams cannot be told apart from videos.
- Twitch and YouTube call `https://<dashboard>/webhooks/twitch` and `https://<dashboard>/webhooks/youtube`. This only works on the public HTTPS deployment, not locally.
- The `scheduler` service in `docker-compose.yml` uses the dashboard image. Every five minutes it checks with Twitch and YouTube whether followed streamers are live and catches up on missed webhooks, every two minutes it posts scheduled YouTube streams when they start, and every hour it renews the YouTube subscriptions and repairs the Twitch subscriptions.
- What happens to a Twitch notification when the stream ends (keep it, add the duration and VOD link, or delete it) is set for the whole server on the Streams page. With Server Premium, each streamer can have its own choice.
- The live role needs the **Presence Intent** under **Bot → Privileged Gateway Intents** in the Developer Portal.

The dashboard listens on `127.0.0.1:8090` and is meant to sit behind a reverse proxy, for example Caddy:

```
toxbot.example.com {
    reverse_proxy 127.0.0.1:8090
}
```

To update: push to `main`, wait for the action to finish, then use **Pull and redeploy** on the stack in Portainer.

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
