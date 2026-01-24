<?php

namespace BlueFission\Security;

use BlueFission\Arr;
use BlueFission\IVal;
use BlueFission\Obj;
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
            $this->setValue('algo', (string)$algo);
        }

        Dev::do('_after', [$this]);
    }

    public static function algorithms(): array
    {
        return hash_algos();
    }

    public static function supports(string $algo): bool
    {
        return in_array($algo, hash_algos(), true);
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

        $this->setValue('algo', (string)$algo);
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
        if ($computed === '' || $hash === '') {
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

        if (!is_file($path)) {
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
        $output = $prefix . ':' . $hash;
        return (string)Dev::apply('_out', $output);
    }

    public function errors(): array
    {
        $value = $this->field('errors');
        return is_array($value) ? $value : [];
    }

    public function clearErrors(): self
    {
        $this->setValue('errors', []);
        return $this;
    }

    protected function normalizeData($data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_int($data) || is_float($data) || is_bool($data)) {
            return (string)$data;
        }

        if (is_array($data) || is_object($data)) {
            return json_encode($data);
        }

        if (is_resource($data)) {
            $contents = stream_get_contents($data);
            return $contents === false ? '' : $contents;
        }

        return (string)$data;
    }

    protected function isAlgorithmSupported(string $algo): bool
    {
        return in_array($algo, hash_algos(), true);
    }

    protected function addError(string $field, string $message): void
    {
        $errors = $this->errors();
        if (!array_key_exists($field, $errors)) {
            $errors[$field] = [];
        }
        $errors[$field][] = ['message' => $message];
        $this->setValue('errors', $errors);
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
