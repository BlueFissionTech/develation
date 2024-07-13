# Collection Classes Documentation

The `BlueFission\Collections` namespace contains several classes that implement the collection pattern, providing structured management of grouped objects. This document covers the primary classes and interfaces in this namespace, including `Collection`, `Hierarchical`, and `Group`.

## Collection

The `Collection` class provides a flexible way to manage an array of items. It implements `ICollection`, `ArrayAccess`, and `IteratorAggregate`, making it versatile for various use cases.

### Methods

- **add($object, $key)**: Adds an object to the collection at the specified key.
- **get($key)**: Retrieves the object at the specified key.
- **remove($key)**: Removes the object at the specified key.
- **has($key)**: Checks if a key exists in the collection.
- **count()**: Returns the number of items in the collection.
- **toArray()**: Converts the collection to an array.
- **clear()**: Removes all items from the collection.
- **first()**: Returns the first item in the collection.
- **last()**: Returns the last item in the collection.

### Usage Scenario

`Collection` is ideal for scenarios where you need to maintain a list of objects or values, such as managing a list of subscribers in an application or storing a set of configurations.

## Hierarchical

The `Hierarchical` class extends `Collection` to handle data in a hierarchical structure like a tree. Each node in the hierarchy can have a parent and multiple children.

### Methods

- **add($object, $label)**: Adds a child object under a specific label.
- **label($label = null)**: Gets or sets the label of the current node.
- **parent($parent = null)**: Gets or sets the parent of the current node.
- **path()**: Returns the path from the root to the current node.

### Usage Scenario

`Hierarchical` is useful for representing and manipulating hierarchical data, such as organizational structures, category trees in an e-commerce system, or nested menus in a web application.

## Group

The `Group` class is a specialized version of `Collection` that can enforce a specific type for its elements, ensuring that all objects in the group are of a uniform type.

### Methods

- **type($type = null)**: Get or set the type of objects that the group should contain.
- **get($key)**: Overridden to return objects cast to the specified type.
- **first()**: Overridden to return the first object, cast to the specified type.
- **last()**: Overridden to return the last object, cast to the specified type.

### Usage Scenario

`Group` is suited for scenarios where uniformity of the stored objects is crucial. For example, if you are developing a system where you need to manage groups of users, products, or other entities where all members of the group must be instances of a specific class.

## ICollection Interface

The `ICollection` interface defines the essential methods that any collection should implement, ensuring consistency across different types of collections.

### Methods

- **contents()**: Returns all objects in the collection.
- **add($object, $label = null)**: Adds an object to the collection.
- **has($label)**: Checks if the collection has an object with the specified label.
- **get($label)**: Retrieves an object by its label.
- **remove($label)**: Removes an object by its label.