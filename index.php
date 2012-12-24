<?php

include './vendor/autoload.php';

$container = new Fuel\DependencyInjection\Container();

class DummyProvider extends Fuel\DependencyInjection\Provider
{
	public function forge($container, $suffix)
	{
		return 'Dep';
	}
}

$container->register('Dep', 'ExtDep', function($entry){
	$entry->preferSingleton();
});

$container->registerProvider('dummy', new DummyProvider);

class Dep
{
	public $id = null;

	public function __construct()
	{
		$this->id = time();
	}
}

class ExtDep extends Dep {}

class Dependant
{
	public $dep;

	public $other;

	public $third;

	public function __construct(Dep $dep, $arg = null)
	{
		$this->dep = $dep;
		$this->third = $arg;
	}

	public function setOther($other)
	{
		$this->other = $other;
	}
}

$container->register('eh', 'Dependant', function($entry) {
	$entry->preferSingleton()
		->methodInjection('setOther', 'Dep')
		->paramInjection('dummy', 'Dep');
});

$dummy = $container->resolve('dummy.haha');
//print_r($dummy);
//die();

var_dump($container->resolve('eh'));

//print_r($container);
