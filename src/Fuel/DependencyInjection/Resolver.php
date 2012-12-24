<?php

namespace Fuel\DependencyInjection;

abstract class Resolver
{
	/**
	 * @var  boolean  $allowSIngleton  wether to allow singletons
	 */
	protected $allowSingleton = true;

	/**
	 * @var  boolean  $preferSingleton  wether to prefer singletons
	 */
	protected $preferSingleton = false;

	/**
	 * @var  Fuel\DependencyInjection\Container  $container  container
	 */
	protected $container;

	/**
	 * @var  string  $root  resolver root
	 */
	public $root;

	/**
	 * @var  array  $methodInjections  method injections
	 */
	protected $methodInjections = array();

	/**
	 * @var  boolean  $resolveNamedParams  wether to resolve named parameters
	 */
	protected $resolveNamedParams = false;

	/**
	 * Sets the Container
	 *
	 * @param   Fuel\DependencyInjection\Container  $container  container
	 * @return  $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Sets the identifier root
	 *
	 * @param   string  $root  root
	 * @return  $this
	 */
	public function setRoot($root)
	{
		$this->root = $root;

		return $this;
	}

	/**
	 * Allow or disallow singletons
	 *
	 * @param   boolean  $allow  wether to allow singletons
	 * @return  $this
	 */
	public function allowSingleton($allow = true)
	{
		$this->allowSingleton = $allow;

		return $this;
	}

	/**
	 * Turn parameter name resolving on or off.
	 *
	 * @param   boolean  $allow  wether to allow named parameter resolving
	 * @return  $this
	 */
	public function allowNamedParams($allow = true)
	{
		$this->allowNamedParams = $allow;

		return $this;
	}


	/**
	 * Set wether singletons are prefered
	 *
	 * @param   boolean  $prefer  wether to prefer singletons
	 * @return  $this
	 */
	public function preferSingleton($prefer = true)
	{
		$this->preferSingleton = $prefer;

		return $this;
	}

	/**
	 * Prepare the identifiers based on singleton preference
	 *
	 * @param   string  $identifier  identifier
	 * @param   string  $name        instance name
	 * @return  void
	 */
	protected function prepareIdentifiers($identifier, &$name)
	{
		if ( ! $this->allowSingleton and $identifier === $name)
		{
			throw new ResolveException($identifier.' does not allow singleton usage.');
		}

		if ($name === null)
		{
			$name = $this->preferSingleton ? $identifier : false;
		}
	}

	/**
	 * Resolve class dependencies.
	 *
	 * @param   string  $class  class name
	 * @return  object  resolved object with injected dependencies
	 */
	protected function resolveDependencies($class)
	{
		$reflection = new \ReflectionClass($class);
		$constructor = $reflection->getConstructor();

		if ( ! $constructor)
		{
			// In this case there are no dependencies to inject
			// So we can just return a new instance
			return $reflection->newInstance();
		}

		$resolver = array($this, 'resolveParameter');
		$params = array_map($resolver, $constructor->getParameters());

		return $reflection->newInstanceArgs($params);
	}

	/**
	 * Resolve a reflection parameter depencency
	 *
	 * @param   ReflectionParameter  $param  relfection parameter
	 * @return  object               resolved dependency
	 * @throws  ResolveException
	 */
	public function resolveParameter($param)
	{
		$identifier = $param->getName();
		$name = null;

		// Resolve param injection alias
		if (isset($this->paramInjections[$identifier]))
		{
			list($identifier, $name) = $this->paramInjections[$identifier];
		}

		try
		{
			return $this->container->resolve($identifier, $name);
		}
		catch (ResolveException $e)
		{
			// Let this exception fly, there are
			// other ways to retrieve the dependency
		}

		$class = $param->getClass();

		if ( ! $class)
		{
			if ($param->isDefaultValueAvailable())
			{
				return $param->getDefaultValue();
			}

			$class = $param->getDeclaringClass()->getName();

			throw new ResolveException('Could not resolve parameter '.$param->getName().' for '.$class);
		}

		try
		{
			return $this->container->resolve($class->getName());
		}
		catch(ResolveException $e)
		{
			if ($param->isDefaultValueAvailable())
			{
				return $param->getDefaultValue();
			}

			throw $e;
		}
	}

	/**
	 * Ensure an an object instance.
	 *
	 * @param   mixed   $result  forge result
	 * @return  object  resolved dependency
	 * @throws  ResolveException
	 */
	protected function ensureInstance($result)
	{
		if (is_callable($result))
		{
			$result = call_user_func($result, $this->container);
		}

		if (is_object($result))
		{
			return $result;
		}

		if ( ! is_string($result)  or ! class_exists($result, true))
		{
			throw new ResolveException('Resolving returned unexpected output: '.print_r($result, true));
		}

		return $this->resolveDependencies($result);
	}

	/**
	 * Resolve a dependency
	 *
	 * @param   string  $identifier  identifier
	 * @param   string  $name        instance name
	 * @return  object  resolved dependency
	 * @throws  Fuel\DependencyInjection\ResolveException
	 */
	public function resolve($identifier, $name)
	{
		// Get singleton preferences.
		$this->prepareIdentifiers($identifier, $name);

		// Strip the root from the identifier
		$suffix = substr($identifier, strlen($this->root));

		if ($suffix)
		{
			$suffix = trim($suffix, '.');
		}

		$result = $this->forge($this->container, $suffix ?: null);

		// Forge can return a string, in which case we'll need
		// to convert that into a class.
		$result = $this->ensureInstance($result);

		if ($name)
		{
			// Cache named instances
			$this->container->inject($identifier, $result, $name);
		}

		foreach ($this->methodInjections as $method => $injection)
		{
			list($identifier, $name) = $injection;
			$dependency = $this->container->resolve($identifier, $name);

			$result->{$method}($dependency);
		}

		return $result;
	}

	/**
	 * Add an injector method.
	 *
	 * @param   string  $method      injection method
	 * @param   string  $identifier  dependency identifier
	 * @param   string  $name        instance name
	 * @return  $this
	 */
	public function methodInjection($method, $identifier, $name = null)
	{
		$this->methodInjections[$method] = array($identifier, $name);

		return $this;
	}

	/**
	 * Add an named parameter injection.
	 *
	 * @param   string  $method      injection method
	 * @param   string  $identifier  dependency identifier
	 * @param   string  $name        instance name
	 * @return  $this
	 */
	public function paramInjection($param, $identifier, $name = null)
	{
		$this->paramInjections[$param] = array($identifier, $name);

		return $this;
	}
}