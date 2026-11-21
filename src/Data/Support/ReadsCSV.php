<?php

namespace BlueFission\Data\Support;

use BlueFission\Str;

/** Read one bounded logical CSV record from a local regular file. */
trait ReadsCSV
{
	/**
	 * Read only the first logical CSV record, without creating a file or reading
	 * later records. The byte limit includes any BOM and record terminator.
	 * Content filters are deliberately not applied to this byte-bounded read.
	 *
	 * @return array|null Fields, or null when the file has no record at all.
	 * @throws \InvalidArgumentException Invalid path, limit, or CSV dialect.
	 * @throws \UnexpectedValueException Non-regular target.
	 * @throws \RuntimeException Missing/unreadable file or IO failure.
	 * @throws \LengthException First record exceeds the byte limit.
	 * @throws \DomainException Malformed quoted record or escape.
	 */
	public static function firstCsvRecordBounded(
		string $path,
		int $maxBytes,
		string $delimiter = ',',
		string $enclosure = '"',
		string $escape = '',
		bool $stripBom = false
	): ?array {
		if ($maxBytes < 0) {
			throw new \InvalidArgumentException('Maximum CSV record bytes must not be negative.');
		}
		if (Str::len($delimiter) !== 1 || Str::len($enclosure) !== 1
			|| ($escape !== '' && Str::len($escape) !== 1)
			|| $delimiter === $enclosure
			|| ($escape !== '' && ($escape === $delimiter || $escape === $enclosure))
			|| Str::has("\r\n", $delimiter)
			|| Str::has("\r\n", $enclosure)
			|| ($escape !== '' && Str::has("\r\n", $escape))) {
			throw new \InvalidArgumentException('CSV delimiter, enclosure and escape must be distinct single-byte symbols.');
		}

		$hasScheme = preg_match('~^[a-z][a-z0-9+.-]*:~i', $path) === 1;
		$isDrivePath = preg_match('~^[a-z]:(?!//)~i', $path) === 1;
		if ($path === '' || Str::has($path, "\0") || ($hasScheme && !$isDrivePath)) {
			throw new \InvalidArgumentException('A local file path is required.');
		}
		if (!file_exists($path)) {
			throw new \RuntimeException('File does not exist.');
		}
		if (!is_file($path)) {
			throw new \UnexpectedValueException('Target is not a regular file.');
		}
		if (!is_readable($path)) {
			throw new \RuntimeException('File is not readable.');
		}

		$handle = @fopen($path, 'rb');
		if ($handle === false) {
			throw new \RuntimeException('File could not be opened for reading.');
		}
		try {
			$stat = fstat($handle);
			if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
				throw new \UnexpectedValueException('Target is not a regular file.');
			}

			$fields = [];
			$field = '';
			$state = 'start';
			$escaped = false;
			$bytesRead = 0;
			$bomPending = '';
			$bomDecided = false;
			while (true) {
				if ($bytesRead === $maxBytes) {
					$current = fstat($handle);
					if ($current === false) {
						throw new \RuntimeException('File size could not be checked.');
					}
					if ($current['size'] > $bytesRead) {
						throw new \LengthException('First CSV record exceeds the maximum byte count.');
					}
					$byte = null;
				} else {
					$read = fgetc($handle);
					if ($read === false) {
						if (!feof($handle)) {
							throw new \RuntimeException('CSV record could not be read.');
						}
						$byte = null;
					} else {
						$byte = $read;
						$bytesRead++;
					}
				}

				if ($byte === null) {
					$feed = $bomPending;
					if ($bytesRead === 0) {
						return null;
					}
				} elseif (!$bomDecided) {
					$bomPending .= $byte;
					if (!str_starts_with("\xef\xbb\xbf", $bomPending)) {
						$feed = $bomPending;
						$bomPending = '';
						$bomDecided = true;
					} elseif (Str::len($bomPending) === 3) {
						if (!$stripBom) {
							$field .= $bomPending;
						}
						$bomPending = '';
						$bomDecided = true;
						continue;
					} else {
						continue;
					}
				} else {
					$feed = $byte;
				}

				for ($index = 0, $length = Str::len($feed); $index < $length; $index++) {
					if (self::consumeCsvByte($feed[$index], $delimiter, $enclosure, $escape, $fields, $field, $state, $escaped)) {
						if ($feed[$index] === "\r") {
							// A single lookahead distinguishes bare CR from CRLF without
							// parsing any later record. Both terminator bytes count.
							$next = fgetc($handle);
							if ($next === false && !feof($handle)) {
								throw new \RuntimeException('CSV record could not be read.');
							}
							if ($next === "\n") {
								if ($bytesRead === $maxBytes) {
									throw new \LengthException('First CSV record exceeds the maximum byte count.');
								}
								$bytesRead++;
							}
						}
						return $fields;
					}
				}
				if ($byte === null) {
					if ($state === 'quoted' || $escaped) {
						throw new \DomainException('CSV record ends inside a quoted field or escape.');
					}
					$fields[] = $field;
					return $fields;
				}
			}
		} finally {
			fclose($handle);
		}
	}

	private static function consumeCsvByte(
		string $byte,
		string $delimiter,
		string $enclosure,
		string $escape,
		array &$fields,
		string &$field,
		string &$state,
		bool &$escaped
	): bool {
		if ($state === 'quoted') {
			if ($escaped) {
				if ($byte !== $enclosure && $byte !== $escape) {
					throw new \DomainException('Invalid CSV escape sequence.');
				}
				$field .= $byte;
				$escaped = false;
			} elseif ($escape !== '' && $byte === $escape) {
				$escaped = true;
			} elseif ($byte === $enclosure) {
				$state = 'afterQuote';
			} else {
				$field .= $byte;
			}
			return false;
		}

		if ($state === 'afterQuote') {
			if ($byte === $enclosure) {
				$field .= $enclosure;
				$state = 'quoted';
				return false;
			}
			if ($byte !== $delimiter && $byte !== "\r" && $byte !== "\n") {
				throw new \DomainException('Unexpected content after a quoted CSV field.');
			}
		}

		if ($byte === $delimiter) {
			$fields[] = $field;
			$field = '';
			$state = 'start';
			return false;
		}
		if ($byte === "\r" || $byte === "\n") {
			$fields[] = $field;
			return true;
		}
		if ($byte === $enclosure) {
			if ($state !== 'start') {
				throw new \DomainException('Quote inside an unquoted CSV field.');
			}
			$state = 'quoted';
			return false;
		}
		$field .= $byte;
		$state = 'unquoted';
		return false;
	}
}
