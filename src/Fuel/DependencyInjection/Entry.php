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
	public function __construct($factory)
	{
		$this->factory = $factory;
	}

	/**
	 * Get the entry result.
	 *
	 * @return  mixed  dependancy result
	 */
	public function forge()
	{
		return $this->factory;
	}
}