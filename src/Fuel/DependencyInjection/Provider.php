<?php

namespace Fuel\DependencyInjection;

abstract class Provider extends Resolver
{
	/**
	 * Provide entries to the container
	 *
	 * @param   Fuel\DependencyInjection\Container  $container  container
	 * @return  void
	 */
	public function provide($container)
	{
		/**
		 * This is used for packages that register multiple
		 * dependencies. The function is called when a
		 * provider is added to the container. It's left empty
		 * in case the provider doesn't provide anything.
		 */
	}

	/**
	 * Create a new object
	 *
	 * @param   Fuel\DependencyInjection\Container  $container  container
	 * @param   string                              $suffix  identifier suffix
	 * @return  object                              dependency
	 */
	protected function forge($container, $suffix)
	{
		throw new ResolveException(get_called_class().' does not have a instance forge.');
	}
}