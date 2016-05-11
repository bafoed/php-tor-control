# PHP Tor Control library

## Install

Via Composer

``` bash
$ composer require bafoed/php-tor-control
```

## Usage

You can see a simple example of usage in [example.php](example.php).

Available methods are:

    <?php
    $torControl->changeIP(); // creates new tor identity
    $torControl->getVersion(); // returns tord version 
    $torControl->getAddress(); // returns IP address of server
    $torControl->getUser(); // returns username of tord user
    $torControl->getAccountingStats(); // return some network stats if accounting is enabled
    $torControl->_getInfo('circuit-status'); // call other tord functions (look for GETINFO docs)

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email bafoed@bafoed.ru instead of using the issue tracker.

## Credits

- [bafoed][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[link-author]: https://github.com/bafoed
