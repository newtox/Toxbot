# Privacy Policy for Toxbot Discord Bot

Last Updated: 25.10.2026

## Introduction

This Privacy Policy explains how Toxbot ("we", "our", or "the bot") and the Toxbot dashboard collect, use, and protect information when you use them.

## Information We Collect

### When you use the bot
- **Discord user and server IDs**: to know who used a command and which server's settings apply
- **Settings**: your language and embed color, and each server's welcome and goodbye messages, autorole, level-up and tag settings
- **Command usage**: which command you used and when. The total number of your commands is shown on your profile card. Each use is also logged with your username and ID in a private channel that only the developer can see.
- **Levels**: your XP and level, globally and per server, and when you last received XP. To give XP for chat messages, the bot notices that you sent a message on a server. **The content of your messages is not stored**, except in tickets (see below).
- **Credits and shop**: your credit balance, a history of what you earned and spent (for example daily rewards, level-ups, votes and purchases), your daily streak and the backgrounds you own and use
- **Tags**: the name and content of tags you create, how often they were used, and reports about tags, including who reported them and why
- **Stream notifications**: the Twitch and YouTube channels a server follows (channel ID, name and avatar), the server's notification settings, and which streams and videos were announced in which message
- **Live role**: to give the live role, the bot sees on servers with a live role whether you are currently streaming on Discord. This status is not stored.
- **Server Premium**: which server you chose for Server Premium
- **Moderation**: when a moderator warns, times out, kicks, bans or unbans you on a server, or when Discord reports such an action from its audit log, we store a case with your ID and name, the moderator's ID and name, the reason and the duration. Moderators of that server can see these cases and your roles, join date and level in the dashboard. The member list in the dashboard is loaded from Discord when it is opened and is not stored.
- **Link filter**: on servers that turn on the link filter, the bot checks new messages for links. Messages with unwanted links are deleted; if the server also warns or times out, a moderation case with the blocked domain is stored. Other messages are not stored.
- **Verification**: on servers with verification, we store when you joined and when you verified. The captcha is provided by Cloudflare Turnstile.
- **Tickets**: when you open a ticket, we store the ticket number, type, your ID and name, and who closed it and why. When a ticket is closed with Toxbot, the messages in the ticket channel are saved as a transcript: author ID, name and avatar, the text and links to attachments. The server's team can read the transcript in the dashboard, and a copy is posted in the server's ticket log channel.
- **Unban appeals**: if you are banned and send an appeal, we store your ID, name, your text, the ban reason Discord tells us, and the team's decision and answer.
- **Purchases**: when you buy Premium or an XP boost, Discord tells us which product you bought and how long it is valid. **We never see your payment details**; payments are handled entirely by Discord.

### When you use the dashboard
- When you log in with Discord, we receive your Discord ID, username, display name, avatar and the list of your servers. We use the server list only to show which servers you can manage.
- A session cookie and a login cookie keep you logged in until you log out.
- To show your servers, we keep the access token Discord gives us at login, encrypted, and renew it automatically. It is deleted when you log out.

### Profile cards
To draw your profile card, the dashboard loads your current avatar from Discord and keeps it for up to one hour.

## How We Use Your Information

We use the collected information to:
- Provide the bot's and dashboard's features, such as settings, levels, leaderboards, the shop, tags and Premium
- Prevent abuse, for example by removing reported tags and blocking users or servers that misuse Toxbot. For a block we store the user or server ID, a short reason and the date.
- Improve and develop new features
- Respond to user requests and support needs

We do not sell your data and do not use it for advertising.

## Visibility

Some information is visible to other users on purpose: your level, rank, credits and command count on your profile card, your position on leaderboards, and the tags you create, including that you are their owner.

## Third-Party Services

- **Discord** runs the platform and handles all payments. See [Discord's Privacy Policy](https://discord.com/privacy).
- **Cloudflare Turnstile** checks that a person, not a bot, solves the verification. Your browser connects to Cloudflare for this. See [Cloudflare's Privacy Policy](https://www.cloudflare.com/privacypolicy/).
- **Top.gg**: if you vote for Toxbot on Top.gg, Top.gg sends us your Discord ID so we can give you credits. See [Top.gg's Privacy Policy](https://top.gg/privacy).
- **Twitch** and **YouTube**: for stream notifications we ask them for public channel, stream and video information and receive notifications when a followed channel goes live. No data about you is sent there. See [Twitch's Privacy Notice](https://www.twitch.tv/p/legal/privacy-notice/) and [Google's Privacy Policy](https://policies.google.com/privacy).
- **waifu.pics** provides the images for roleplay and image commands. No data about you is sent there.
- Images in tags are loaded from the image host the tag's creator chose (for example Discord or Imgur) when a tag is shown.

## Data Security

We implement reasonable security measures to protect your information:
- Database access is restricted and password-protected
- We use environment variables to secure sensitive credentials
- Dashboard sessions are encrypted

## Data Retention

- Your settings, levels, credits, purchases and tags are kept as long as you use Toxbot, or until you delete them in the dashboard or ask us to.
- A server's settings, tickets, unban appeals and verifications are deleted after Toxbot has been removed from that server, at the latest the next time the bot restarts.
- The owner of a server can delete all data of that server at any time in the dashboard. Toxbot then leaves the server.
- Followed streamers are removed together with the server's settings. Announcement records are deleted after 30 days.
- Moderation cases are kept as long as the server uses Toxbot, so moderators can see a member's history. You can ask us to delete them.
- Server levels stay stored even after Toxbot leaves a server, so they are still there if it is added again. You can ask us to delete them.

## Your Rights

You have the right to:
- Request access to your personal data
- Request correction or deletion of your data
- Object to or restrict certain processing

You can delete your data yourself in the dashboard under **Profile → Delete my data**. This deletes your settings, your levels on every server, credits, backgrounds, tags, Server Premium choices, unban appeals and your dashboard login. Your messages in ticket transcripts are kept for the servers, but without your name, ID and avatar. Moderation cases about you belong to the servers where they happened and are kept; ask the server's team or us if you want them removed. Records of purchases from Discord are kept, and purchases are not refunded by deleting your data; refunds are handled by Discord.

For everything else, contact us using the details below.

## Changes to This Policy

We may update this Privacy Policy from time to time. We will notify users of any significant changes through Discord announcements.

## Contact

If you have questions about this Privacy Policy, please contact me at [justin.lys26@gmail.com](mailto:justin.lys26@gmail.com) or open an issue on this GitHub repository.
