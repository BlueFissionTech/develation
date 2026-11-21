# String entity decoding: review candidate

This documents the API decision requested in #326. It does not add a helper
or change `Str::stripTags()`.

## Current boundary

`Str::_stripTags()` delegates to PHP `strip_tags()` and calls `alter()`; the
instance operation is fluent and the static `Str::stripTags($text)` returns a
raw string through `Val`'s helper dispatch. It deliberately leaves HTML
entity references alone. `src/HTML/Form.php` uses native
`html_entity_decode()` internally, but the public `Str` helper surface has no
equivalent. Decoding and tag removal must remain separate opt-in steps; neither
is an HTML sanitizer or a text-equality policy.

## Proposed contract for review, not yet implemented

Consider `Str::decodeEntities($text, $flags = ENT_QUOTES | ENT_HTML5,
$encoding = 'UTF-8')`, backed by a fluent instance `_decodeEntities()` helper.
Under the existing `Str` dispatch convention, the instance call would mutate
the stored string through `alter()`, return the same `Str` object, and emit the
normal change/action events; the static call would return the decoded raw
string. The public name, parameter types/defaults, and encoding validation
need maintainer and affected-owner approval before implementation.

For the proposed default mode, follow native `html_entity_decode()` byte
behavior rather than introducing a custom parser. An isolated PHP 8.2.11
probe with `ENT_QUOTES | ENT_HTML5` and UTF-8 decoded `&amp;`, `&lt;`,
`&quot;`, `&apos;`, and `&#65;`; left `&unknown;` and incomplete `&amp`
unchanged; left malformed input bytes (`C3 28`) unchanged while still decoding
a later `&amp;`; and did not remove adjacent `<em>` tags. These observations
are test cases, not a claim that all encodings or invalid-input modes have
been qualified.

Before implementation, test byte-exact parity and return types for empty
input, named/numeric/unknown/incomplete entities, quotes, adjacent tags,
malformed UTF-8, explicit flags and encodings, and composed
`stripTags()->decodeEntities()` use. Verify that the instance's change and
action events follow existing `alter()`/dynamic-dispatch behavior. Review
whether invalid flag/encoding values should propagate PHP's native failure or
have an explicit library error; avoid silent substitution or normalization.

No dependency, automatic decoding, consumer migration, release, or merge is
implied by this candidate.
