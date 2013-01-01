<?php

namespace FuelPHP\DependencyInjection;

class Container
{
	/**
	 * @var  array  $instances  named intances
	 */
	protected $instances = array();

	/**
	 * @var  array  $entries  entries
	 */
	protected $entries = array();

	/**
	 * @var  array  $providers  providers
	 */
	protected $providers = array();

	/**
	 * Retrieve a dependency
	 *
	 * @param   string  $identifier  dependency identifier
	 * @param   string  $name        optional reference name
	 * @return  mixed   resolved dependency
	 * @throws  FuelPHP\DependencyInjection\ResolveException
	 */
	public function resolve($identifier, $name = null)
	{
		// Return any previously named instances
		if (func_num_args() > 1 and $name !== false and $cached = $this->findCached($identifier, $name))
		{
			return $cached;
		}

		// Try to resolve from a local entries
		$dependency = $this->resolveEntry($identifier, $name);

		if ( ! $dependency and ! empty($this->providers))
		{
			// Fall back to providers, this is
			// more expensive.
			$dependency = $this->providerLookup($identifier, $name);
		}

		if ( ! $dependency)
		{
				$dependency = $this->dynamicLoopup($identifier, $name);
		}

		// We have failed to resolve the dependencies,
		// it is time to bring the pain.
		if ( ! $dependency)
		{
			throw new ResolveException('Unable to resolve: '.$identifier);
		}

		return $dependency;
	}

	/**
	 * Resolve a depenency
	 *
	 * @param   string      $identifier  identifier
	 * @param   string      $name        optional instance name
	 * @return  mixed|null  resoled result if available, otherwise null
	 */
	public function resolveEntry($identifier, $name = null)
	{
		if (isset($this->entries[$identifier]))
		{
			return $this->entries[$identifier]->resolve($identifier, $name);
		}
	}

	/**
	 * Retrieve an object from cache
	 *
	 * @param   string       $identifier  identifier
	 * @param   string       $name        instance name
	 * @return  object|null  dependency or null when not found
	 */
	public function findCached($identifier, $name = null)
	{
		$cacheKey = $identifier.'::'.($name ?: $identifier);

		if (isset($this->instances[$cacheKey]))
		{
			return $this->instances[$cacheKey];
		}
	}

	/**
	 * Retrieve an object from cache
	 *
	 * @param   string       $identifier  identifier
	 * @param   string       $name        instance name
	 * @return  $this
	 */
	public function removeCached($identifier, $name = null)
	{
		$cacheKey = $identifier.'::'.($name ?: $identifier);

		if (isset($this->instances[$cacheKey]))
		{
			unset($this->instances[$cacheKey]);
		}

		return $this;
	}

	/**
	 * Singleton resolving.
	 *
	 * @param  string  $identifier  dependency identifier
	 */
	public function singleton($identifier)
	{
		return $this->resolve($identifier, $identifier);
	}

	/**
	 * Forge a new instance.
	 *
	 * @param   string  $identifier  dependency identifier
	 * @return  mixed  resolved dependency
	 */
	public function forge($identifier)
	{
		return $this->resolve($identifier, false);
	}

	/**
	 * Resolve a dependency through registered providers.
	 *
	 * @param   string  $identifier  identifier
	 * @param   string  $name        instance name
	 */
	public function providerLookup($identifier, $name = null)
	{
		if ($provider = $this->findProvider($identifier))
		{
			return $provider->resolve($identifier, $name);
		}
	}

	/**
	 * Resolve a non-registered class.
	 *
	 * @param   string  $idenfitier  identifier
	 * @param   string  $name        instance name
	 * @return  mixed  resolved resource
	 */
	public function dynamicLoopup($identifier, $name = null)
	{
		$entry = new Entry($identifier, $this);

		try
		{
			return $entry->resolve($identifier, $name);
		}
		catch (ResolveException $e)
		{
			// Ignore this exception
		}
	}

	/**
	 * Register a dependency resolver
	 *
	 * @param   string   $identifier  identifier
	 * @param   mixed    $factory     string class name, object or callable that returns one of those
	 * @param   closure  $config      configuration callback
	 * @return  $this
	 */
	public function register($identifier, $factory, $config = null)
	{
		if ( ! $factory instanceof Entry)
		{
			$factory = new Entry($factory);
		}

		if ($config)
		{
			$config($factory);
		}

		$factory->setContainer($this);
		$this->entries[$identifier] = $factory;

		return $this;
	}

	/**
	 * Remove an entry.
	 *
	 * @param   string  $identifier  identifier
	 * @return  $this
	 */
	public function unregister($identifier)
	{
		if (isset($this->entries[$identifier]))
		{
			unset($this->entries[$identifier]);
		}

		return $this;
	}

	/**
	 * Inject a dependency.
	 *
	 * @param   string  $identifier  identifier
	 * @param   object  $dependency  dependency
	 * @param   string  $name        dependency name
	 * @return  $this
	 */
	public function inject($identifier, &$dependency, $name = null)
	{
		$cacheKey = $identifier.'::'.($name ?: $identifier);
		$this->instances[$cacheKey] = &$dependency;

		return $this;
	}

	/**
	 * Register a provider.
	 *
	 * @param  string        $root      root
	 * @param  Provider      $provider  provider
	 * @param  closure|null  configuration closure
	 */
	public function registerProvider($root, Provider $provider, $config = null)
	{
		$provider->setContainer($this);
		$provider->setRoot($root);

		if ($config)
		{
			$config($provider);
		}

		$provider->provide($this);
		$this->providers[$root] = $provider;

		return $this;
	}

	/**
	 * Find the provides for a given identifier.
	 *
	 * @param   string         $identifier  dependency
	 * @return  Provider|null  provider or null when not found
	 */
	public function findProvider($identifier)
	{
		foreach ($this->providers as $root => $provider)
		{
			if (strpos($identifier, $root) === 0)
			{
				return $provider;
			}
		}
	}

	/**
	 * Remove a provider
	 *
	 * @param   string  $root  the root the provider responds to
	 * @return  $this
	 */
	public function unregisterProvider($root)
	{
		if (isset($this->providers[$root]))
		{
			unset($this->providers[$root]);
		}

		return $this;
	}
}