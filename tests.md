# Tests

This project uses PHPUnit. The suite is split by feature area and includes optional integration tests. Integration tests are skipped unless you opt in with environment variables.

## Quick Start

1. Install dependencies: `composer install`
2. Run all tests: `vendor/bin/phpunit --do-not-cache-result`
3. Run a subset: `vendor/bin/phpunit --do-not-cache-result tests/Parsing`
4. Run all tests inside Docker (php container only, lib mode):

```powershell
$env:BF_MODE = 'lib'
$env:BF_SERVICES = 'mysql,redis'
powershell -File scripts/bf-test.ps1
```

## Optional Integrations

Some tests require external services or PHP extensions. These are skipped by default and only run when the relevant environment variables are set.

### Network

Enable network-dependent tests:

```
setx DEV_ELATION_NETWORK_TESTS 1
```

`RUN_NETWORK_TESTS=1` is also accepted.

### MySQL (mysqli)

Required PHP extension: `mysqli`

```
setx DEV_ELATION_MYSQL_HOST 127.0.0.1
setx DEV_ELATION_MYSQL_PORT 3306
setx DEV_ELATION_MYSQL_USER root
setx DEV_ELATION_MYSQL_PASS password
setx DEV_ELATION_MYSQL_DB develation_test
```

### MongoDB (mongodb)

Required PHP extension: `mongodb`

```
setx DEV_ELATION_MONGO_URI mongodb://127.0.0.1:27017
```

### Memcached

Required PHP extension: `memcached`

```
setx DEV_ELATION_MEMCACHED_HOST 127.0.0.1
setx DEV_ELATION_MEMCACHED_PORT 11211
```

### DB Queue Integration

Runs MySQL-backed queue tests (requires MySQL env vars above):

```
setx DEV_ELATION_DBQUEUE_TESTS 1
```

### Email

Enable email test coverage (no external SMTP is required, but the test can be noisy):

```
setx DEV_ELATION_EMAIL_TESTS 1
```

## Notes

- Skipped tests indicate optional integrations are not configured.
- If a full suite run is slow, run feature suites individually from `tests/`.
