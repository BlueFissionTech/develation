# Security Tools

This library includes lightweight utilities for hashing, checksums, and content IDs. All helpers are built to fit the DevElation behavioral model and support hooks via `DevElation::apply()` / `DevElation::do()`.

## Hash

`BlueFission\Security\Hash` provides hashing, HMAC, file checksums, and content IDs.

### Quick Start

```php
use BlueFission\Security\Hash;

$hash = new Hash('sha256');
$digest = $hash->hash('payload');

if ($hash->verify('payload', $digest)) {
    echo 'valid';
}
```

### HMAC

```php
$signature = $hash->hmac('payload', 'secret');
```

### File Checksums

```php
$checksum = $hash->checksumFile('/path/to/file.txt');
```

### Content IDs

```php
$contentId = $hash->contentId(['id' => 7]); // cid:<hash>
```

### Algorithms

```php
$algorithms = Hash::algorithms();
$ok = Hash::supports('sha256');
```
