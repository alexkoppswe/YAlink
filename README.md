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
![use](https://github.com/user-attachments/assets/0dca6f5e-f808-4964-8fa3-89fb6cbd3b01)<br>
- * Note, the options menu dosent show in image.*  
  
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


## 🤝 Contributing

Contributions are welcome just submit a pull request or donate a coffee.

<a href='https://ko-fi.com/X8X11DTGJQ' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi6.png?v=6' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

## License
📄 YAlink is released under the GNU Affero General Public License. See the `LICENSE.txt` file for more information.


