# Obj Class Documentation

The `Obj` class is part of the BlueFission framework, designed to encapsulate complex data structures and provide an object-oriented interface for manipulating such data. It implements the `IObj` interface, extending the `Val` class's functionality to handle complex and structured data types. It's designed to handle complex data types through an object-oriented interface. It allows for dynamic data fields and incorporates event-driven programming for reactive and flexible data handling.

## Class Overview

- **Namespace**: `BlueFission`
- **Implements**: `IObj`
- **Purpose**: To provide an advanced structure for handling complex object types with flexibility and extensibility, including features such as dynamic fields, constraints, and serialization.
- **Key Feature**: Dynamic field management with event dispatching for data changes.


## Features

- **Dynamic Fields**: Fields can be dynamically added, accessed, and modified using object attributes.
- **Event Dispatching**: Leverages the `Dispatches` trait for event handling, allowing objects to react to and broadcast events on data changes.
- **Data Type Flexibility**: Supports flexible data types for fields, with the capability to lock data types if needed.
- **Serialization Support**: Provides methods for serializing object data to arrays or JSON strings.

## Key Properties

- `_data`: An instance of `Arr` that stores the object's fields and their values.
- `_types`: An array defining the types for the dynamic fields.
- `_type`: The class name, used primarily for identification.
- `_exposeValueObject`: Determines whether to expose the value object directly or its value.
- `_lockDataType`: Locks the data type of fields to prevent type changes.

## Constructor

```php
public function __construct()
```
Initializes a new instance of the `Obj` class, setting up the data storage and configuring event dispatching for changes.

## Methods

### field

```php
public function field(string $field, $value = null): mixed
```
Sets or gets the value of a specific field by name. If a value is provided, it sets the value; otherwise, it returns the current value of the field.

### clear

```php
public function clear(): IObj
```
Clears all fields in the object, resetting them to their default state.

### assign

```php
public function assign(mixed $data): IObj
```
Assigns a set of values to the object's fields in bulk, either from an associative array or another object.

### exposeValueObject

```php
public function exposeValueObject(bool $expose = true): IObj
```
Configures the object to either expose the underlying `IVal` instances directly or just their values when accessing fields.

### constraint

```php
public function constraint($callable): IObj
```
Adds a constraint function to the object's fields, allowing for validation or transformation of data on assignment.

### Serialization and JSON Representation

- `toArray()`: Converts the object data into an associative array.
- `toJson()`: Converts the object data into a JSON string.
- `serialize() / unserialize($data)`: Supports PHP's serialization mechanisms.

## Usage Example

```php
$obj = new BlueFission\Obj();
$obj->field('name', 'John Doe');
$obj->field('age', 30);

// Bulk assign
$obj->assign(['name' => 'Jane Doe', 'age' => 32]);

echo $obj->field('name'); // Outputs: Jane Doe

// Convert to JSON
echo $obj->toJson();
```

Fields within an `Obj` instance can be accessed and modified as object attributes, providing a clean and intuitive interface for data manipulation.

### Setting and Getting Fields

```php
$obj = new BlueFission\Obj();
$obj->name = 'John Doe'; // Sets the field 'name'
echo $obj->name; // Gets the value of the field 'name'
```

This approach is equivalent to calling the `field` method directly:

```php
$obj->field('name', 'John Doe');
echo $obj->field('name');
```

### Event Dispatching

The `Obj` class can dispatch and respond to events, thanks to the `Dispatches` trait. This feature allows for complex interactions within and between objects, based on changes to their state or other triggers.

```php
$obj->behavior('customEvent', function($args) {
    echo "Custom event triggered with args: " . json_encode($args);
});

// Triggering the custom event
$obj->dispatch('customEvent', ['key' => 'value']);
```

### Serialization and Representation

`Obj` provides methods for converting the object data into various formats for storage or transmission.

```php
// Converting object data to an array
$arrayData = $obj->toArray();

// Converting object data to a JSON string
$jsonData = $obj->toJson();
```

### Handling Data Types and Constraints

The class allows for data types of fields to be specified and enforced, ensuring that data integrity is maintained.

```php
// Assuming a field 'age' should only accept numeric values
$obj->field('age')->constraint(function($value) {
    if (!is_numeric($value)) {
        throw new InvalidArgumentException("Age must be numeric.");
    }
});
$obj->age = 30; // Valid
$obj->age = 'thirty'; // Throws exception
```

### Extending Functionality

The `Obj` class can be extended to add custom functionality or behavior, making it a versatile base class for more specialized object types.

```php
class CustomObj extends BlueFission\Obj {

    protected $_data = [
        'customField1' => null,
        'customField2' => null,
        'customField3' => 'value'
    ];

    protected $_types = [
        'customField1' => BlueFission\DataTypes::STRING
        'customField2' => BlueFission\DataTypes::INTEGER
        'customField3' => BlueFission\DataTypes::STRING
    
    ];

    public function customMethod() {
        // Custom logic here
    }
}
```

#### The `$_data` Property

The `$_data` property defines the initial fields of the object and their default values. It can be overridden in subclasses to provide a custom set of fields.

```php
class Person extends \BlueFission\Obj {
    protected $_data = [
        'name' => null,
        'age' => null
    ];
}

$obj = new Person();
$obj->name = 'John Doe';
$obj->age = 30;
```

#### The `$_types` Property

The `$_types` property defines the data types for the fields, ensuring that the fields adhere to specific types. This property can be customized in subclasses to enforce stricter data type checks. When setting types for fields, those fields are then cast as `BlueFission\Val` [datatypes](datatypes.md) allowing their features and methods to be available to the field. Whether or not the field is locked to a datatype can be set with the `_lockDataType` property. Further, the `exposeValueObject` method can be used to expose the `Val` object directly, or just the value of the object.

```php
class Person extends \BlueFission\Obj {
    protected $_data = [
        'name' => null,
        'age' => null
    ];

    protected $_types = [
        'name' => \BlueFission\DataTypes::STRING,
        'age' => \BlueFission\DataTypes::INTEGER
    ];

    protected $_lockDataType = true;

    protected $_exposeValueObject = true;
}

$obj = new Person();

$obj->name = 'john doe';

$obj->name->capitalize(); // 'John Doe'

$obj->age = 'thirty'; // Throws exception
```

### Using Events

Events can be used to trigger actions based on changes to the object's state. The `Dispatches` trait provides a simple way to manage event-driven programming within the object.

```php
use BlueFission\Behavioral\Behaviors\Meta;

class Person extends \BlueFission\Obj {
    public function __construct() {
        parent::__construct();
        $this->when('onAgeChange', function($behavior, $meta) {
            echo "Age changed to: " . $meta->data;
        });
    }

    public function setAge($age) {
        $this->age = $age;
        $this->dispatch('onAgeChange', new Meta(data: $age));
    }
}

$obj = new Person();

$obj->setAge(30); // Outputs: Age changed to: 30
```

This can also be accomplished directly through typed fields.

```php
class Person extends \BlueFission\Obj {
    protected $_data = [
        'name' => null,
        'age' => null
    ];

    protected $_types = [
        'name' => \BlueFission\DataTypes::STRING,
        'age' => \BlueFission\DataTypes::INTEGER
    ];

    protected $_lockDataType = true;

    protected $_exposeValueObject = true;

    public function __construct() {
        parent::__construct();
        $this->when(Event::CHANGE, function() {
            if
            echo "My values have changed";
        });
    }
}

$obj = new Person();

$obj->name = 'Jane Doe'; // Outputs: My values have changed
```

