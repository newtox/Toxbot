# Toxbot Dashboard

Web dashboard for [Toxbot](../README.md), built with Laravel, Livewire and Tailwind CSS.

- **Discord login** via OAuth2 (scopes `identify guilds`)
- **Server list** with every server with Toxbot you are on. Servers where you are owner, administrator or have "Manage Server" can be configured; on the others you see the member list and the starboard, plus the modlog with Kick Members, Ban Members, Timeout Members or View Audit Log, the unban appeals with Ban Members, and the tickets of the ticket types whose team roles you have. Servers you manage without Toxbot get an invite button.
- **Landing page** with features, Premium and invite link, plus live numbers for servers, members with XP and tags
- **Server overview** with member count, streamers, Server Premium status and every module at a glance; every setting has its own page, grouped into General (welcome, goodbye, autorole, language, data), Community (levels, streams, live role, tags) and Moderation & safety (moderation, verification, link filter, anti-raid, tickets). The groups fold in the sidebar and remember their state, and each page can be reset on its own. Members, starboard, modlog, ticket history and appeals sit above them under Server.
- **Welcome and goodbye messages** with channel picker, placeholders (`&mention`, `&user`, `&server`) and a live preview that looks like Discord
- **Autorole**, limited to roles below Toxbot's highest role, same rule as `/autorole`
- **Server language** for everything posted to the whole server, such as ticket and verify buttons, ticket logs and raid alarms
- **Level-up messages** and **tags** per server: turn them on or off, pick the channel, send level-ups as a DM as well, unblock blocked tags and manage server tags (20 per server, 100 with Server Premium, only on that server, shown by `/tag show` ahead of global tags with the same name)
- **User settings** with their own sidebar: profile card with level, rank and credits, language and embed color (same as `/language` and `/color`, the color is also the dashboard's accent color), Premium, shop, tags and your data
- **Shop** to buy and switch profile backgrounds, with a live preview of the card. The shop starts empty; backgrounds are uploaded on the administration page, and cards without one get a gradient in the user's embed color.
- **Tags**: create, edit and delete your own tags
- **Administration** for the bot owner, with its own sidebar: handle reported tags, manage the backgrounds in the shop, block users or servers, and give Premium or Server Premium without a purchase, with or without an end date
- **Profile cards** rendered as PNG under `/card/{user}.png`, used by `/profile` in Discord
- **Top.gg webhook** under `/webhooks/topgg` that pays credits for votes
- **Stream notifications**: add Twitch and YouTube channels per server, pick the channel, ping role and message. Twitch uses EventSub webhooks (`/webhooks/twitch`), YouTube uses WebSub (`/webhooks/youtube`). Servers with Server Premium get more streamers, a message and ping role per streamer, filters and an action for when the stream ends.
- **Server Premium picker** on the Premium page of the user settings: Premium users choose one server they manage that gets Server Premium
- **Live role** settings, the role itself is given by the bot
- **Starboard**: channel, the emojis that count (⭐ by default, plus any Unicode emoji or the server's own) and the number of reactions needed, plus the list of posts sorted by date or reactions. Expired Discord image links are refreshed when the list is shown. Members without "Manage Server" see the list too.
- **Moderation**: member list with search and filters, member profiles with level, roles and cases (members without moderation permissions see the list and profiles without cases, timeouts and actions; moderators only get the actions their permissions allow), actions (warn, timeout, kick, ban, unban) that the bot carries out, the modlog with filters, and settings for the modlog channel, DMs and warning levels
- **Protection**: verification page with Cloudflare Turnstile under `/verify/{server}`, link filter with scam lists and a domain check, anti-raid and a minimum account age
- **Tickets**: ticket types with emoji, text and team roles, the button message, and the ticket history with transcripts
- **Unban appeals**: form for banned members under `/appeal/{server}` and a list for the team to accept or reject
- **Automatic setup** on the overview and in the moderation, protection and ticket settings: creates the missing channels, roles and permissions
- **Data**: a server's Data page resets its settings or deletes all its data and removes Toxbot (owner only); the Data page in the user settings deletes your own data
- **Info** with links (GitHub, Top.gg, privacy policy, terms, contact), every slash command with its options and a search, read from Discord's application commands, and a system page with the bot's status and the versions of bot and dashboard
- **All 10 bot languages**. For logged-in users, the dashboard language and the bot language are the same setting.

## How it works with the bot

The dashboard reads and writes Toxbot's own tables (`guilds`, `users`, `tags`, `member_levels`, `backgrounds` and so on) in the shared MySQL database. The bot reads them fresh on every event and command, so there is no restart and no extra API between the two.

Channels, roles and bot info come straight from the Discord REST API using the bot token. The dashboard also posts messages, creates channels and roles and swaps roles for verification this way.

Actions that need the bot's full logic, such as bans with modlog and DM, are written to `mod_actions`; the bot picks them up within a few seconds.

The dashboard keeps its own logins in `dashboard_users`, because `users` belongs to the bot.

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Fill in `.env`:

| Variable | Where to get it |
| --- | --- |
| `DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET` | Discord Developer Portal → your application → OAuth2 |
| `DISCORD_BOT_TOKEN` | Same token as `token=` in the bot's `.env` |
| `DB_*` | Toxbot's MySQL database, or `DB_CONNECTION=sqlite` for a throwaway local database |
| `TOXBOT_OWNER_ID` | Your Discord user ID, only this account sees the administration |
| `TOXBOT_PREMIUM_USERS`, `TOXBOT_PREMIUM_USER_SKUS` | Premium without subscription for these user IDs, and the SKU ID of the subscription |
| `TOPGG_WEBHOOK_SECRET` | Secret of the Top.gg webhook, leave empty to turn vote rewards off |
| `TOXBOT_CONTACT_EMAIL`, `TOXBOT_SUPPORT_INVITE` | Contact address (defaults to justin.lys26@gmail.com) and an optional support server invite, shown on the info page |
| `TOXBOT_PREMIUM_GUILDS`, `TOXBOT_PREMIUM_GUILD_SKUS` | Server Premium without subscription for these server IDs, and the SKU ID of the server subscription |
| `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET` | [dev.twitch.tv/console](https://dev.twitch.tv/console) → your application |
| `STREAM_WEBHOOK_SECRET` | Any random string with 10 to 100 characters |
| `YOUTUBE_API_KEY` | Optional, Google Cloud → YouTube Data API v3 |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Optional, Cloudflare → Turnstile widget for the dashboard domain; without them the verification page only asks for the Discord login |

PHP needs the `pdo_mysql` and `gd` extensions; `gd` draws the profile cards.

In the Developer Portal, add `http://localhost:8000/auth/discord/callback` under **OAuth2 → Redirects**.

```bash
php artisan migrate
php artisan serve
```

Stream webhooks need a public HTTPS address, so Twitch and YouTube notifications only arrive on the real deployment. Adding streamers works locally as long as the Twitch credentials are set.

The scheduler (`php artisan schedule:work`) runs `streams:sync` every five minutes, `streams:check-upcoming` every two minutes and `streams:maintain` every hour.

Open `http://localhost:8000`. Use `localhost`, not `127.0.0.1`, otherwise the Discord login fails because the session cookie belongs to the other host name.

### Migrations and an existing bot database

`create_toxbot_tables` is safe to run against a database the bot already uses. Existing tables are left as they are; it only adds the `notify` and `stream` columns to `guilds`, which the bot writes but which were missing from the original `toxbot.sql`. On an empty database it creates all bot tables with proper primary keys.

The later migrations only add tables and columns for tags, levels, credits, the shop, entitlements, stream notifications, moderation, protection, tickets, unban appeals, the server language, the starboard, the bot status, server tags and Premium grants, all with defaults, so an older bot version keeps working with them.

## Translations

One PHP file per area and language under `lang/{locale}/` (`menu`, `login`, `home`, `servers`, `overview`, `guild`, `profile`, `common`, `levels`, `shop`, `tags`, `admin`, `streams`, `moderation`, `protection`, `tickets`, `setup`, `data`, `starboard`, `info`), used as `__('guild.welcome_title')`.

The list of languages lives in `config/app.php` under `locales`, together with the matching bot language code. To add a language, add it there, copy `lang/en/` to `lang/{code}/` and translate it. `LocaleTest` checks that every language has every key.

## Tests

```bash
php artisan test
```

The Discord API is faked with `Http::fake()`, so the tests need no token and no network.

## Production

The `Dockerfile` builds a production image (PHP 8.4 with Nginx and GD, assets compiled, migrations run on start). It is built by GitHub Actions and deployed together with the bot; see [Deployment](../README.md#deployment) in the main README.
