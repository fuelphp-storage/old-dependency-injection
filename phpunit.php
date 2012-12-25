<?php

namespace Fuel\DependencyInjection {
	class ProviderThatProvides extends Provide {
		public function provide($container)
		{
			$container->register('from.provider', 'from provider');
		}
	}

	class ProviderThatForges {
		public function forge($container, $suffix)
		{

		}
	}
}

namespace {
	include './vendor/autoload.php';
}