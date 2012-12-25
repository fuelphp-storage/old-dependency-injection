<?php

namespace Fuel\DependencyInjection;

class Entry extends Resolver
{
	/**
	 * @var  callable|string  $factory  instance constructor
	 */
	protected $factory;

	/**
	 * Constructor.
	 *
	 * @param  callable  $factory
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