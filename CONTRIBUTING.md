# Contributing to TASLibPHP

Thanks for helping. Bug reports, feature requests and patches are all welcome.

## Reporting bugs and requesting features

Open an [issue](https://github.com/TrueArrowSoftware/TASLibPHP/issues). For a bug, include:

- what you expected and what happened
- the TASLibPHP version (see `VERSION`) and PHP version
- the smallest code or steps that reproduce it

Security problems go through [SECURITY.md](SECURITY.md), not public issues.

## Sending a patch

1. Fork the repository and create a branch from `master`.
2. Make your change, with tests (see below).
3. Run the test suite, and make sure it passes.
4. Open a pull request on GitHub describing what changed and why. Link the issue if there is one.

## Requirements for contributions

- **Tests:** a new feature or bug fix must come with a PHPUnit test in `test/`. A bug fix needs a test that fails without the fix. Pull requests that add functionality without tests will be asked to add them before merging.
- **Style:** follow [PSR-12](https://www.php-fig.org/psr/psr-12/) for new and changed code. Match the surrounding naming and comment style.
- **PHP version:** code must run on PHP 8.2 or higher.
- **Database access:** use the prepared-statement form (`Execute($sql, $values)` and friends) for anything that includes user input.
- **Docs:** update `README.md` and `docs/` when behavior changes, and add an entry under `[Unreleased]` in [CHANGELOG.md](CHANGELOG.md), including its **Security** heading ("None" if there is nothing to report).

## Running the tests

```bash
composer install
cp test/configure.local.sample.php test/configure.local.php   # set your test DB credentials
php ./vendor/bin/phpunit --bootstrap ./test/bootstrap.php ./test
```

## License

By contributing you agree your work is released under the project's [MIT License](LICENSE).
