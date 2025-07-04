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
class Request extends Obj {
	
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
		switch($this->type())
		{
			case 'GET':
				$request = filter_input_array(INPUT_GET);
				break;
			case 'POST':
				$request = filter_input_array(INPUT_POST);
				break;
			case 'PUT':
				$putData = file_get_contents("php://input");
				parse_str($putData, $myVars);
				$request = json_decode($putData, true);
				break;
			default:
				// $request = filter_input_array(INPUT_REQUEST); // Awaiting implmentation
				$get = filter_input_array(INPUT_GET) ?? [];
				$post = filter_input_array(INPUT_POST) ?? [];
				
				$request = array_merge($get, $post);
				break;
		}

		return $request;
	}

	/**
	 * Retrieves the value of a specific field from the request data.
	 *
	 * @param string $field The name of the field to retrieve.
	 * @param mixed $default The default value to return if the field is not set.
	 *
	 * @return mixed The value of the field or the default value if the field is not set.
	 */
	public function get($field, $default = null)
	{
		if ( isset($this->_data[$field]) ) {
			return $this->_data[$field];
		}

		return $default;
	}

	/**
	 * Retrieves the value of a specific field from the request data, or returns null if the field is not set.
	 *
	 * @param string $field The name of the field to retrieve.
	 *
	 * @return mixed|null The value of the field or null if the field is not set.
	 */
	public function file($field)
	{
		$file = $_FILES[$field] ?? null;
		if ( !$file ) {
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