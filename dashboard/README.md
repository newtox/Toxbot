# Toxbot Dashboard

Web dashboard for [Toxbot](../README.md), built with Laravel, Livewire and Tailwind CSS.

- **Discord login** via OAuth2 (scopes `identify guilds`)
- **Server list** with every server where you are owner, administrator or have "Manage Server". Servers without Toxbot get an invite button.
- **Welcome and goodbye messages** with channel picker, placeholders (`&mention`, `&user`, `&server`) and a live preview that looks like Discord
- **Autorole**, limited to roles below Toxbot's highest role, same rule as `/autorole`
- **Profile** with language and embed color, same settings as `/language` and `/color`. The embed color is also the dashboard's accent color.
- **All 10 bot languages**. For logged-in users, the dashboard language and the bot language are the same setting.

## How it works with the bot

The dashboard reads and writes Toxbot's own tables (`guilds`, `users`) in the shared MySQL database. The bot reads them fresh on every event and command, so there is no restart and no extra API between the two.

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

In the Developer Portal, add `http://localhost:8000/auth/discord/callback` under **OAuth2 → Redirects**.

```bash
php artisan migrate
php artisan serve
```

Open `http://localhost:8000`. Use `localhost`, not `127.0.0.1`, otherwise the Discord login fails because the session cookie belongs to the other host name.

### Migrations and an existing bot database

`create_toxbot_tables` is safe to run against a database the bot already uses. Existing tables are left as they are; it only adds the `notify` and `stream` columns to `guilds`, which the bot writes but which were missing from the original `toxbot.sql`. On an empty database it creates all bot tables with proper primary keys.

## Translations

One PHP file per area and language under `lang/{locale}/` (`menu`, `login`, `servers`, `guild`, `profile`, `common`), used as `__('guild.welcome_title')`.

The list of languages lives in `config/app.php` under `locales`, together with the matching bot language code. To add a language, add it there, copy `lang/en/` to `lang/{code}/` and translate it. `LocaleTest` checks that every language has every key.

## Tests

```bash
php artisan test
```

The Discord API is faked with `Http::fake()`, so the tests need no token and no network.

## Production

The `Dockerfile` builds a production image (PHP 8.4 with Nginx, assets compiled, migrations run on start). It is built by GitHub Actions and deployed together with the bot; see [Deployment](../README.md#deployment) in the main README.
