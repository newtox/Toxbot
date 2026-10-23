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
- **Server setup**: welcome and goodbye messages, autorole, level-up messages
- **Levels**: XP for messages and commands, with a global level and a separate level per server, plus `/leaderboard`
- **Credits and shop**: `/daily`, credits for level-ups and Top.gg votes, profile backgrounds to buy with `/shop`
- **Profile cards**: `/profile` shows level, rank, credits and the chosen background as an image
- **Tags**: global custom commands with `/tag`, reportable and blockable per server
- **Stream notifications** for Twitch and YouTube: a message with role ping when a streamer goes live or uploads a video
- **Live role**: members who stream on Discord get a role while they are live
- **Moderation**: `/mod warn`, `timeout`, `untimeout`, `kick`, `ban` (also temporary) and `unban`, numbered cases in a modlog channel, DMs to members, automatic actions after a number of warnings, and bans, kicks and timeouts done in Discord or by other bots are logged too
- **Premium** through Discord subscriptions: double XP and daily reward, premium backgrounds, more tags and tags as embeds, plus a 24 hour XP boost to buy
- **Server Premium** for one server of the subscriber's choice, included in Premium: more streamers, a custom message and ping role per streamer, game and title filters, and editing or deleting the notification when the stream ends

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

The server runs both from `docker-compose.yml`, deployed as a Portainer stack. Secrets are not part of the images; they are set as stack environment variables. See `stack.env.example` for the full list.

Backgrounds uploaded in the dashboard are stored in `/docker_volumes/toxbot-dashboard/backgrounds` on the host. Create the folder once and give it to the container user:

```bash
mkdir -p /docker_volumes/toxbot-dashboard/backgrounds
chown 33:33 /docker_volumes/toxbot-dashboard/backgrounds
```

### Premium and Top.gg

- Create the SKUs in the Developer Portal under **Monetization**: a user subscription for Premium and a consumable for the XP boost. Put their IDs into `PREMIUM_USER_SKUS` and `XP_BOOST_SKU`.
- `PREMIUM_USERS` gives Premium to the listed user IDs without a subscription, for example for testing.
- For vote rewards, add `https://<dashboard>/webhooks/topgg` as webhook on Top.gg and put the generated secret into `TOPGG_WEBHOOK_SECRET`.
- Server Premium comes with the user subscription: subscribers pick one server on their profile page in the dashboard. Discord only allows one published subscription per app, so a separate server subscription is optional; if you ever sell one, put its SKU ID into `PREMIUM_GUILD_SKUS`. `PREMIUM_GUILDS` gives Server Premium to the listed server IDs without a subscription.

### Moderation

For moderation, Toxbot needs the permissions **Kick Members**, **Ban Members**, **Moderate Members** and **View Audit Log**. New invites ask for them; on servers where Toxbot already is, give them to its role. Its role has to be above the roles of the members it should moderate.

Actions in the dashboard are written to the `mod_actions` table and carried out by the bot within a few seconds, so DMs, modlog and warning levels work the same as with `/mod`.

### Stream notifications

- Create an application at [dev.twitch.tv/console](https://dev.twitch.tv/console) (OAuth redirect `http://localhost`, category "Chat Bot" or "Other") and put its client ID and secret into `TWITCH_CLIENT_ID` and `TWITCH_CLIENT_SECRET`.
- Set `STREAM_WEBHOOK_SECRET` to a random string with 10 to 100 characters, for example `openssl rand -hex 32`. It signs the Twitch and YouTube webhooks.
- `YOUTUBE_API_KEY` is optional (Google Cloud → YouTube Data API v3). Without it, YouTube channels have to be added by channel ID or `/channel/UC…` link, and live streams cannot be told apart from videos.
- Twitch and YouTube call `https://<dashboard>/webhooks/twitch` and `https://<dashboard>/webhooks/youtube`. This only works on the public HTTPS deployment, not locally.
- The `scheduler` service in `docker-compose.yml` renews the YouTube subscriptions and posts scheduled YouTube streams when they start. It uses the dashboard image.
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
- `services/`: the logic behind the commands and the event handlers, for example levels, moderation, premium and the live role; `events.dart` registers the commands and handlers
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
