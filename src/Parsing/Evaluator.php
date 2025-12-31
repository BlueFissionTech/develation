<?php

namespace BlueFission\Parsing;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\StandardRegistry;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Val;
use BlueFission\Obj;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;

class Evaluator implements IDispatcher
{
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    protected Element $element;
    protected string $expression;
    protected array $params;
    protected string $var;
    protected string $type;
    protected string $value = '';
    protected $generatorDriver;

    public function __construct($element = null)
    {
        if (!$element instanceof Element) {
            throw new \InvalidArgumentException("Evaluator requires an instance of Element.");
        }

        $this->__dispatchConstruct();
        $this->element = $element;
        $generator = GeneratorRegistry::get();
        if ($generator) {
            $this->echo($generator);
        }
    }

    public function setDriver($driver): void
    {
        $this->generatorDriver = $driver;
    }

    public function evaluate(string $expression = ''): mixed
    {
        // Parse assignment/cast/push/append flags, then resolve and store the result.
        $this->expression = $expression;
        $this->var = '';
        $this->type = 'val'; // Default type
        $append = false;
        $push = false;

        if ($this->match($this->expression, $m)) {
            $this->var = $m['var'] ?? $m['assignment'];
            $this->type = $m['castv'] ?? $m['casta'] ?? $this->type;
            $append = (isset($m['appendv']) && $m['appendv'] == '&') || (isset($m['appenda']) && $m['appenda'] == '&');
            $push = (isset($m['pushv']) && $m['pushv'] == '[]') || (isset($m['pusha']) && $m['pusha'] == '[]');
        }

        if (strpos($this->var, '$') === 0) {
            $var = substr($this->var, 1);

            $this->var = $this->element->resolveValue($var);
        }

        $value = $this->process();

        $this->value = $value ?? '';

        if ($append) {
            if (!$this->element->hasScopeVariable($this->var)) {
                $this->element->setScopeVariable($this->var, '');
            }

            $originalValue = $this->element->getScopeVariable($this->var);
            if (is_array($originalValue)) {
                $value = array_merge($originalValue, [$value]);
            } elseif (is_string($originalValue)) {
                $value = $originalValue . $value;
            } else {
                throw new \Exception("Cannot append to non-array/string variable '{$this->var}'.");
            }
        }

        if ($push) {
            if (!$this->element->hasScopeVariable($this->var)) {
                $this->element->setScopeVariable($this->var, []);
            }

            $originalValue = $this->element->getScopeVariable($this->var);
            if (is_array($originalValue)) {
                $value = array_merge($originalValue, [$value]);
            } else {
                throw new \Exception("Cannot push to non-array variable '{$this->var}'.");
            }
        }

        $this->element->setScopeVariable($this->var, $value);

        return $this->value;
    }

