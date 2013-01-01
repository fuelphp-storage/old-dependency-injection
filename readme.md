# Dependency Injection

[![Build Status](https://travis-ci.org/fuelphp/dependency-injection.png?branch=master)](https://travis-ci.org/fuelphp/dependency-injection)

Simple yet powerful dependency injection package. The package provides an
easy way to define and resolve dependencies. Multiple resolver types and
injection methods are provider to suit you in many different use-cases.

## Creating a container

```
$container = new Fuel\DependencyInjection\Container;
```

## Register a dependency resolver

```
$container->register('dependency.identifier', 'MyClass');
// Register as string class name, this will return
// a new instance of MyClass

$container->register('dependency.identifier', function($container) {
	return new MyClass($container->resolve('sub.dependency'));
});
// Register using a callback, this allows you to manually inject
// dependencies into newly generated instances.
```

## Resolve a dependency

```
try
{
	$dep = $container->resolve('dependency.identifier');
}
catch (Fuel\DepencencyInjection\ResolverException $e)
{
	// The dependency was not resolved
}
```

## Named instances / Multitons

It's also possible to store named instances to give
you a multiton implementation.

```
$first = $container->resolve('dependency.identifier', 'name');
// First resolved instance

$different = $container->resolve('dependency.identifier', 'other');
// This object is NOT the same as $first
$same_as_first = $container->resolve('dependency.identifier', 'name');
// This object IS the same as $first
```

## Singletons

This package holds one naming convention and that is: when your name is
identical to the dependency identifier, you've got a singleton. To make
it a little easier there is a helper method for easy access.

```
$singleton = $container->singleton('dependency.identifier');
```

## Dependency configuration

There are a couple way to influence the container while resolving
dependencies. One if which, is the option to prefer singletons over new
instances. Container entries can be configured during registration.

```
$container->register('new.dependency', 'SomeClass', function ($entry) {
	$entry->preferSingleton();
});

$singleton = $container->resolve('new.dependency');
```

_By default singletons are not preferred_

In some cases a singleton is easy for passing around data of provide
easy access to a global entity. The consumer of a package should not
have to worry about what the best thing would be.

It's also possible to allow and block singleton use:

```
$container->register('new.dependency', 'SomeClass', function ($entry) {
	$entry->allowSingleton();
	// Allow it, is default

	$entry->blockSingleton();
	// Block it
});

$singleton = $container->resolve('new.dependency');
// This will raise an exception
```

## Dependency results

When resolving a dependency, 3 types can be returned:

* (string) class name
* (object) resolved instance
* (closure) returning one of the above

## Injecting Dependencies

In some cases, like with testing, it's needed to inject dependencies right into container without the interference of a resolver. When no name is provided a singleton is presumed.

```
$container->inject('singleton.dep', $instance);
// Inject singleton

$container->inject('named.dep', $instance, 'named');
// Inject named dependency
```

## Dependency Providers

In order for packages to be easily available through the Container a package can create a Provider. The Provider is responsible for adding dependencies to the container and resolving of new instances.

Where normal entries have a identifier that must be merged fully, the Providers match the root of an identifier. For example, if you register a Provider that resolves anything that's prefixed with `my.root`, things like `my.root.one` and `my.root.two` will both match and be resolved by that provider.

Do not that cache and normal entries are preferred over Provider resolving as it is a more expensive process.

## Automated Dependency Injection

When resolving dependencies the container has a couple of ways to automatically inject dependencies into the resolved objects. We do this using:

* named parameters,
* method injection,
* param aliases,
* or class hinting.

### Named params… wait what?

Yes and this is not python. Through the power or the super handy reflection classes it's possible to define automatically detect dependencies by sniffing parameter names. Named parameters are only available when the return type is a string class name.

```
class Something {}

class Depending
{
	protected $dependancy;

	public function __construct($myDependency)
	{
		$this->dependency = $myDependency;
	}
}

$container->register('myDependency', 'Something');

$depending = $container->resolve('Depending');
```

The code above will successfully inject an instance of `Something` into the `Depending` class.

### Method injection

Another way of injection dependencies is though setters. To do this, list the methods that need to be called in the entry configuration:


```
$container->register('user', 'Model\User', function($entry) {
	$entry->methodInjection('addStorage', '');
});
```

### Class hinting

Class hinting is normally used to force a particular type of value to be passed to a function. Once again, though the power of reflection this is something we can take into account.

```
class Something {}

class Depending
{
	protected $dep;

	public function __construct(Something $dep)
	{
		$this->dep = $dep;
	}
}

$depending = $resolving->resolve('Depending');
```

### Param aliasing

To compliment named parameters it's possible to define param aliases. This converts the the reference before looking it up. This is handy when you've want to reference a nested dependency.

```
class Depending
{
	public $dep;

	public function __construct($paramAlias)
	{
		$this->dep = $paramAlias;
	}
}

$container->register('del', 'Depending', function($entry) {
	$entry->aliasParam('paramAlias', 'stdClass');
	// Inject a stdClass into Depending
});
```