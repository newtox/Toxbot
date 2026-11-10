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
- **Automatic setup** in the dashboard or with `/setup` in Discord: creates the channels, roles and permissions for tickets, moderation, protection and verification in one go
- **Events** in Discord and in the dashboard: `/event setup` creates an event with its own role and a category with an info channel where only the organizer and an optional team role can write, a chat and a voice channel; `/event invite` sends members a DM where they join or decline with a button and get the role; the dashboard shows who joined, and `/event end` deletes everything again
- **Data control**: server admins can reset all settings, the server owner can delete all data and remove Toxbot, and users can delete their own data
- **Levels**: a global level that grows through commands and, once a server turns levels on, a separate server level for messages and commands, plus `/leaderboard` for the server
- **Credits and shop**: `/daily`, `/credits` for the balance, streak and recent activity, credits for level-ups and Top.gg votes, backgrounds, avatar frames and progress bars for the profile card to buy with `/shop` and switch with `/background`, `/frame` and `/bar` or on the dashboard profile, some of them animated; `/pay` sends credits to another member; `/slots` is a slot machine for credits
- **Profile cards**: `/profile` shows level, rank and credits as an image with the chosen background, avatar frame and progress bar; with an animated item or an animated Discord avatar the card is a GIF
- **Tags**: global custom commands with `/tag`, also as embeds, reportable and blockable per server, plus server tags that admins create in the dashboard and that `/tag show` prefers on their server. Everyone gets 10 tags; `/tag slots` or the Tags page in the dashboard sells 5 more for 1,000 credits, up to 250
- **Starboard**: messages with enough reactions of a chosen emoji are reposted to a starboard channel, and every member can browse them in the dashboard
- **Dashboard for members**: members without "Manage Server" can open the member list with profiles and the starboard of their servers
- **Stream notifications** for Twitch and YouTube: a message with role ping when a streamer goes live or uploads a video
- **Live role**: members who stream on Discord get a role while they are live
- **Moderation**: `/mod warn`, `timeout`, `untimeout`, `kick`, `ban` (also temporary), `unban` and `clear`, numbered cases in a modlog channel, DMs to members, automatic actions after a number of warnings, and bans, kicks and timeouts done in Discord or by other bots are logged too
- **Protection**: verification of new members with a captcha in the dashboard, a link filter against invites to other servers, scam links and blocked domains (or only allowed ones), Discord AutoMod rules for blocked domains, blocked words, mention spam, spam content, Discord's word lists and profile names, anti-raid that raises the verification level and kicks new joins, and a minimum account age
- **Tickets and unban appeals**: ticket types with their own button, text and team roles, private ticket channels, `/ticket close`, `add` and `remove`, transcripts saved on close and shown in the dashboard, and a dashboard page where banned members can ask to be unbanned
- **Server Premium**, a Discord subscription for one server: more streamers, a custom channel, message and ping role per streamer, game and title filters, a separate choice per streamer for what happens to the notification when the stream ends, 100 server tags instead of 20, and an own profile picture, banner and bio for Toxbot on that server
- **Exclusive items**: backgrounds, frames and bars that are only sold in the Discord store, purely cosmetic

## How it works

On every push to `main`, a GitHub Action builds two Docker images, one for the bot and one for the dashboard, and publishes them to the GitHub Container Registry. The server runs the bot, the dashboard and a scheduler from these images. Both share one MySQL database; secrets are never part of the images.

Both images carry their commit and build time. The bot writes its status (commit, uptime, ping, servers, memory) to the database once a minute, and the dashboard shows it together with its own version under Info → System. Shop items (backgrounds, avatar frames and progress bars) are stored as files next to the dashboard.

### Permissions

Toxbot is added to servers with the scopes `bot` and `applications.commands` and these permissions: View Channels, Send Messages, Embed Links, Attach Files, Read Message History, Mention Everyone, Manage Messages, Manage Channels, Manage Roles, Manage Server, View Audit Log, Kick Members, Ban Members and Timeout Members. Mention Everyone is only used so that ticket team roles and stream ping roles are pinged even when they are not mentionable; Toxbot never pings `@everyone` or `@here`.

