h# Installation and Setup

## Installing Composer

To install Composer, follow these steps:

1. Open your terminal or command prompt.
2. Navigate to the project directory.
3. Run the following command to install Composer:
   ```bash
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   ```
4. Move the Composer executable to a directory that is in your system's PATH.

## Installing Dependencies

To install the dependencies required for this project, run the following command in your terminal or command prompt:

```bash
composer install
```

## Running the Server

To run the server, navigate to the project directory and execute the following command:

```bash
php -S localhost:8000 -t public public/index.php
```

Open your web browser and navigate to `http://localhost:8000` to access the application.

Note: Make sure you have PHP installed on your system and the `php` executable is in your system's PATH.
