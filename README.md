# YAlink - Yet Another link shortener
Yet Another link shortener with password protection and temporary link hosting
## Description
🔗 YAlink is a web application that allows users to share links with others in a fast and easy way.

## Features
🌐 Link Sharing: Users can share links with others by generating unique shortened URLs.

🔗 Link Management: Users can password protect the link, add a time limit, and specify the number of views.

🔒 Security: The forms use CSRF protection and input validation (JS & PHP) and do not store data persistently on the client.

- Note that the JS validation can be customized for your needs, and there is no server lockout for brute force attacks on passwords.

## Images
![use](https://github.com/user-attachments/assets/0dca6f5e-f808-4964-8fa3-89fb6cbd3b01)
* Note, the <select> menu dosent show in image.

## Prerequisites
- PHP installed (Developed on v8.0+)
- A web server (e.g. Apache, Nginx)
- A database (Currently set up with SQLite3)

## Configuration
To configure follow these steps:
1. Open the `php/config.php` file located in the project directory.
2. Update the database connection settings with your own database credentials.
3. Customize other settings such as:
- timezone
- link hash
- domains (for CSRF)

as well as enable SSL in `token.php` (or move it to `config.php`).

## Contributing
🤝 We welcome contributions to the YAlink project. If you would like to contribute, please follow these guidelines:
- Fork the YAlink repository on GitHub.
- Create a new branch for your feature or bug fix.
- Make your changes and ensure they are properly tested.
- Submit a pull request to the main repository, explaining the changes you have made.

## License
📄 YAlink is released under the GNU Affero General Public License. See the `LICENSE.txt` file for more information.


