# Minion
A lightweight, extensible IRC bot written in PHP 5.3.3.

## Installation
Minion has no dependencies by default. Just clone the repo. If you want to use the Log plugin with a database backend, you'll need to ensure that the correct database driver for PDO is installed. On debian-based Linux distributions, this can usually be achieved with `apt-get install php5-mysql` or `apt-get install php5-sqlite`.

## Configuration
1. Copy `config.php-dist` to `config.php`
2. `config.php` overrides `lib/config.base.php`. Copy stuff from `lib/config.base.php` into `config.php` and make any changes you desire, or add a `__construct()` function to `config.php` in order to extend the base config.

## Running
`./minion`

## Extending
Plugins are in the `plugins` directory. Their configuration goes in `config.php`. The Channel plugin is a good example of simple trigger/response behavior. The Log plugin is an example of a more complex plugin architecture.

## Testing
Minion's tests use [PHPUnit](http://phpunit.de). Install PHPUnit and run `phpunit tests/` in your minion base directory.
