# String entity decoding

`Str::decodeEntities()` is an opt-in text-normalization helper. It does not
change `Str::stripTags()` or sanitize HTML output.

## Current boundary

`Str::_stripTags()` delegates to PHP `strip_tags()` and calls `alter()`; the
instance operation is fluent and the static `Str::stripTags($text)` returns a
raw string through `Val`'s helper dispatch. It deliberately leaves HTML
entity references alone. `src/HTML/Form.php` uses native
`html_entity_decode()` internally, but the public `Str` helper surface has no
equivalent. Decoding and tag removal must remain separate opt-in steps; neither
is an HTML sanitizer or a text-equality policy.

## Public contract

`Str::decodeEntities($text, $flags = ENT_QUOTES | ENT_HTML5,
$encoding = 'UTF-8')` returns a raw string through the existing static helper
dispatch. `Str::make($text)->decodeEntities($flags, $encoding)` alters the
wrapped value and returns the same `Str` object, with normal change and action
events. Both use PHP `html_entity_decode()` with the supplied flags and
encoding; no custom parser, tag removal, whitespace normalization, or equality
policy is applied.

```php
use BlueFission\Str;

$plain = Str::make('<em>Tom &amp; Jo</em>')->stripTags()->decodeEntities()->val();
// Tom & Jo
```

The default follows native `html_entity_decode()` byte behavior. PHP 8.2.11
tests with `ENT_QUOTES | ENT_HTML5` and UTF-8 decode `&amp;`, `&lt;`,
`&quot;`, `&apos;`, and `&#65;`; left `&unknown;` and incomplete `&amp`
unchanged; left malformed input bytes (`C3 28`) unchanged while still decoding
a later `&amp;`; and do not remove adjacent `<em>` tags. Explicit flags and
encoding are passed through. PHP warns and assumes UTF-8 for an unsupported
encoding; this helper retains that native warning/fallback behavior rather
than silently selecting another encoding itself. Callers should supply a
known encoding. These observations do not qualify every encoding.

`tests/StrTest.php` covers byte-exact parity and return types for empty input,
named/numeric/unknown/incomplete entities, quotes, adjacent tags, malformed
UTF-8, explicit flags/encoding, native unsupported-encoding warning, event
dispatch, and composed `stripTags()->decodeEntities()` use.

Entity decoding is presentation/normalization, not HTML sanitization. Do not
use it as a security boundary or assume it defines text equality.
