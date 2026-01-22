# Date Class Documentation

The `Date` class is an extension of the `Val` class within the BlueFission framework, designed to encapsulate and enhance the handling of date and time values in PHP.

## Class Overview

- **Namespace**: `BlueFission`
- **Extends**: `Val`
- **Implements**: `IVal`
- **Purpose**: To provide a structured and object-oriented way to manipulate date and time values, offering functionalities like formatting, timezone adjustments, and comparisons.

## Features

- Encapsulates PHP's `DateTime` functionalities.
- Provides utility methods for date and time manipulation and comparison.
- Allows for easy formatting and timezone adjustments.
- Incorporates event dispatching for changes in date values.

## Properties

- `$_type`: Fixed to "datetime".
- `$_format`: The format of the date. Default is ISO 8601 (`"c"`).
- `$_timezone`: The timezone of the date. Default is `"UTC"`.
- `$_datetime`: A `DateTime` object managing most date operations.

## Constructor

```php
public function __construct($value = null, $timezone = null)
```
- `$value`: The initial date value. Can be a `DateTime` object, a date string, or null for the current time.
- `$timezone`: Optional. The timezone to use. Defaults to the timezone of the provided `DateTime` object or `"UTC"` if none is specified.

## Methods

### value

```php
public function value($value = null): mixed
```
Overrides the parent `value` method to set or get the current date value formatted according to `$_format`.

### is

```php
public function is(): bool
```
Checks if the current value is a valid date.

### timestamp

```php
public function timestamp($data = null): int|null
```
Gets or sets the timestamp of the current date instance.

### time

```php
public function time(): string
```
Gets or optionally sets the time part of the current date instance. Can adjust hours, minutes, and seconds.

### format

```php
public function format(string $format = null): IVal | string
```
Gets or sets the formatting string for the date representation.

### difference

```php
public function difference($time2, $interval = null): float
```
Calculates the difference between two dates in the specified interval (e.g., seconds, days).

### date

```php
public function date(): string
```
Gets or optionally sets the date part of the current instance. Can adjust day, month, and year.

## Usage Example

```php
$date = new BlueFission\Date('2023-04-01', 'America/New_York');
echo $date->format('Y-m-d H:i:s'); // Outputs the date in the specified format

// Change the time
echo $date->time(14, 30, 0); // Sets the time to 2:30 PM and outputs the result

// Get the difference in days
$anotherDate = new BlueFission\Date('2023-04-05');
echo $date->difference($anotherDate->val(), 'days'); // Outputs the difference in days
```

# E-Commerce Example with BlueFission Date Wrapper

The `Date` class in BlueFission gives you a smarter way to handle time and date in your online store. You can create and format order dates, calculate shipping time, and handle timezones easily — all while keeping your code clean and object-oriented.

---

## 1. Create an Order Date in a Specific Timezone

```php
use BlueFission\Date;

// Customer places an order on April 1, 2023, in New York time
$orderDate = new Date('2023-04-01', 'America/New_York');

echo $orderDate->format('Y-m-d H:i:s');
// Outputs: 2023-04-01 00:00:00 (adjusted to NY time)
```

## 2. Set Delivery Time

```php
// Set delivery time to 2:30 PM on the same day
echo $orderDate->time(14, 30, 0);
// Outputs: 14:30:00
```

## 3. Compare to Expected Delivery Date

```php
$deliveryDate = new Date('2023-04-05');

// Find how many days until delivery
echo $orderDate->difference($deliveryDate->val(), 'days');
// Outputs: 4
```

## 4. Get Timestamps for Logs or Sorting

```php
echo $orderDate->timestamp();
// Outputs a UNIX timestamp like 1680307200
```

## 5. Format Dates for Emails

```php
echo $deliveryDate->format('l, F jS Y');
// Outputs: Wednesday, April 5th 2023
```

## Summary

With BlueFission’s Date class, you can:

- Set and format order dates  
- Calculate time between now and delivery  
- Handle different timezones easily  
- Send nicely formatted dates in emails or receipts  
- Log events with exact timestamps

