# Contributing
JIRA REST API Client is an open source, community-driven project. If you'd like to contribute, feel free to do this, but remember to follow these few simple rules:

## Submitting an issues
- A reproducible example is required for every bug report, otherwise it will most probably be __closed without warning__.
- If you are going to make a big, substantial change, let's discuss it first.

## Working with Pull Requests
1. Create your feature addition or a bug fix branch based on __`master`__ branch in your repository's fork.
2. Make necessary changes, but __don't mix__ code reformatting with code changes on topic.
3. Add entry in `CHANGELOG.md` file following https://keepachangelog.com/en/1.0.0/ format (if applicable).
4. Add tests for those changes (please look into `tests/` folder for some examples). This is important so we don't break it in a future version unintentionally.
5. Check your code using "Coding Standard" (see below).
6. Commit your code.
7. Squash your commits by topic to preserve a clean and readable log.
8. Create Pull Request.

## Running the Tests

### OPTIONAL: Configuration for Integration Tests

The `repository` term used below refers to your clone of current project GitHub repository fork and not JIRA instance.

To be able to run integration tests locally please follow these steps once:

1. make sure repository is located in web server document root (or it's sub-folder)
2. copy `phpunit.xml.dist` file into `phpunit.xml` file
3. in the `phpunit.xml` file:
 * uncomment part, where `REPO_URL` environment variable is defined
 * set `REPO_URL` environment variable value to URL, from where repository can be accessed (e.g. `http://localhost/path/to/repository/[:<portNumber>]`)
 
Before running tests, change directory to the root of your repository and run `php -S localhost:<portNumber>`

Then run the unit tests as per normal.

N.B. you can study the `.github/workflows/tests.yml` file to see how we run these tests on our build servers by way of example.

### OPTIONAL: Creating a LIVE test locally

1. copy `phpunit.xml.dist` file into `phpunit.xml` file
2. in the `phpunit.xml` file:
 * uncomment part, where `LIVE_TEST_ENDPOINT`, `LIVE_TEST_USERNAME` and `LIVE_TEST_PASSWORD` environment variables are defined
 * populate them with the corresponding values
3. write the desired LIVE test in the `ApiTest::testLive` method (e.g. `$api->addWorklog('JRA-12', '12m');`)
4. run it using `vendor/bin/phpunit --filter testLive` command
5. confirm, that results in your Jira instance are as expected
6. rollback changes to the `ApiTest::testLive` method before sending a PR

### Running Test Suite

Make sure that you don't break anything with your changes by running:

```bash
$> vendor/bin/phpunit
```

## Checking coding standard violations

This library uses [Coding Standard](https://github.com/aik099/CodingStandard) to ensure consistent formatting across the code base. Make sure you haven't introduced any Coding Standard violations by running following command in the root folder of the library:

```bash
$> vendor/bin/phpcs --standard="vendor/aik099/coding-standard/CodingStandard" src tests
```

or by making your IDE ([instructions for PhpStorm](https://www.jetbrains.com/help/phpstorm/using-php-code-sniffer.html)) to check them automatically.
