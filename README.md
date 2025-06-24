[![CC BY-NC-SA 4.0][cc-by-nc-sa-shield]][cc-by-nc-sa]

This work is licensed under a
[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License][cc-by-nc-sa].

[![CC BY-NC-SA 4.0][cc-by-nc-sa-image]][cc-by-nc-sa]

[cc-by-nc-sa]: http://creativecommons.org/licenses/by-nc-sa/4.0/
[cc-by-nc-sa-image]: https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png
[cc-by-nc-sa-shield]: https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg

# Toxbot: The Dart Discord Bot

Welcome to the **Toxbot** repository! Here, you'll find **Toxbot**, a Discord bot crafted in Dart using `nyxx` and `nyxx_commands` for seamless integration with Discord's slash command system. Whether you're here to use the bot, extend its functionality, or learn about Dart in a Discord bot context, you're in the right place.

## Overview

- **Toxbot**: A versatile Discord bot designed for community engagement, moderation, and entertainment, utilizing slash commands.
- **Technology**: Built with Dart, leveraging `nyxx` for Discord interactions and `nyxx_commands` for managing slash commands.

## How to Use

### Prerequisites

- **Dart SDK**: Ensure Dart is installed and configured on your system.
- **Discord Developer Account**: Have a Developer Application to obtain a Bot Token.

### Installation

1. **Clone this repository**:
   ```bash
   git clone https://github.com/newtox/Toxbot.git
   cd Toxbot
   ```

2. **Setup**:
   - Create a `.env` file based on the provided `.env.example`. Copy the content from:
     ```plaintext
     .env.example
     ```
   - Replace the placeholders with your bot details:
     ```
     token=your_bot_token_here
     db_host=your_database_host
     db_port=your_database_port
     db_user=your_database_username
     db_password=your_database_password
     db_name=your_database_name
     ```

3. **Run the Bot**:
   - After setting up, run:
     ```bash
     dart run bin/main.dart
     ```
   - If dependencies aren't installed, first run:
     ```
     dart pub get
     ```

### Usage

Toxbot uses slash commands. Users can see all commands by typing `/` in Discord. For instance, to check if Toxbot is online, use:

```dart
/ping
```

## Contributing

Interested in adding features to Toxbot? Here's how:

- **Fork** the repository.
- **Create** a new branch: `git checkout -b feature/your-feature-name`
- **Commit** your contributions: `git commit -m 'Add new feature: your-feature-name'`
- **Push** to your branch: `git push origin feature/your-feature-name`
- **Submit** a pull request detailing your script or improvements.

## License

This project is licensed under the **Creative Commons Attribution-NonCommercial-ShareAlike (CC BY-NC-SA)** License. For more details, see:

- **License**: [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)

By contributing, you agree that your contributions will be licensed under the same license.

## Contact

Have a feature request or found a bug?

- Open an issue directly on GitHub.
- Or reach out to me at [contact@placeholder.de](mailto:contact@placeholder.de)