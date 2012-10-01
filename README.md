# Minion
A lightweight, extensible IRC bot written in PHP 5.3.3.

## Installation
Minion has no dependencies. Just clone the repo.

## Configuration
1. Copy `config.php-dist` to `config.php`
2. `config.php` overrides `lib/config.base.php`. Copy stuff from `lib/config.base.php` into `config.php` and make any changes you desire.

## Running
`./minion`

## Extending
Plugins are in the `plugins` directory. Their configuration goes in `config.php`.
