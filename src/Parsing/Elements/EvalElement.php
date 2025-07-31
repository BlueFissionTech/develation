<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Val;

class EvalElement extends Element implements IExecutableElement, IRenderableElement
{
    protected string $name;
    protected array $params;
    protected string $var;
    protected string $type;
    protected string $value = '';
    protected $generatorDriver;

    public function __construct(string $tag, string $match, string $raw, array $attributes = [])
    {
        parent::__construct($tag, $match, $raw, $attributes);
        $generator = GeneratorRegistry::get();
        if ($generator) {
            $this->echo($generator);
        }
    }

    public function setDriver($driver): void
    {
        $this->generatorDriver = $driver;
    }

    public function execute(): mixed
    {
        $this->name = $this->attributes['expression'];
        if (strpos($this->name, ':')) {
            $parts = explode(':', $this->name, 2);
            $this->name = $parts[0];
            $this->type = $parts[1];
        } else {
            $this->type = 'val'; // Default type
        }

        $append = false;
        $push = false;

        if (preg_match('/^
                (?<var>\$?[a-zA-Z_][a-zA-Z0-9_-]*)        # Variable name, optionally starting with $ (if name itself is variable)
                (?:(?<call>\((.*?)\)))?                   # Optional function call with arguments
                (?:(?<push>\[\]))?                        # Optional array push operator ([])
                (?:(?<append>\&))?                        # Optional append operator (&)
                (?::(?<cast>[a-zA-Z_][a-zA-Z0-9_-]*))?    # Optional type cast (e.g., :text, :number)
                (?:\s*->\s*
                    (\$\.
                        (?<chain>([a-zA-Z_][a-zA-Z0-9_-]*)\(["\']?(.*?)["\']?\)*)
                    )
                )?
                \s*(?<attributes>(([a-zA-Z_][a-zA-Z0-9_-]*)\s?=\s?(.*?)))?    # Attributes or assignment (e.g., key=value)
                $/xsm', $this->raw, $m)) {
            $this->name = $m['var'] ?? $this->name;
            $this->type = $m['cast'] ?? $this->type;
            $append = isset($m['append']) && $m['append'] === '&';
            $push = isset($m['push']) && $m['push'] === '[]';
        }

        if (strpos($this->name, '$') === 0) {
            $name = substr($this->name, 1);

            $this->name = $this->resolveValue($name);
        }

        $this->var = $this->attributes['assign'] ?? $this->name;

        $value = $this->evaluate();

        if (!$value || empty($value)) {
            $value = $this->getAttribute('default');
        }

        $this->value = $value ?? '';

        if ($append) {
            if (!$this->hasScopeVariable($this->var)) {
                $this->setScopeVariable($this->var, '');
            }

            $originalValue = $this->getScopeVariable($this->var);
            if (is_array($originalValue)) {
                $value = array_merge($originalValue, [$value]);
            } elseif (is_string($originalValue)) {
                $value = $originalValue . $value;
            } else {
                throw new \Exception("Cannot append to non-array/string variable '{$this->var}'.");
            }
        }

        if ($push) {
            if (!$this->hasScopeVariable($this->var)) {
                $this->setScopeVariable($this->var, []);
            }

            $originalValue = $this->getScopeVariable($this->var);
            if (is_array($originalValue)) {
                $value = array_merge($originalValue, [$value]);
            } else {
                throw new \Exception("Cannot push to non-array variable '{$this->var}'.");
            }
        }

        $this->setScopeVariable($this->var, $value);

        return $this->value;
    }

    public function render(): string
    {
        $silent = $this->getAttribute('silent');

        [$function, $params, $assign] = array_values($this->parseAdditional());

        if ($silent || $assign) {
            return '';
        }

        return (string)$this->value;
    }

    public function evaluate(): mixed
    {
        $expression = trim($this->getRaw());

        // Strip surrounding ={} if present
        $expression = ltrim($expression, '= ');
        $steps = preg_split('/->/', $expression);

        $additional = $this->parseAdditional();
        $options = array_merge($this->attributes, $additional);
        $append = false;
        $push = false;

        $value = null;
        $first = true;

        foreach ($steps as $step) {
            $step = trim($step);

            if ($step === '') continue;

            // Handle function calls or method chains
            if (preg_match('/^
                (?<var>\$?[a-zA-Z_][a-zA-Z0-9_-]*)        # Variable name, optionally starting with $ (if name itself is variable)
                (?:(?<call>\((.*?)\)))?                   # Optional function call with arguments
                (?:(?<push>\[\]))?                        # Optional array push operator ([])
                (?:(?<append>\&))?                        # Optional append operator (&)
                (?::(?<cast>[a-zA-Z_][a-zA-Z0-9_-]*))?    # Optional type cast (e.g., :text, :number)
                (?:\s*->\s*
                    (\$\.
                        (?<chain>([a-zA-Z_][a-zA-Z0-9_-]*)\(["\']?(.*?)["\']?\)*)
                    )
                )?
                \s*(?<attributes>(([a-zA-Z_][a-zA-Z0-9_-]*)\s?=\s?(.*?)))?    # Attributes or assignment (e.g., key=value)
                $/xsm', $this->raw, $m)) {
                $var = $m['var'];
                $call = $m['call'] ?? '';
                $append = isset($m['append']) && $m['append'] == '&';
                $push = isset($m['push']) && $m['push'] == '[]';
                $cast = $m['cast'] ?? 'val';
                $chain = $m['chain'] ?? '';

                if ($first) {
                    $first = false;

                    if (str_starts_with($var, '$')) {
                        $varName = substr($var, 1);
                        if ($this->hasScopeVariable($varName)) {
                            $var = $this->getScopeVariable($varName);
                        } else {
                            $var = $varName;
                        }
                    }

                    if (isset($m['call']) && !empty($m['call']) && $m['call'] != "") {
                        $paramsStr = trim($m['call'], '() ');
                        $this->params = $this->parseParameters($paramsStr ?? '');
                        $value = $this->invokeTool($var);
                    } elseif (isset($options['use']) && !empty($options['use'])) {
                        $this->params = $this->parseParameters($options['params'] ?? '');
                        $value = $this->invokeTool();
                    } else {
                        $value = $this->useGenerator($var);
                    }
                }

                // Cast value
                $castClass = $this->resolveCastClass($cast);
                if (!class_exists($castClass)) {
                    throw new \Exception("Cast type '{$cast}' is not supported.");
                }

                $obj = $castClass::make($value);

                // Apply method chain
                if (preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)\((.*?)\)/', $chain, $methods, PREG_SET_ORDER)) {
                    foreach ($methods as $method) {
                        $methodName = $method[1];
                        $args = $this->parseParameters($method[2] ?? '');
                        if (!method_exists($obj, $methodName) && !method_exists($obj, Val::PRIVATE_PREFIX. $methodName)) {
                            throw new \Exception("Method '{$methodName}' not found on " . get_class($obj));
                        }

                        $value = $obj->$methodName(...$args);
                        $obj = $value; // method may return chained object or final value
                    }
                } else {
                    $value = $obj;
                }

            } elseif (preg_match('/^@\(["\']?(.*?)["\']?\)$/', $step, $m)) {
                // handle path access: @(foo.bar.0)
                $path = $m[1];
                $segments = explode('.', $path);
                foreach ($segments as $seg) {
                    if (is_array($value) && isset($value[$seg])) {
                        $value = $value[$seg];
                    } elseif (is_object($value) && isset($value->$seg)) {
                        $value = $value->$seg;
                    } else {
                        throw new \Exception("Path segment '{$seg}' not found in object/array.");
                    }
                }
            } elseif (preg_match_all('/^(\$\.(?<chain>([a-zA-Z_][a-zA-Z0-9_-]*)\(["\']?(.*?)["\']?\)*))
                (([a-zA-Z_][a-zA-Z0-9_-]*)\s?=\s?(.*?))?$/x', $step, $m)) {
                // handle object functions: $.()
                $chain = '';
                if (isset($m['chain'])) {
                    $chain = $m['chain'][0] ?? '';
                }

                $castClass = $this->resolveCastClass($cast);
                if (!class_exists($castClass)) {
                    throw new \Exception("Cast type '{$cast}' is not supported.");
                }

                $value = $castClass::make($value);

                $segments = explode('.', $chain);
                foreach ($segments as $seg) {
                    $seg = trim($seg);
                    $method = trim(substr($seg, 0, strpos($seg, '(')));
                    if (is_object($value) && (method_exists($value, $method) || method_exists($value, Val::PRIVATE_PREFIX . $method))) {

                        $result = call_user_func_array([$value, $method], $this->parseParameters(substr($seg, strpos($seg, '(') + 1, -1)));

                        if ($result) {
                            $value = $result;
                        }
                    } else {
                        throw new \Exception("function '{$method}' not found in object.");
                    }
                }
            } elseif (preg_match('/^([a-zA-Z_][a-zA-Z0-9_-]*)\((.*?)\)$/', $step, $m)) {
                // Treat as tool or function
                $tool = $m[1];
                $params = $this->parseParameters($m[2] ?? '');
                $toolObj = \BlueFission\Parsing\Registry\FunctionRegistry::get($tool);
                if ($toolObj) {
                    $value = $toolObj->execute($params);
                } else {
                    throw new \Exception("Function/tool '{$tool}' not found.");
                }
            } else {
                // treat as final assignment
                $this->var = trim($step);
                if ($append) {
                    if (!$this->hasScopeVariable($this->var)) {
                        $this->setScopeVariable($this->var, '');
                    }

                    $originalValue = $this->getScopeVariable($this->var);
                    if (is_array($originalValue)) {
                        $value = array_merge($originalValue, [$value]);
                    } elseif (is_string($originalValue)) {
                        $value = $originalValue . $value;
                    } else {
                        throw new \Exception("Cannot append to non-array/string variable '{$this->var}'.");
                    }
                }

                if ($push) {
                    if (!$this->hasScopeVariable($this->var)) {
                        $this->setScopeVariable($this->var, []);
                    }

                    $originalValue = $this->getScopeVariable($this->var);
                    if (is_array($originalValue)) {
                        $value = array_merge($originalValue, [$value]);
                    } else {
                        throw new \Exception("Cannot push to non-array variable '{$this->var}'.");
                    }
                }

                $this->setScopeVariable($this->var, $value);
            }
        }

        if ($value instanceof \BlueFission\IVal) {
            return $value->val();
        }

        return $value;
    }

    protected function parseAdditional(): array
    {
        $raw = $this->getRaw();

        $additional = [];
        $matches = [];

        // parse for the following string features
        // toolName(arguments) -> variable
        // to see if parens exist, if they have argument content, and if there is a
        // related variable assignment.
        if (preg_match('/
            (?<tag_name>(\$)?[^.a-zA-Z_][a-zA-Z0-9_-]*)\(((?<function_args>[^)]*)\))?            # tag or variable name
            (?:\s*->\s*(?<assign_target>[a-zA-Z_][a-zA-Z0-9_-]*))?
            /x', $raw, $matches)) {
        }

        $additional['use'] = $matches['tag_name'] ?? '';
        $additional['params'] = $matches['function_args'] ?? '';
        $additional['assign'] = $matches['assign_target'] ?? '';

        return $additional;
    }

    protected function parseParameters(string $params): array
    {
        $pattern = '/(?:
            (?<param>
                "(.*?)"   # double quoted
                |
                \'(.*?)\' # single quoted
                |
                \[(.*?)\]                            # bracketed
                |
                [a-zA-Z_][a-zA-Z0-9_-]*                         # unquoted
            )([,\s])?
        )*/x';

        preg_match_all($pattern, $params, $matches, PREG_SET_ORDER);
        $args = [];

        if (!$matches)
            return [];

        foreach ($matches as $match) {
            if (!isset($match['param']) || empty($match['param'])) {
                continue;
            }
            $args[] = trim($this->resolveValue($match['param']));
        }

        return $args;
    }

    protected function invokeTool( $name = null ): mixed
    {
        [$function, $params, $assign] = array_values($this->parseAdditional());

        $function = $name ?? $this->resolveValue($function);

        if (!$function) {
            throw new \Exception("No function/tool specified for invocation.");
        }

        $tool = FunctionRegistry::get($function);
        if ($tool) {
            return $tool->execute($this->params);
        }

        return "[Unknown tool: $function]";
    }

    protected function useGenerator(string $expression): mixed
    {
        try {
            $generator = GeneratorRegistry::get();
            $generator->setDriver($this->generatorDriver);
            return $generator->generate($this);
        }
        catch (\Exception $e) {
            return "[Generation Error]";
        }
    }
}
