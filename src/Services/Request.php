<?php

namespace BlueFission\Services;

use BlueFission\Obj;

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

        $this->_data = $this->all();
    }

    /**
     * Retrieves all request data based on the request method.
     *
     * @return array An array of request data.
     */
    public function all()
    {
        switch ($this->type()) {
            case 'GET':
                $request = filter_input_array(INPUT_GET);
                if ($request === null || $request === false) {
                    $request = $_GET ?? [];
                }
                break;
            case 'POST':
                $request = filter_input_array(INPUT_POST);
                if ($request === null || $request === false) {
                    $request = $_POST ?? [];
                }
                break;
            default:
                // $request = filter_input_array(INPUT_REQUEST); // Awaiting implmentation
                $get = filter_input_array(INPUT_GET) ?? [];
                $post = filter_input_array(INPUT_POST) ?? [];

                if ($get === [] && !empty($_GET)) {
                    $get = $_GET;
                }
                if ($post === [] && !empty($_POST)) {
                    $post = $_POST;
                }

                $request = array_merge($get, $post);
                break;
        }

        return $request;
    }

    public function file($field)
    {
        $file = $_FILES[$field] ?? null;
        if (!$file) {
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
        return $_SERVER['REQUEST_METHOD'] ?? '_';
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
