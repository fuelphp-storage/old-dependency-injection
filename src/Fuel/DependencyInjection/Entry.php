<?php

namespace Fuel\DependencyInjection;

class Entry extends Resolver
{
	/**
	 * @var  callable|string|object  $factory  object, classname or callable returning one of those
	 */
	protected $factory;

	/**
	 * Constructor.
	 *
	 * @param  callable                            $factory
	 * @param  Fuel\DependencyInjection\Container  $container  container
	 */
	public function __construct($factory, $container = null)
	{
		$this->factory = $factory;
		$container and $this->container = $container;
	}

	/**
	 * Get the entry result.
	 *
	 * @return  mixed  dependancy result
	 */
	public function forge($container)
	{
		return $this->factory;
	}
}