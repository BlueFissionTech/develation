# BlueFission Data Type Wrapper Classes

The BlueFission Data Type Wrapper Classes provide a powerful and flexible way to work with basic data types in PHP, enhancing them with additional functionalities such as event dispatching, constraints, dynamic method slotting, and more. These classes make it easier to manipulate data types while incorporating logic directly related to the data they represent.

## Overview

The BlueFission Data Type Wrapper Classes aim to enhance PHP's native data types by encapsulating them in objects that offer additional functionality. These enhancements include event handling, constraints for data integrity, dynamic method slotting for runtime extensibility, and utility methods that augment or replace PHP's native functions for more intuitive and object-oriented data manipulation.

## Purpose

The core purpose of these wrapper classes is to provide a structured, object-oriented approach to handling common data types in PHP. By wrapping data types in classes, it becomes possible to:

- **Incorporate Logic with Data**: Attach behaviors and constraints directly to the data, ensuring that the data behaves as expected in all contexts.
- **Enhance Readability and Maintenance**: Use object-oriented techniques to manipulate data types, improving code readability and maintainability.
- **Facilitate Extensibility**: Easily extend data types with custom functionalities without altering the original class definitions.
- **Centralize Common Operations**: Replace scattered implementations of common data operations with centralized, reusable methods.

## Features

- **Event Dispatching**: Respond to changes in data with custom event handlers.
- **Constraints**: Apply constraints to ensure data integrity as values change.
- **Dynamic Method Slotting**: Extend instances with new functionalities at runtime.
- **Utility Methods**: Includes a set of utility methods for common data manipulation needs.

## Class Hierarchy

- `Val`: The base class for all data types. Implements `IVal` interface.
- `Flag`: Specialized class for Boolean values.
- `Num`: Specialized class for numeric values.
- `Str`: Specialized class for string values.
- `Arr`: Specialized class for array values.

## Key Methods

### Static Methods

- `make($value)`: Factory method to create a new instance of the class with the given value.
- `grab()`: Retrieve the last value passed to any instance statically.
- `use()`: Use the last value passed to create a new instance of the class.
- `slot($name, $callable)`: Dynamically add a new method to the class.

### Instance Methods

- `val()`: Get or set the current value of the instance.
- `cast()`: Cast the internal value to the specified type.
- `constraint($callable, $priority)`: Add a new constraint to the value.
- `snapshot()`: Take a snapshot of the current value for later use.
- `reset()`: Reset the value to the last snapshot taken.

## Usage Examples

### `is`

Check if a value qualifies as a specific data type using the static method.

```php
if (Num::is(42)) {
    echo "The value is a valid number.";
}
```

### `isValid`

Determine the validity of a value according to a specific data type's rules. Note: `isValid` might inherently rely on instance-specific information (like `_type` in the `Val` class). This example conceptually demonstrates how you might use it if a static context were applicable or if extended to support such usage:

```php
if (Num::isValid(42)) {
    echo "The value is a valid number.";
}
```

### `isEmpty`

Check if a given value is considered "empty" by PHP standards, using the static method version.

```php
if (Str::isEmpty("")) {
    echo "The string is empty.";
}
```

### `isNull`

Verify if a given value is `null` using the static method.

```php
if (Val::isNull(null)) {
    echo "The value is null.";
}
```

### `grab` and `use`

Recall and instantiate the last values passed statically

```php
$value = 'Hello, World';

if (Str::is($value)) {
    echo Str::grab(); // Outputs 'Hello, World'
}
```

```php
if (Arr::is($value)) {
    echo Arr::use()->removeDuplicates()->size(); // Creates a new instance with the last value and outputs the size of the filtered array
}
```

### Usage as Invoked Variables

These wrapper classes are designed to be used directly in a similar fashion to their native PHP counterparts. Thanks to the `__invoke` magic method, instances of these classes can be invoked as if they were a function, returning or setting their value depending on the context:

```php
$number = Num::make(42);

// Using the object as an invoked variable to get its value
echo $number(); // Outputs: 42

// Setting a new value through invocation
$number(100);
echo $number(); // Outputs: 100
```

This feature allows for seamless integration of these objects into existing codebases, requiring minimal changes to how variables are accessed or modified.

## Replacing Native PHP Functions

Several native PHP functions for data manipulation are replaced or augmented by methods in these classes, providing a more intuitive and object-oriented interface.

### Example: String Manipulation

Instead of using PHP's native string manipulation functions, you can use methods provided by the `Str` class:

```php
$input = " BlueFission ";
$myString = Str::make($input)->trim()->lower();

echo $myString(); // Outputs: "bluefission"
```

Here, the `trim()` and `lower()` methods replace the need for `trim($input)` and `strtolower($input)`, respectively.

### Example: Array Operations

Similarly, for arrays, instead of PHP's array functions, you can use methods provided by the `Arr` class:

```php
$array = Arr::make([1, 2, 3, 4, 5]);

// Appending a value to the array
$array->push(6);

// Checking if the array has a specific key
if ($array->hasKey(0)) {
    echo "Key exists";
}

// Merging another array
$array->merge([7, 8, 9]);
```

