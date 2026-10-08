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

Both share the same MySQL database. Whatever you change in the dashboard (welcome and goodbye messages, autorole, language, embed color) is picked up by the bot right away.

## Usage

Toxbot uses slash commands. Type `/` in Discord to see all of them, for example `/ping` to check whether the bot is online.

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
