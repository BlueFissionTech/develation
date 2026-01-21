<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\DevElation as Dev;

class Prompt extends Obj
{
    protected $_data = [
        'lastPrompt' => '',
        'lastResponse' => '',
    ];

    protected $_types = [
        'lastPrompt' => DataTypes::STRING,
        'lastResponse' => DataTypes::STRING,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->behavior(new Action(Action::INPUT), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && is_array($meta->data ?? null)) {
                $data = $meta->data;
                if (array_key_exists('prompt', $data)) {
                    $this->setValue('lastPrompt', (string)$data['prompt']);
                }
                if (array_key_exists('response', $data)) {
                    $this->setValue('lastResponse', (string)$data['response']);
                }
            }
            $this->trigger(Event::RECEIVED, $meta);
        });

        $this->behavior(new Action(Action::PROCESS), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && Val::isNotNull($meta->data)) {
                $this->trigger(Event::PROCESSED, $meta);
            }
        });

        $this->behavior(new Event(Event::RECEIVED), function ($behavior) {
            $this->halt(State::WAITING_FOR_INPUT);
        });
    }

    public function askPrompt(string $message, $default = null, ?string $input = null, bool $trim = true): string
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $default, $input, $this]);
        $this->perform(State::WAITING_FOR_INPUT);
        $response = self::ask($message, $default, $input, $trim);
        $this->perform(new Action(Action::INPUT), new Meta(data: ['prompt' => $message, 'response' => $response]));
        $this->perform(new Action(Action::PROCESS), new Meta(data: $response));
        $response = Dev::apply('_out', $response);
        Dev::do('_after', [$response, $this]);
        return $response;
    }

    public function confirmPrompt(string $message, bool $default = false, ?string $input = null): bool
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $default, $input, $this]);
        $this->perform(State::WAITING_FOR_INPUT);
        $response = self::confirm($message, $default, $input);
        $this->perform(new Action(Action::INPUT), new Meta(data: ['prompt' => $message, 'response' => $response ? 'true' : 'false']));
        $this->perform(new Action(Action::PROCESS), new Meta(data: $response));
        $response = (bool)Dev::apply('_out', $response);
        Dev::do('_after', [$response, $this]);
        return $response;
    }

    public function choicePrompt(string $message, array $choices, $default = null, ?string $input = null): string
    {
        $message = Dev::apply('_in', $message);
        $choices = Dev::apply('_in', $choices);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $choices, $default, $input, $this]);
        $this->perform(State::WAITING_FOR_INPUT);
        $response = self::choice($message, $choices, $default, $input);
        $this->perform(new Action(Action::INPUT), new Meta(data: ['prompt' => $message, 'response' => $response]));
        $this->perform(new Action(Action::PROCESS), new Meta(data: $response));
        $response = Dev::apply('_out', $response);
        Dev::do('_after', [$response, $this]);
        return $response;
    }

    protected function setValue(string $field, $value): void
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof \BlueFission\IVal) {
            $current->val($value);
            return;
        }
        $this->_data[$field] = $value;
    }
    public static function ask(string $message, $default = null, ?string $input = null, bool $trim = true): string
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        $response = $input;
        if (Val::isNull($response)) {
            $response = self::readFromStdin($message);
        }

        if ($trim) {
            $response = trim((string)$response);
        }

        if ($response === '' && Val::isNotNull($default)) {
            return (string)$default;
        }

        return (string)Dev::apply('_out', $response);
    }

    public static function confirm(string $message, bool $default = false, ?string $input = null): bool
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        $defaultValue = $default ? 'y' : 'n';
        $response = self::ask($message, $defaultValue, $input, true);
        $normalized = Str::lower(trim($response));

        if (in_array($normalized, ['y', 'yes', 'true', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['n', 'no', 'false', '0'], true)) {
            return false;
        }

        return (bool)Dev::apply('_out', $default);
    }

    public static function choice(string $message, array $choices, $default = null, ?string $input = null): string
    {
        $message = Dev::apply('_in', $message);
        $choices = Dev::apply('_in', $choices);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        $response = self::ask($message, $default, $input, true);
        $normalized = Str::lower(trim($response));

        if ($normalized === '' && Val::isNotNull($default)) {
            $response = (string)$default;
        }

        if (array_key_exists($response, $choices)) {
            return (string)$choices[$response];
        }

        foreach ($choices as $value) {
            if (strtolower((string)$value) === $normalized) {
                return (string)$value;
            }
        }

        return (string)Dev::apply('_out', $response);
    }

    protected static function readFromStdin(string $message): string
    {
        if ($message !== '') {
            fwrite(STDOUT, $message);
        }

        $line = fgets(STDIN);
        if ($line === false) {
            return '';
        }

        return $line;
    }
}
