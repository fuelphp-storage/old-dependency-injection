# Dependency Injection

#### Table of contents:

* Container
* Provider
* Entry

#### Exception

* Exception
* RevolveException


## Container

Contains dependencies. Responsible for registering and looking up entries and entry providers.

##### Methods:

* findResolver
* resolve
* resolveSingleton
* register
* registerSingleton
* registerProvider
* remove
* removeProvider


## Provider

A Provider provides a Container with dependencies. Once registered it injects entries into the Container and is available for dynamic resolving.

Providers claim a base of which they resolve dependencies for dynamic resolving. 

##### Methods:

* resolve
* resolveSingleton
* provide

## Entry

An Entry is the wrapper around an instance factory. An Entry can block or force singleton usage. By default every resolved instance will be a newly created instance.









