<?php

namespace BlueFission\Services;

use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;

/**
 * Class Request
 *
 * This class extends the Obj class and provides a mechanism for managing incoming request data.
 *
 * @package BlueFission\Services
 */
class Request extends Obj
{
    /**
     * Request constructor.
     *
     * Calls the parent constructor and sets the data property to the result of all().
     */
    public function __construct()
    {
        parent::__construct();

        $this->_data = Arr::make($this->all());
    }

    /**
     * Retrieves all request data based on the request method.
     *
     * @return array An array of request data.
     */
    public function all()
    {
        $method = Str::make($this->type())->upper();

        switch ($method->val()) {
            case 'GET':
                $request = $this->input(INPUT_GET, $_GET ?? []);
                break;
            case 'POST':
                $request = $this->input(INPUT_POST, $_POST ?? []);
                break;
            default:
                $get = Arr::make($this->input(INPUT_GET, $_GET ?? []));
                $post = Arr::make($this->input(INPUT_POST, $_POST ?? []));

                $request = $get->merge($post->val())->val();
                break;
        }

        return $request;
    }

    /**
     * Read request input with a superglobal fallback for CLI/test contexts.
     *
     * @param int $type
     * @param array $fallback
     * @return array
     */
    private function input(int $type, array $fallback = []): array
    {
        $request = filter_input_array($type);

        if (Arr::is($request)) {
            return $request;
        }

        return $fallback;
    }

    public function file($field)
    {
        $files = Arr::make($_FILES ?? []);
        $file = $files->get($field);

        if (!Val::is($file)) {
            return null;
        }

        return new Upload($file);
    }

    /**
     * Retrieves the request method.
     *
     * @return string The request method.
     */
    public function type()
    {
        return Str::make($_SERVER['REQUEST_METHOD'] ?? '_')->upper()->val();
    }

    /**
     * Overrides the default behavior for setting object properties to throw an exception.
     *
     * @param string $field The name of the field to be set.
     * @param mixed $value The value of the field.
     *
     * @throws Exception An exception is thrown when this method is called.
     */
    public function __set($field, $value): void
    {
        throw new \Exception('Request Inputs Are Immutable');
    }
}
