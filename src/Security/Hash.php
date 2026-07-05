<?php

namespace BlueFission\Security;

use BlueFission\Arr;
use BlueFission\Data\FileSystem;
use BlueFission\Flag;
use BlueFission\IVal;
use BlueFission\Net\HTTP;
use BlueFission\Num;
use BlueFission\Obj;
use BlueFission\Ref;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Hash extends Obj
{
    protected $_data = [
        'algo' => 'sha256',
        'last' => '',
        'errors' => [],
    ];

    protected $_types = [
        'algo' => DataTypes::STRING,
        'last' => DataTypes::STRING,
        'errors' => DataTypes::ARRAY,
    ];

    public function __construct(?string $algo = null)
    {
        parent::__construct();

        if (Val::isNotNull($algo)) {
            $this->setValue('algo', Str::make($algo)->val());
        }

        Dev::do('_after', [$this]);
    }

    public static function algorithms(): array
    {
        return Arr::make(hash_algos())->val();
    }

    public static function supports(string $algo): bool
    {
        return Arr::contains(self::algorithms(), $algo, true);
    }

    public static function value($data, ?string $algo = null, bool $raw = false): string
    {
        $hash = new self($algo);
        return $hash->hash($data, $algo, $raw);
    }

    public static function hmacValue($data, string $key, ?string $algo = null, bool $raw = false): string
    {
        $hash = new self($algo);
        return $hash->hmac($data, $key, $algo, $raw);
    }

    public static function checksum(string $path, ?string $algo = null, bool $raw = false): string
    {
        $hash = new self($algo);
        return $hash->checksumFile($path, $algo, $raw);
    }

    public static function contentIdValue($data, ?string $algo = null, string $prefix = 'cid'): string
    {
        $hash = new self($algo);
        return $hash->contentId($data, $algo, $prefix);
    }

    public function algorithm(?string $algo = null): string
    {
        if (Val::isNull($algo)) {
            return (string)$this->field('algo');
        }

        $this->setValue('algo', Str::make($algo)->val());
        return (string)$this->field('algo');
    }

    public function last(): string
    {
        return (string)$this->field('last');
    }

    public function hash($data, ?string $algo = null, bool $raw = false): string
    {
        $data = Dev::apply('_in', $data);
        $algo = $algo ?? $this->algorithm();
        $raw = (bool)Dev::apply('_in', $raw);
        Dev::do('_before', [$data, $algo, $raw, $this]);

        $this->clearErrors();
        $this->perform(new Action(Action::PROCESS), new Meta(data: ['algo' => $algo]));

        $normalized = $this->normalizeData($data);
        if (!$this->isAlgorithmSupported($algo)) {
            $this->addError('algorithm', 'unsupported_hash_algorithm');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $digest = hash($algo, $normalized, $raw);
        if ($digest === false) {
            $this->addError('hash', 'hash_failed');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $this->setValue('last', (string)$digest);
        $digest = (string)Dev::apply('_out', $digest);
        $this->perform(Event::SUCCESS, new Meta(data: $digest));
        $this->perform(Event::PROCESSED, new Meta(data: $digest));
        Dev::do('_after', [$digest, $this]);

        return $digest;
    }

    public function hmac($data, string $key, ?string $algo = null, bool $raw = false): string
    {
        $data = Dev::apply('_in', $data);
        $key = Dev::apply('_in', $key);
        $algo = $algo ?? $this->algorithm();
        $raw = (bool)Dev::apply('_in', $raw);
        Dev::do('_before', [$data, $key, $algo, $raw, $this]);

        $this->clearErrors();
        $this->perform(new Action(Action::PROCESS), new Meta(data: ['algo' => $algo, 'hmac' => true]));

        $normalized = $this->normalizeData($data);
        if (!$this->isAlgorithmSupported($algo)) {
            $this->addError('algorithm', 'unsupported_hash_algorithm');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $digest = hash_hmac($algo, $normalized, $key, $raw);
        if ($digest === false) {
            $this->addError('hash', 'hash_failed');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $this->setValue('last', (string)$digest);
        $digest = (string)Dev::apply('_out', $digest);
        $this->perform(Event::SUCCESS, new Meta(data: $digest));
        $this->perform(Event::PROCESSED, new Meta(data: $digest));
        Dev::do('_after', [$digest, $this]);

        return $digest;
    }

    public function verify($data, string $hash, ?string $algo = null, bool $raw = false): bool
    {
        $data = Dev::apply('_in', $data);
        $hash = Dev::apply('_in', $hash);
        $algo = $algo ?? $this->algorithm();
        $raw = (bool)Dev::apply('_in', $raw);

        $computed = $this->hash($data, $algo, $raw);
        if (Str::isEmpty($computed) || Str::isEmpty($hash)) {
            return false;
        }

        return hash_equals($computed, $hash);
    }

    public function checksumFile(string $path, ?string $algo = null, bool $raw = false): string
    {
        $path = Dev::apply('_in', $path);
        $algo = $algo ?? $this->algorithm();
        $raw = (bool)Dev::apply('_in', $raw);
        Dev::do('_before', [$path, $algo, $raw, $this]);

        $this->clearErrors();
        $this->perform(new Action(Action::PROCESS), new Meta(data: ['algo' => $algo, 'file' => $path]));

        if (!FileSystem::fileExists($path)) {
            $this->addError('file', 'file_not_found');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        if (!$this->isAlgorithmSupported($algo)) {
            $this->addError('algorithm', 'unsupported_hash_algorithm');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $digest = hash_file($algo, $path, $raw);
        if ($digest === false) {
            $this->addError('hash', 'hash_failed');
            $this->perform(Event::FAILURE, new Meta(data: $this->errors()));
            Dev::do('_after', [$this]);
            return '';
        }

        $this->setValue('last', (string)$digest);
        $digest = (string)Dev::apply('_out', $digest);
        $this->perform(Event::SUCCESS, new Meta(data: $digest));
        $this->perform(Event::PROCESSED, new Meta(data: $digest));
        Dev::do('_after', [$digest, $this]);

        return $digest;
    }

    public function contentId($data, ?string $algo = null, string $prefix = 'cid'): string
    {
        $prefix = Dev::apply('_in', $prefix);
        $hash = $this->hash($data, $algo);
        if ($hash === '') {
            return '';
        }
        $output = Str::make($prefix)->append(':')->append($hash)->val();
        return (string)Dev::apply('_out', $output);
    }

    public function errors(): array
    {
        $value = $this->field('errors');
        return Arr::is($value) ? Arr::toArray($value) : [];
    }

    public function clearErrors(): self
    {
        $this->setValue('errors', []);
        return $this;
    }

    protected function normalizeData($data): string
    {
        if (Str::is($data)) {
            return $data;
        }

        if (Num::isIntStrict($data) || Num::isFloatStrict($data) || Flag::isBoolStrict($data)) {
            return (string)$data;
        }

        if (Arr::is($data) || is_object($data)) {
            return HTTP::jsonEncode($data);
        }

        if (is_resource($data)) {
            $contents = Ref::resource($data)->read();
            return $contents === false ? '' : $contents;
        }

        return (string)$data;
    }

    protected function isAlgorithmSupported(string $algo): bool
    {
        return static::supports($algo);
    }

    protected function addError(string $field, string $message): void
    {
        $errors = Arr::make($this->errors());
        $fieldErrors = Arr::make($errors->hasKey($field) ? $errors[$field] : []);

        $fieldErrors->push(['message' => $message]);
        $errors->set($field, $fieldErrors->val());

        $this->setValue('errors', $errors->val());
    }

    protected function setValue(string $field, $value): void
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof IVal) {
            $current->val($value);
            return;
        }
        $this->_data[$field] = $value;
    }
}
