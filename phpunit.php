<?php

include './vendor/autoload.php';

class ProviderThatProvides extends Fuel\DependencyInjection\Provider
{
	public function provide($container)
	{
		$container->register('from.provider', 'stdClass');
	}
}

class ProviderThatForges extends Fuel\DependencyInjection\Provider
{
	public function forge($container, $suffix)
	{
		return $suffix;
	}
}

class ClassHint
{
	public $injection;

	public function __construct(stdClass $dep)
	{
		$this->injection = $dep;
	}
}

class NamedParam
{
	public $injection;

	public function __construct($named)
	{
		$this->injection = $named;
	}
}

class NamedParamAlias
{
	public $injection;

	public function __construct($alias)
	{
		$this->injection = $alias;
	}
}

class ParamDefault
{
	public $injection;

	public function __construct($alias = 1)
	{
		$this->injection = $alias;
	}
}

class ResolveFail
{
	public function __construct(CauseOfFail $parameter) {}
}

class CauseOfFail
{
	public function __construct($something) {}
}

class PlainClass {}

class InjectableThroughMethod
{
	public $string;

	public $object;

	public function setString($string)
	{
		$this->string = $string;
	}

	public function setObject($object)
	{
		$this->object = $object;
	}
}