### Dynamic Method Slotting

Beyond replacing native functions, the dynamic method slotting feature (`slot`) allows for runtime extension of these classes with custom functionalities:

```php
// Slotting a new method into the Str class to add a prefix
Str::slot('addPrefix', function($prefix) {
    return $prefix . $this->val();
});

$myString = Str::make("Fission");
echo $myString->addPrefix("Blue"); // Outputs: "BlueFission"
```

### Working with Boolean Values

```php
$isEven = Flag::make($row % 2);
$isEven->flip();
if ($isEven()) {
    // Do something if $isEven is true
}
```

## Advanced Functionalities

### `snapshot` and `delta`

The `snapshot` method captures the current value of the object for later comparison, while `delta` returns the change between the current value and the snapshot.

```php
$number = Num::make(10);
$number->snapshot(); // Taking a snapshot at 10
$number->val(20); // Changing the value to 20

echo $number->delta(); // Outputs the change from snapshot: 10
```

### `tag` and `grp`

`tag` allows you to categorize or group objects together, while `grp` retrieves objects by their tags, facilitating operations on sets of related objects.

```php
// Tagging numbers
$number1 = Num::make(10)->tag('prime');
$number2 = Num::make(20)->tag('prime');
$number3 = Num::make(30); // Not tagged as prime

// Retrieving all prime numbers
$primes = Num::grp('prime');

foreach ($primes as $prime) {
    echo $prime(), "\n"; // Outputs 10 and 20, but not 30
}
```

Because the `grp` method returns a `Collection`, you can perform calculations directly on the group.

```php
// Tagging numbers
$number1 = Num::make(10)->tag('prime');
$number2 = Num::make(20)->tag('prime');
$number3 = Num::make(40); // Not tagged as prime

echo Num::grp('prime')->count(); // Outputs 2

$total = 0;
Num::grp('prime')->walk(function($value, $key) use $total {
    $total += $value;
});

echo $total; // Outputs 30

Num::grp('prime')->sort(); // Sorts the instances
```

### Dynamic Method Slotting

```php
Str::slot('greet', function() {
    return "Hello, " . $this->val();
});

$myString = Str::make("World");
echo $myString->greet(); // Outputs: Hello, World
```

### Applying Constraints

```php
$myNumber = Num::make(150);
$myNumber->constraint(function(&$value) {
    $value = $value <= 100 ? $value : 100;
});
echo $myNumber->val(); // Outputs: 100
```

## Extending the Classes

Users can extend these classes to create more specialized data type wrappers or add additional functionalities specific to their applications. The design allows for easy extension while maintaining a consistent interface across different data types.

# 🛍️ E-Commerce Example with BlueFission Data Type Wrappers

This example shows how to use BlueFission's smart data wrappers (`Num`, `Str`, `Arr`, `Flag`) to power a simple eCommerce platform with clean, reusable, and dynamic logic.

---

## ✅ 1. Validate Prices on the Fly

```php
use BlueFission\Num;

// Create a price
$price = Num::make(150);

// Add a constraint: max price should be 100
$price->constraint(function (&$value) {
    $value = $value > 100 ? 100 : $value;
});

echo $price(); // Outputs: 100
```

## 🧼 2. Clean Up Product Names

```php
use BlueFission\Str;

// Format a messy product title
$productName = Str::make("  Baby Shark Plush  ");
$productName->trim()->capitalize();

echo $productName(); // Outputs: "Baby shark plush"
```

## 🛒 3. Manage a Shopping Cart with Arr

```php
use BlueFission\Arr;

$cart = Arr::make(['lego_set', 'board_game']);

// Add new item
$cart->push('stuffed_animal');

// Check if an item is in the cart
if ($cart->has('lego_set')) {
    echo "Lego is already in your cart!";
}
```

## 🏷️ 4. Tag and Group Sale Items

```php
use BlueFission\Num;

Num::make(9.99)->tag('sale');
Num::make(14.99)->tag('sale');
Num::make(29.99); // Not tagged

$saleItems = Num::grp('sale');

// Loop through sale items
foreach ($saleItems as $item) {
    echo $item(), "\n"; // 9.99, 14.99
}

// Calculate total sale price
$total = 0;
$saleItems->walk(function($value) use (&$total) {
    $total += $value;
});

echo "Total: $total"; // Outputs: Total: 24.98
```

## 🧠 5. Add Custom Methods with slot()

```php
use BlueFission\Str;

// Add a method to prefix product names
Str::slot('label', function() {
    return strtoupper($this->val()) . " - ON SALE!";
});

$label = Str::make("plush unicorn");

echo $label->label(); // Outputs: PLUSH UNICORN - ON SALE!
```

## 🎮 6. Work with Data Like a Function

```php
use BlueFission\Num;

$quantity = Num::make(2);

echo $quantity(); // Outputs: 2

$quantity(5); // Set new value
echo $quantity(); // Outputs: 5
```

## 📦 Summary

BlueFission Data Wrappers let you:

 Validate and constrain values  
 Format and clean strings  
 Build flexible shopping carts  
 Group and filter tagged data  
 Add dynamic methods at runtime  
 Use values like variables or functions