    public function process(): mixed
    {
        $expression = trim($this->expression);

        // Strip surrounding ={} if present
        $expression = ltrim($expression, '= ');
        $steps = preg_split('/->/', $expression);

        // Merge element attributes with inline call options so extensions can override behavior.
        $additional = $this->parseAdditional();
        $options = array_merge($this->element->getAttributes(), $additional);
        $append = false;
        $push = false;

        $value = null;
        $first = true;

        foreach ($steps as $step) {
            $step = trim($step);

            if ($step === '') continue;

            // Handle function calls or method chains across the pipeline.
            if ($this->match($this->expression, $m)) {
                $var = $m['var'] ?: $m['assignment'] ?? '';
                $call = $m['call'] ?? '';
                $args = $m['arguments'] ?? '';
                $append = (isset($m['appendv']) && $m['appendv'] == '&') || (isset($m['appenda']) && $m['appenda'] == '&');
                $push = (isset($m['pushv']) && $m['pushv'] == '[]') || (isset($m['pusha']) && $m['pusha'] == '[]');
                $cast = $m['castv'] ?? $m['casta'] ?? 'val';
                $chain = $m['chain'] ?? '';
                $attribs = $m['attributes'] ?? '';

                if ($first) {
                    $first = false;

                    if (str_starts_with($var, '$')) {
                        $varName = substr($var, 1);
                        if ($this->element->hasScopeVariable($varName)) {
                            $var = $this->element->getScopeVariable($varName);
                        } else {
                            $var = $varName;
                        }
                    }

                    $this->var = $var;

                    if (isset($call) && !empty($call) && $call != "") {
                        
                        // Split chained calls while keeping argument groups.
                        preg_match_all('/((?<call>[a-zA-Z_][a-zA-Z0-9_-]*(\((?<arguments>[^)]*)\))?))/', $call, $callMatch);

                        $callChain = $callMatch['call'] ?? [];
                        $argChain = $callMatch['arguments'] ?? [];

                        $result = null;
                        if (count($callChain) > 1) {
                            $result = array_shift($callChain);
                            $args = array_shift($argChain);

                            if (strpos($result, '(') !== false) {
                                $this->params = $this->parseParameters($args);
                                $tool = preg_replace('/\([^\)]*\)/', '', $result);
                                $value = $this->invokeTool($tool);
                                continue;
                            }

                            // Standard registry enables predefined call targets without user lookup.
                            $result = StandardRegistry::get($result);
                        } elseif ($call != '') {
                            // Executable element tags run for side effects (no direct output here).
                            $function = preg_replace('/\([^\)]*\)/', '', $call);
                            $tag = TagRegistry::get($function);
                            if ($tag && is_subclass_of($tag->class, IExecutableElement::class) ) {
                                $capture = '';
                                $attributes = $this->parseParameters($attribs);
                                $elementClass = $tag->class;
                                $element = new $elementClass($function, $capture, '', $attributes);
                                $element->execute();
                            }
                        } else {
                            $this->params = $this->parseParameters($args);
                            $value = $this->invokeTool($var);
                            continue;
                        }

                        // Continue method/handler chaining on the prior result.
                        for ($i = 0; $i < count($callChain); $i++) {
                            $part = $callChain[$i];
                            $args = $argChain[$i] ?? '';

                            if (strpos($part, '(') !== false) {
                                $part = preg_replace('/\([^\)]*\)/', '', $part);
                            }

                            $test = $this->parseParameters($args);

                            $result = $this->call($result ?? $var, $part, $this->parseParameters($args));
                        }

                        $value = $result;
                    } elseif (isset($options['use']) && !empty($options['use'])) {
                        $this->params = $this->parseParameters($options['params'] ?? '');
                        $value = $this->invokeTool();
                    } else {
                        $value = $this->useGenerator($var);
                    }
                }

                // Cast value into an expected type wrapper for chainable methods.
                $castClass = $this->element->resolveCastClass($cast);
                if (!class_exists($castClass)) {
                    throw new \Exception("Cast type '{$cast}' is not supported.");
                }

                $obj = $castClass::make($value);

                // Apply method chain (dot calls) on the cast object.
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

                $castClass = $this->element->resolveCastClass($cast);
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
                // Treat as final assignment or terminal variable name.
                $this->var = trim($step);
                if ($append) {
                    if (!$this->element->hasScopeVariable($this->var)) {
                        $this->element->setScopeVariable($this->var, '');
                    }

                    $originalValue = $this->element->getScopeVariable($this->var);
                    if (is_array($originalValue)) {
                        $value = array_merge($originalValue, [$value]);
                    } elseif (is_string($originalValue)) {
                        $value = $originalValue . $value;
                    } else {
                        throw new \Exception("Cannot append to non-array/string variable '{$this->var}'.");
                    }
                }

                if ($push) {
                    if (!$this->element->hasScopeVariable($this->var)) {
                        $this->element->setScopeVariable($this->var, []);
                    }

                    $originalValue = $this->element->getScopeVariable($this->var);
                    if (is_array($originalValue)) {
                        $value = array_merge($originalValue, [$value]);
                    } else {
                        throw new \Exception("Cannot push to non-array variable '{$this->var}'.");
                    }
                }

                $this->element->setScopeVariable($this->var, $value);
            }
        }

        if ($value instanceof \BlueFission\IVal) {
            return $value->val();
        }

        return $value;
    }

