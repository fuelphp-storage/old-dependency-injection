<?php

namespace Fuel\DependencyInjection;

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
	 * @throws  Fuel\DependencyInjection\ResolveException
	 */
	public function resolve($identifier, $name = null)
	{
		// Return any previously named instances
		if ($name !== false and $cached = $this->findCached($identifier, $name))
		{
			return $cached;
		}

		// Try to resolve from a local entries
		$dependency = $this->resolveEntry($identifier, $name);

		if ( ! $dependency)
		{
			// Fall back to providers, this is
			// more expensive.
			$dependency = $this->providerLookup($identifier, $name);
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
	 * @param   string  $identifier  identifier
	 * @param   string  $name        optional instance name
	 * @return
	 */
	public function resolveEntry($identifier, $name = null)
	{
		if ($name !== false and $cached = $this->findCached($identifier, $name))
		{
			return $cached;
		}

		if ( ! isset($this->entries[$identifier]))
		{
			return false;
		}

		return $this->entries[$identifier]->resolve($identifier, $name);
	}

	/**
	 * Retrieve an object from cache
	 *
	 * @param   string       $identifier  identifier
	 * @param   string       $name        instance name
	 * @return  object|null  dependency or null when not found
	 */
	protected function findCached($identifier, $name)
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
	protected function removeCached($identifier, $name = null)
	{
		$cacheKey = $identifier.'::'.($name ?: $identifier);

		if (isset($this->instance[$cacheKey]))
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
		return $this->retrieve($identifier, $identifier);
	}

	/**
	 * Forge a new instance.
	 *
	 * @param   string  $identifier  dependency identifier
	 * @return  object  resolved dependency
	 */
	public function forge($identifier)
	{
		return $this->retrieve($identifier, false);
	}

	/**
	 * Resolve a dependency through registered providers.
	 *
	 * @param   string  $identifier  identifier
	 */
	protected function providerLookup($identifier, $name)
	{
		if ( ! $provider = $this->findProvider($identifier))
		{
			return false;
		}

		return $provider->resolve($identifier, $name);
	}

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
	public function inject($identifier, $dependency, $name = null)
	{
		$cacheKey = $identifier.'::'.($name ?: $identifier);
		$this->instances[$cacheKey] = $dependency;

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