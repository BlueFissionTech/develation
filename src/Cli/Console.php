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
use BlueFission\Cli\Util\Spinner;
use BlueFission\Cli\Util\Working;
use BlueFission\Cli\Util\Prompt as PromptUtil;
use BlueFission\Cli\Util\Cursor;
use BlueFission\DevElation as Dev;

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

        $config = Dev::apply('_in', $config);
        if (Val::isNotNull($config) && Arr::isAssoc($config)) {
            $this->assign($config);
        }

        Dev::do('_after', [$this]);
    }

    public function supportsColors(?bool $value = null): bool
    {
        $value = Dev::apply('_in', $value);
        Dev::do('_before', [$value, $this]);
        if (Val::isNull($value)) {
            $supports = $this->_data['supportsColors'];
            if (Val::isNull($supports)) {
                $supports = Ansi::supportsColors();
                $this->_data['supportsColors'] = $supports;
            }
            $supports = (bool)Dev::apply('_out', $supports);
            $this->trigger(Event::PROCESSED, new Meta(data: $supports));
            Dev::do('_after', [$supports, $this]);
            return $supports;
        }

        $this->_data['supportsColors'] = $value;
        $value = (bool)Dev::apply('_out', $value);
        $this->trigger(Event::PROCESSED, new Meta(data: $value));
        Dev::do('_after', [$value, $this]);
        return $value;
    }

    public function outputHandler(?callable $handler = null)
    {
        $handler = Dev::apply('_in', $handler);
        if (Val::isNull($handler)) {
            return $this->_data['outputHandler'];
        }

        $this->_data['outputHandler'] = $handler;
        Dev::do('_after', [$this]);
        return $this;
    }

    public function inputHandler(?callable $handler = null)
    {
        $handler = Dev::apply('_in', $handler);
        if (Val::isNull($handler)) {
            return $this->_data['inputHandler'];
        }

        $this->_data['inputHandler'] = $handler;
        Dev::do('_after', [$this]);
        return $this;
    }

    public function write(string $text, bool $newline = false): string
    {
        $text = Dev::apply('_in', $text);
        $newline = Dev::apply('_in', $newline);
        Dev::do('_before', [$text, $newline, $this]);
        $payload = $newline ? $text . PHP_EOL : $text;
        $this->_data['lastOutput'] = $payload;

        $this->perform(new Action(Action::SEND), new Meta(data: $payload));

        $handler = $this->_data['outputHandler'];
        if (is_callable($handler)) {
            call_user_func($handler, $payload);
        } else {
            echo $payload;
        }

        $payload = Dev::apply('_out', $payload);
        $this->trigger(Event::SENT, new Meta(data: $payload));
        $this->trigger(Event::PROCESSED, new Meta(data: $payload));
        Dev::do('_after', [$payload, $this]);

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
        $text = Dev::apply('_in', $text);
        $color = Dev::apply('_in', $color);
        $styles = Dev::apply('_in', $styles);
        Dev::do('_before', [$text, $color, $styles, $this]);
        $output = Ansi::colorize($text, $color, $styles, $this->supportsColors());
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function dim(string $text): string
    {
        $text = Dev::apply('_in', $text);
        Dev::do('_before', [$text, $this]);
        $output = Ansi::dim($text, $this->supportsColors());
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function gray(string $text): string
    {
        $text = Dev::apply('_in', $text);
        Dev::do('_before', [$text, $this]);
        $output = Ansi::gray($text, $this->supportsColors());
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function table(array $headers, array $rows, array $options = []): string
    {
        $headers = Dev::apply('_in', $headers);
        $rows = Dev::apply('_in', $rows);
        $options = Dev::apply('_in', $options);
        Dev::do('_before', [$headers, $rows, $options, $this]);
        $this->perform(new Action(Action::TRANSFORM), new Meta(data: ['headers' => $headers, 'rows' => $rows]));
        $output = Table::render($headers, $rows, $options);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function progress(int $total, int $current, int $width = 40): string
    {
        $total = Dev::apply('_in', $total);
        $current = Dev::apply('_in', $current);
        $width = Dev::apply('_in', $width);
        Dev::do('_before', [$total, $current, $width, $this]);
        $bar = new ProgressBar($total, $width);
        $bar->setCurrent($current);

        $output = $bar->render();
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function spinner(string $label = '', ?array $frames = null, int $intervalMs = 120): Spinner
    {
        $label = Dev::apply('_in', $label);
        $frames = Dev::apply('_in', $frames);
        $intervalMs = Dev::apply('_in', $intervalMs);
        Dev::do('_before', [$label, $frames, $intervalMs, $this]);
        $spinner = new Spinner($label, $frames, $intervalMs);
        $this->trigger(Event::PROCESSED, new Meta(data: $spinner));
        Dev::do('_after', [$spinner, $this]);
        return $spinner;
    }

    /**
     * Run a task while showing a spinner (DevOps request).
     * If no task is provided, returns a Working instance for manual control.
     */
    public function working(string $label = '', ?callable $work = null, int $intervalMs = 120, ?array $frames = null)
    {
        $label = Dev::apply('_in', $label);
        $work = Dev::apply('_in', $work);
        $intervalMs = Dev::apply('_in', $intervalMs);
        $frames = Dev::apply('_in', $frames);
        Dev::do('_before', [$label, $work, $intervalMs, $frames, $this]);

        $working = new Working($label, $frames, $intervalMs, function ($text) {
            $this->write($text);
        });

        if (Val::isNotNull($work)) {
            $result = $working->run($work);
            $this->trigger(Event::PROCESSED, new Meta(data: $result));
            Dev::do('_after', [$result, $this]);
            return $result;
        }

        $this->trigger(Event::PROCESSED, new Meta(data: $working));
        Dev::do('_after', [$working, $this]);
        return $working;
    }

    public function prompt(string $message, $default = null, ?string $input = null): string
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $default, $input, $this]);
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        $output = $this->_prompt->askPrompt($message, $default, $inputValue);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function confirm(string $message, bool $default = false, ?string $input = null): bool
    {
        $message = Dev::apply('_in', $message);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $default, $input, $this]);
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        $output = $this->_prompt->confirmPrompt($message, $default, $inputValue);
        $output = (bool)Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function choice(string $message, array $choices, $default = null, ?string $input = null): string
    {
        $message = Dev::apply('_in', $message);
        $choices = Dev::apply('_in', $choices);
        $default = Dev::apply('_in', $default);
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$message, $choices, $default, $input, $this]);
        $inputValue = $input;
        if (Val::isNull($inputValue) && is_callable($this->_data['inputHandler'])) {
            $inputValue = call_user_func($this->_data['inputHandler'], $message);
        }

        $output = $this->_prompt->choicePrompt($message, $choices, $default, $inputValue);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function cursor(int $x = 1, int $y = 1, bool $visible = true): Cursor
    {
        $x = Dev::apply('_in', $x);
        $y = Dev::apply('_in', $y);
        $visible = Dev::apply('_in', $visible);
        Dev::do('_before', [$x, $y, $visible, $this]);
        $cursor = new Cursor($x, $y, $visible);
        Dev::do('_after', [$cursor, $this]);
        return $cursor;
    }

    public function lastOutput(): string
    {
        $output = (string)$this->_data['lastOutput'];
        return (string)Dev::apply('_out', $output);
    }
}