    protected function match($expression, &$matches): bool
    {
        if (preg_match('/^
            (
                (?<var>\$?[a-zA-Z_][a-zA-Z0-9_-]*)        # Variable name, optionally starting with $ (if name itself is variable)
                (?:(?<pushv>\[\]))?                        # Optional array push operator ([])
                (?:(?<appendv>\&))?                        # Optional append operator (&)
                (?::(?<castv>[a-zA-Z_][a-zA-Z0-9_-]*))?    # Optional type cast (e.g., :text, :number)
                |
                (?:(?<call>\@?\$?[a-zA-Z_][\.a-zA-Z0-9_-]*\((.*?)\)))?                   # Optional function call with arguments
            )
            (?:\s*->\s*
                ((\$\.
                    (?<chain>([a-zA-Z_][a-zA-Z0-9_-]*)\(["\']?(.*?)["\']?\)*)
                ) |
                (
                    (?<assignment>\$?[a-zA-Z_][a-zA-Z0-9_-]*)        # Variable name, optionally starting with $ (if name itself is variable)
                    (?:(?<pusha>\[\]))?                        # Optional array push operator ([])
                    (?:(?<appenda>\&))?                        # Optional append operator (&)
                    (?::(?<casta>[a-zA-Z_][a-zA-Z0-9_-]*))?    # Optional type cast (e.g., :text, :number)
                    )
                )
            )?
            \s*(?<attributes>(([a-zA-Z_][a-zA-Z0-9_-]*)\s?=\s?(.*?)))?    # Attributes or assignment (e.g., key=value)
            $/xsm', $this->expression, $matches)) {
            return true;
        }

        return false;
    }

    protected function parseAdditional(): array
    {
        $expression = $this->expression;

        $additional = [];
        $matches = [];

        // parse for the following string features
        // toolName(arguments) -> variable
        // to see if parens exist, if they have argument content, and if there is a
        // related variable assignment.
        if (preg_match('/
            (?<tag_name>(\$)?[^.a-zA-Z_][a-zA-Z0-9_-]*)\(((?<function_args>[^)]*)\))?            # tag or variable name
            (?:\s*->\s*(?<assign_target>[a-zA-Z_][a-zA-Z0-9_-]*))?
            /x', $expression, $matches)) {
        }

        $additional['use'] = $matches['tag_name'] ?? '';
        $additional['params'] = $matches['function_args'] ?? '';
        $additional['assign'] = $matches['assign_target'] ?? '';

        return $additional;
    }

    protected function parseParameters(string $params): array
    {
        // Parse loosely-typed arguments; resolved values can be vars, strings, or bracketed values.
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
            if (!isset($match['param']) || trim($match['param']) === '') {
                continue;
            }
            $arg = trim($this->element->resolveValue($match['param']));
            $arg = (empty($arg)) ? null : $arg;

            $args[] = $arg;
        }

        return $args;
    }

    protected function invokeTool( $name = null ): mixed
    {
        // Allow tag or attribute-based tool invocation with optional assignment.
        [$function, $params, $assign] = array_values($this->parseAdditional());

        $function = $name ?? $this->element->resolveValue($function);

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
        // Generators are used when a variable is a content-producing tag.
        try {
            $generator = GeneratorRegistry::get();
            $generator->setDriver($this->generatorDriver);
            return $generator->generate($this->element);
        }
        catch (\Exception $e) {
            return "[Generation Error]";
        }
    }

    protected function call($classOrObject, $method, $args = []): mixed
    {
        // Handle both class static calls and instance method calls.
        if (is_string($classOrObject)) {
            if (!class_exists($classOrObject)) {
                throw new \Exception("Class '{$classOrObject}' does not exist.");
            }
            $reflectionMethod = new \ReflectionMethod($classOrObject, $method);
            if ($reflectionMethod->isStatic()) {
                return $classOrObject::$method(...$args);
            } else {
                $obj = new $classOrObject();
                if (!$obj) {
                    throw new \Exception("Failed to instantiate class '{$classOrObject}'.");
                }
                if (!method_exists($obj, $method) && !method_exists($obj, Val::PRIVATE_PREFIX . $method)) {
                    throw new \Exception("Method '{$method}' not found on " . get_class($obj));
                }
                return $obj->$method(...$args);
            }
        } elseif (is_object($classOrObject)) {
            $obj = $classOrObject;
        } else {
            throw new \Exception("Invalid class or object provided for method call.");
        }

        if (!method_exists($obj, $method) && !method_exists($obj, Val::PRIVATE_PREFIX . $method)) {
            throw new \Exception("Method '{$method}' not found on " . get_class($obj));
        }

        return $obj->$method(...$args);
    }
}
