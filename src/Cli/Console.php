<?php
namespace BlueFission\Cli;

use BlueFission\Obj;
use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Cli\Util\Ansi;
use BlueFission\Cli\Util\Screen;
use BlueFission\Cli\Util\Table;
use BlueFission\Cli\Util\ProgressBar;
use BlueFission\Cli\Util\Prompt as PromptUtil;
use BlueFission\Cli\Util\Cursor;

class Console extends Obj
{
    protected PromptUtil $_prompt;

    protected $_data = [
        'supportsColors' => null,
        'outputHandler' => null,
        'inputHandler' => null,
        'lastOutput' => '',
    ];

    protected $_types = [
        'supportsColors' => DataTypes::BOOLEAN,
        'lastOutput' => DataTypes::STRING,
    ];

    public function __construct($config = null)
    {
        parent::__construct();

        $this->_prompt = new PromptUtil();

        if (Val::isNotNull($config) && Arr::isAssoc($config)) {
            $this->assign($config);
        }
    }

    public function supportsColors(?bool $value = null): bool
    {
        if (Val::isNull($value)) {
            $supports = $this->_data['supportsColors'];
            if (Val::isNull($supports)) {
                $supports = Ansi::supportsColors();
                $this->_data['supportsColors'] = $supports;
            }
            return (bool)$supports;
        }

        $this->_data['supportsColors'] = $value;
        return (bool)$value;
    }

    public function outputHandler(?callable $handler = null)
    {
        if (Val::isNull($handler)) {
            return $this->_data['outputHandler'];
        }

        $this->_data['outputHandler'] = $handler;
        return $this;
    }

    public function inputHandler(?callable $handler = null)
    {
        if (Val::isNull($handler)) {
            return $this->_data['inputHandler'];
        }

        $this->_data['inputHandler'] = $handler;
        return $this;
    }

    public function write(string $text, bool $newline = false): string
    {
        $payload = $newline ? $text . PHP_EOL : $text;
        $this->_data['lastOutput'] = $payload;

        $this->perform(new Action(Action::SEND), new Meta(data: $payload));

        $handler = $this->_data['outputHandler'];
        if (is_callable($handler)) {
            call_user_func($handler, $payload);
        } else {
            echo $payload;
        }

        $this->trigger(Event::SENT, new Meta(data: $payload));

        return $payload;
    }

    public function writeln(string $text): string
    {
        return $this->write($text, true);
    }

    public function rewriteLine(string $text): string
    {
        return $this->write(Screen::rewriteLine($text));
    }

    public function clearScreen(): string
    {
        return $this->write(Screen::clearScreen());
    }

    public function color(string $text, ?string $color = null, array $styles = []): string
    {
        return Ansi::colorize($text, $color, $styles, $this->supportsColors());
    }

    public function dim(string $text): string
    {
        return Ansi::dim($text, $this->supportsColors());
    }

    public function gray(string $text): string
    {
        return Ansi::gray($text, $this->supportsColors());
    }

    public function table(array $headers, array $rows, array $options = []): string
    {
        $this->perform(new Action(Action::TRANSFORM), new Meta(data: ['headers' => $headers, 'rows' => $rows]));
        $output = Table::render($headers, $rows, $options);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));

        return $output;
    }

    public function progress(int $total, int $current, int $width = 40): string
    {
        $bar = new ProgressBar($total, $width);
        $bar->setCurrent($current);

        return $bar->render();
    }

    public function prompt(string $message, $default = null, ?string $input = null): string
    {
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        return $this->_prompt->askPrompt($message, $default, $inputValue);
    }

    public function confirm(string $message, bool $default = false, ?string $input = null): bool
    {
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        return $this->_prompt->confirmPrompt($message, $default, $inputValue);
    }

    public function choice(string $message, array $choices, $default = null, ?string $input = null): string
    {
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        return $this->_prompt->choicePrompt($message, $choices, $default, $inputValue);
    }

    public function cursor(int $x = 1, int $y = 1, bool $visible = true): Cursor
    {
        return new Cursor($x, $y, $visible);
    }

    public function lastOutput(): string
    {
        return (string)$this->_data['lastOutput'];
    }
}
