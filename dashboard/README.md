# Toxbot Dashboard

Web dashboard for [Toxbot](../README.md), built with Laravel, Livewire and Tailwind CSS.

- **Discord login** via OAuth2 (scopes `identify guilds`)
- **Server list** with every server where you are owner, administrator or have "Manage Server". Servers without Toxbot get an invite button.
- **Landing page** with features, Premium and invite link, plus live numbers for servers, members with XP and tags
- **Server overview** with member count, streamers, Server Premium status and every module at a glance; the settings are split into pages for welcome and roles, levels, streams and tags
- **Welcome and goodbye messages** with channel picker, placeholders (`&mention`, `&user`, `&server`) and a live preview that looks like Discord
- **Autorole**, limited to roles below Toxbot's highest role, same rule as `/autorole`
- **Level-up messages** and **tags** per server: turn them on or off, pick the channel, unblock blocked tags
- **Profile** with the profile card, level, rank and credits, plus language and embed color, same settings as `/language` and `/color`. The embed color is also the dashboard's accent color.
- **Shop** to buy and switch profile backgrounds, with a live preview of the card. The shop starts empty; backgrounds are uploaded on the administration page, and cards without one get a gradient in the user's embed color.
- **Tags**: create, edit and delete your own tags
- **Administration** for the bot owner: handle reported tags and manage the backgrounds in the shop
- **Profile cards** rendered as PNG under `/card/{user}.png`, used by `/profile` in Discord
- **Top.gg webhook** under `/webhooks/topgg` that pays credits for votes
- **Stream notifications**: add Twitch and YouTube channels per server, pick the channel, ping role and message. Twitch uses EventSub webhooks (`/webhooks/twitch`), YouTube uses WebSub (`/webhooks/youtube`). Servers with Server Premium get more streamers, a message and ping role per streamer, filters and an action for when the stream ends.
- **Server Premium picker** on the profile page: Premium users choose one server they manage that gets Server Premium
- **Live role** settings, the role itself is given by the bot
- **Moderation**: member list with search and filters, member profiles with level, roles and cases, actions (warn, timeout, kick, ban, unban) that the bot carries out, the modlog with filters, and settings for the modlog channel, DMs and warning levels
- **All 10 bot languages**. For logged-in users, the dashboard language and the bot language are the same setting.

## How it works with the bot

The dashboard reads and writes Toxbot's own tables (`guilds`, `users`, `tags`, `member_levels`, `backgrounds` and so on) in the shared MySQL database. The bot reads them fresh on every event and command, so there is no restart and no extra API between the two.

Channels, roles and bot info come straight from the Discord REST API using the bot token.

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
| `TOXBOT_PREMIUM_GUILDS`, `TOXBOT_PREMIUM_GUILD_SKUS` | Server Premium without subscription for these server IDs, and the SKU ID of the server subscription |
| `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET` | [dev.twitch.tv/console](https://dev.twitch.tv/console) → your application |
| `STREAM_WEBHOOK_SECRET` | Any random string with 10 to 100 characters |
| `YOUTUBE_API_KEY` | Optional, Google Cloud → YouTube Data API v3 |

PHP needs the `pdo_mysql` and `gd` extensions; `gd` draws the profile cards.

In the Developer Portal, add `http://localhost:8000/auth/discord/callback` under **OAuth2 → Redirects**.

```bash
php artisan migrate
php artisan serve
```

Stream webhooks need a public HTTPS address, so Twitch and YouTube notifications only arrive on the real deployment. Adding streamers works locally as long as the Twitch credentials are set.

The scheduler (`php artisan schedule:work`) runs `streams:maintain` every hour and `streams:check-upcoming` every two minutes.

Open `http://localhost:8000`. Use `localhost`, not `127.0.0.1`, otherwise the Discord login fails because the session cookie belongs to the other host name.

### Migrations and an existing bot database

`create_toxbot_tables` is safe to run against a database the bot already uses. Existing tables are left as they are; it only adds the `notify` and `stream` columns to `guilds`, which the bot writes but which were missing from the original `toxbot.sql`. On an empty database it creates all bot tables with proper primary keys.

The later migrations only add tables and columns for tags, levels, credits, the shop, entitlements and stream notifications, all with defaults, so an older bot version keeps working with them.

## Translations

One PHP file per area and language under `lang/{locale}/` (`menu`, `login`, `servers`, `guild`, `profile`, `common`, `levels`, `shop`, `tags`, `admin`, `streams`), used as `__('guild.welcome_title')`.

The list of languages lives in `config/app.php` under `locales`, together with the matching bot language code. To add a language, add it there, copy `lang/en/` to `lang/{code}/` and translate it. `LocaleTest` checks that every language has every key.

## Tests

```bash
php artisan test
```

The Discord API is faked with `Http::fake()`, so the tests need no token and no network.

## Production

The `Dockerfile` builds a production image (PHP 8.4 with Nginx and GD, assets compiled, migrations run on start). It is built by GitHub Actions and deployed together with the bot; see [Deployment](../README.md#deployment) in the main README.