### Premium and votes

- Server Premium is a Discord guild subscription. Whoever buys it picks the server in Discord, and Server Premium applies to that server. The bot owner can also give Server Premium on the **Premium** page of the administration, without an end date or until a chosen day.
- Exclusive shop items are sold in the Discord store and can't be bought with credits. The dashboard and `/shop` link to the store, and Toxbot gives the item to the buyer as soon as Discord reports the purchase.
- Voting on Top.gg gives credits (200 by default, doubled on weekends). `/vote` links to the vote page and shows when a user can vote again.
- The Premium page of the user settings lists the servers someone manages and whether Server Premium is active there.

### Automatic setup

The server overview, `/setup` in Discord and the Moderation, Verification, Link filter, Anti-raid and Tickets pages have an automatic setup. Pick the team roles and the areas, and Toxbot creates what is missing: a private `#mod-log`, a `Tickets` category with `#tickets` and a private `#ticket-log`, a `Support` ticket type and the ticket buttons, the link filter and anti-raid, and for verification an `Unverified` role with a `#verify` channel that hides every other channel from that role. Channels and roles that are already set or have the same name are reused. It needs **Manage Channels** and **Manage Roles**.

Messages for the whole server (ticket and verify buttons, ticket logs, raid alarms, automatic reasons) use the server language from the Language page. It is taken from the dashboard language of whoever first saves a setting and can be changed there.

### Resetting and deleting data

- Every server has a **Data** page under General. Anyone who can manage the server can reset all settings: modules are turned off, ticket types, warning rules, followed creators, blocked tags and server tags are removed, and the ticket and verify messages are deleted. Levels, cases, tickets and appeals stay.
- Every settings page can also be reset on its own with the button at the bottom of the page.
- Only the server owner can delete all data of the server (settings, levels, cases, tickets with transcripts, appeals, verifications, starboard posts and Toxbot's AutoMod rules). Toxbot then leaves the server.
- Users can delete their own data on the Data page of their user settings. Their starboard posts are removed, their ticket messages are kept without name, ID and avatar; moderation cases and purchase records stay.

### Slots

`/slots bet` spins three reels for 10 to 10,000 credits, `/slots` alone shows the payout table. Three of a kind pay 8× (🍒) up to 500× (7️⃣), two of a kind return the bet. On average about 93 % of the bets come back, so slots slowly take credits out of the economy. Every member can spin once every five seconds. Credits cannot be bought with real money, so slots are play money only. Server admins can turn slots off on the Levels page of the dashboard.

### Blacklist

The bot owner manages the blacklist on the dashboard's administration page. Blocked users get an error for every command, earn no XP, cannot open tickets and cannot log in to the dashboard. Toxbot leaves blocked servers right away and whenever it is invited again. The bot reloads the list once a minute.

### Credits and items

The bot owner can add or remove credits and give or take away shop items, in Discord with `/system credits`, `/system give-item` and `/system take-item` or on the dashboard's administration page under **Users**, where you pick someone from the list or search by name or Discord ID. The balance never drops below 0; items that are taken away are unequipped. Retired and exclusive items can be given too. Every change shows up in the user's `/credits` history. `/system active-users` and that list show the users who run the most commands.

### Moderation

For moderation, Toxbot needs the permissions **Kick Members**, **Ban Members**, **Moderate Members** and **View Audit Log**. New invites ask for them; on servers where Toxbot already is, give them to its role. Its role has to be above the roles of the members it should moderate.

Actions in the dashboard are handed to the bot and carried out within a few seconds, so DMs, modlog and warning levels work the same as with `/mod`.

### Protection

- Verification gives new members a role and sends them a link to the dashboard's verification page by DM. There they log in with Discord and solve a captcha. Set up the role in Discord so that it only sees one channel, or let the automatic setup create the role and the channel and hide everything else from it; the dashboard can post a message with a button there. While verification is on, Toxbot also hides every newly created channel from the role, unless it already inherits that from its category or keeps everyone out anyway. The captcha is [Cloudflare Turnstile](https://www.cloudflare.com/application-services/products/turnstile/); after solving it, the role is removed and the autorole is given instead.
- Toxbot needs **Manage Roles** for verification, **Manage Messages** for the link filter, **Kick Members** for anti-raid and the minimum account age, and **Manage Server** to raise the verification level during a raid. Its role has to be above the verification role.
- Blocked domains and everything on the AutoMod page are Discord AutoMod rules that Toxbot creates when you save the Link filter or AutoMod page; Discord blocks those messages before they appear and reports them in the modlog channel. This needs **Manage Server**. If Discord refuses a rule, the dashboard says so, and Toxbot keeps deleting messages with blocked domains itself.
- Scam links are checked against [DSP Scam Links](https://github.com/Discord-AntiScam/scam-links) and [discord-phishing-links](https://github.com/nikolaischunk/discord-phishing-links), reloaded every six hours. The dashboard can check a single domain against them. Roles can be excluded from the link filter.

### Starboard and members

- On the Starboard page, pick a channel, the emojis that count (⭐ by default, up to ten, any Unicode emoji or the server's own) and how many reactions a message needs. Every person counts once per message, whichever of the emojis they use; reactions from the author and from bots don't count. The first emoji is shown on the post. When the count drops below the threshold again or the message is deleted, the post is removed from the starboard. Messages from NSFW channels only go to an NSFW starboard channel.
- Toxbot needs **View Channel**, **Send Messages**, **Embed Links** and **Read Message History** in the starboard channel and in the channels it watches.
- Members without "Manage Server" see the servers with Toxbot they are on. They get the member list and profiles with roles, levels and join date, plus the leaderboard and the starboard; cases, timeouts and moderation actions stay hidden from them.
- Moderators don't need "Manage Server": with **Kick Members**, **Ban Members**, **Timeout Members** or **View Audit Log** they see the modlog and the cases in member profiles, and the profile only offers the actions their permissions allow. **Ban Members** opens the unban appeals. The ticket history shows the tickets of every ticket type whose team roles they have.

### Tickets

- In the server settings under Tickets, pick a category for the ticket channels, a log channel and the channel for the buttons, add ticket types and post the buttons.
- Toxbot needs **Manage Channels** to create and delete ticket channels, **Manage Roles** for their permissions, **Read Message History** for the transcript and **Attach Files** to post it in the log channel.
- When a ticket is closed, up to 2000 messages are saved and shown under Tickets in the dashboard; the log channel gets the transcript as a text file.
- Texts in the ticket channel (welcome, close button, closing message) and the log use the server language.
- A ticket channel deleted by hand closes its ticket, but without a transcript, because Discord deletes the messages with the channel. Close tickets with the button or `/ticket close` to keep them.
- Banned members find the appeal form on the dashboard. With appeals turned on, ban DMs contain the link. Accepting an appeal unbans the member through the bot.

### Events

- `/event setup` or the Events page of the dashboard creates an event with its own role and a category with `#info`, `#chat` and one to five voice channels. Only the participants, the organizer and an optional team role see them; in `#info` only the organizer and the team can write.
- `/event invite` or the dashboard sends members a DM with **Join** and **Decline**. Joining gives the event role and posts a short note in the event's chat; declining takes the role away again.
- `/event end` or the dashboard deletes the role, the category and the channels. Toxbot needs **Manage Roles** and **Manage Channels** for events.

### Stream notifications

- Twitch reports streams through EventSub and YouTube reports new videos and streams through WebSub, both right away and straight to the dashboard. Every message is signed, so the dashboard only accepts the ones that really come from Twitch or YouTube.
- Every five minutes a scheduler also checks whether followed streamers are live and catches up on anything missed, every two minutes it posts scheduled YouTube streams when they start, and every hour it renews the YouTube subscriptions and repairs Twitch subscriptions that got lost.
- YouTube channels can be added by link, `@handle` or channel ID, Twitch channels by name or link. Toxbot tells videos and live streams apart, and each followed creator can get live notifications, video notifications or both.
- What happens to a Twitch notification when the stream ends (keep it, add the duration and VOD link, or delete it) is set for the whole server on the Streams page. With Server Premium, each streamer can have its own choice.
- The live role uses Discord's presence updates to see who is streaming.

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
