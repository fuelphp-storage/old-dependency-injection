<?php

use FuelPHP\DependencyInjection\Container;
use FuelPHP\DependencyInjection\Entry;

class ContainerTests extends PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException FuelPHP\DependencyInjection\ResolveException
	 */
	public function testResolveFail()
	{
		$container = new Container();

		$container->resolve('unknown.dependency');
	}

	public function testDynamicResolve()
	{
		$container = new Container();

		$result = $container->resolve('stdClass');

		$this->assertInstanceOf('stdClass', $result);
	}

	public function testResolveString()
	{
		$container = new Container();

		$container->register('dep', 'stdClass');

		$result = $container->resolve('dep');

		$this->assertInstanceOf('stdClass', $result);
	}

	public function testResolveCallableReturnsObject()
	{
		$container = new Container();

		$container->register('dep', function(){
			return new stdClass;
		});

		$result = $container->resolve('dep');

		$this->assertInstanceOf('stdClass', $result);
	}

	public function testResolveCallableReturnsString()
	{
		$container = new Container();

		$container->register('dep', function(){
			return 'stdClass';
		});

		$result = $container->resolve('dep');

		$this->assertInstanceOf('stdClass', $result);
	}

	public function testResolveSingleton()
	{
		$container = new Container();

		$container->register('dep', function(){
			return 'stdClass';
		});

		$a = $container->singleton('dep');
		$b = $container->singleton('dep');
		$c = $container->resolve('dep');
		$d = $container->forge('dep');

		$hashA = spl_object_hash($a);
		$hashB = spl_object_hash($b);
		$hashC = spl_object_hash($c);
		$hashD = spl_object_hash($d);

		$this->assertEquals($hashA, $hashB);
		$this->assertTrue($hashB === $hashC);
		$this->assertTrue($hashA !== $hashD);
	}

	public function testPreferSingleton()
	{
		$container = new Container();
		$container->register('dep', 'stdClass', function($entry){
			$entry->preferSingleton();
		});
		$a = $container->resolve('dep');
		$b = $container->singleton('dep');
		$hashA = spl_object_hash($a);
		$hashB = spl_object_hash($b);
		$this->assertEquals($hashA, $hashB);
	}

	/**
	 * @expectedException FuelPHP\DependencyInjection\ResolveException
	 */
	public function testBlockSingleton()
	{
		$container = new Container();
		$entry = new Entry('stdClass');
		$entry->blockSingleton();
		$container->register('no.singleton', $entry);
		$container->singleton('no.singleton');
	}

	/**
	 * @expectedException FuelPHP\DependencyInjection\ResolveException
	 */
	public function testBlockNamedSingleton()
	{
		$container = new Container();
		$entry = new Entry('stdClass');
		$entry->blockSingleton();
		$container->register('no.singleton', $entry);
		$container->resolve('no.singleton', 'no.singleton');
	}

	public function testFromCache()
	{
		$container = new Container();

		$a = $container->singleton('stdClass');
		$b = $container->singleton('stdClass');
		$container->removeCached('stdClass');
		$c = $container->singleton('stdClass');

		$hashA = spl_object_hash($a);
		$hashB = spl_object_hash($b);
		$hashC = spl_object_hash($c);

		$this->assertEquals($hashA, $hashB);
		$this->assertFalse($hashB === $hashC);
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testUnregister()
	{
		$container = new Container();
		$container->register('dep', 'stdClass');
		$dep = $container->resolve('dep');
		$this->assertInstanceOf('stdClass', $dep);
		$container->unregister('dep');
		$container->resolve('dep');
	}

	public function testRegisterConfig()
	{
		$container = new Container();
		$mock = Mockery::mock('FuelPHP\DependencyInjection\Entry', function($mock) use ($container){
			$mock->shouldReceive('setContainer')
				->once()
				->with($container)
				->andReturn($mock)
				->shouldReceive('configCall')
				->once();
		});

		$container->register('dep', $mock, function($entry){
			$entry->configCall();
		});
	}

	public function testProviderRegister()
	{
		$container = new Container();
		$provider = Mockery::mock('FuelPHP\DependencyInjection\Provider', function($mock) use ($container) {
			$mock->shouldReceive('setContainer')
				->once()
				->with($container)
				->andReturn($mock)
				->shouldReceive('setRoot')
				->once()
				->with('base.node')
				->andReturn($mock)
				->shouldReceive('provide')
				->once()
				->with($container)
				->andReturn($mock)
				->shouldReceive('configCall')
				->once()
				->shouldReceive('resolve')
				->once()
				->with('base.node.named', null)
				->andReturn('return value');
		});

		$container->registerProvider('base.node', $provider, function($provider) {
			$provider->configCall();
		});

		$resolved = $container->resolve('base.node.named');
		$this->assertEquals('return value', $resolved);
	}

	public function testProviderForge()
	{
		$container = new Container;
		$container->registerProvider('give.me.a', new ProviderThatForges);
		$resolved = $container->resolve('give.me.a.stdClass');
		$this->assertInstanceOf('stdClass', $resolved);
	}

	public function testProviderProvide()
	{
		$container = new Container;
		$container->registerProvider('the.provider', new ProviderThatProvides);
		$result = $container->resolve('from.provider');
		$this->assertInstanceOf('stdClass', $result);
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testProfiderForgeFail()
	{
		$container = new Container;
		$container->registerProvider('forge.will', new ProviderThatProvides);
		$container->resolve('forge.will.fail');
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testAllowSingleton()
	{
		$container = new Container;

		$container->register('dep', 'stdClass', function($entry){
			$entry->allowSingleton(false);
		});

		$container->singleton('dep');
	}

	public function testResolveClassHint()
	{
		$container = new Container();

		$result = $container->resolve('ClassHint');
		$this->assertInstanceOf('stdClass', $result->injection);
	}

	public function testResolveNamedParam()
	{
		$container = new Container();
		$container->register('named', 'stdClass');

		$result = $container->resolve('NamedParam');
		$this->assertInstanceOf('stdClass', $result->injection);
	}

	public function testResolveNamedParamAlias()
	{
		$container = new Container();
		$container->register('named', 'stdClass');
		$container->register('depending', 'NamedParamAlias', function($entry){
			$entry->aliasParam('alias', 'named');
		});

		$result = $container->resolve('depending');
		$this->assertInstanceOf('stdClass', $result->injection);
	}

	public function testResolveParamDefault()
	{
		$container = new Container();
		$container->register('named', 'stdClass');
		$container->register('depending', 'ParamDefault');

		$result = $container->resolve('depending');
		$this->assertEquals(1, $result->injection);
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testResolveParamFail()
	{
		$container = new Container;
		$container->resolve('ResolveFail');
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testProviderLookupFail()
	{
		$container = new Container;
		$container->registerProvider('this.root', new ProviderThatForges);

		$container->resolve('something.unknown');
	}

	/**
	 * @expectedException  FuelPHP\DependencyInjection\ResolveException
	 */
	public function testProviderUnregister()
	{
		$container = new Container;
		$container->registerProvider('provider', new ProviderThatForges);
		$result = $container->resolve('provider.stdClass');
		$this->assertInstanceOf('stdClass', $result);
		$container->unregisterProvider('provider');
		$container->resolve('provider.stdClass');
		$this->assertInstanceOf('stdClass', $result);
	}

	public function testDontResolveParams()
	{
		$container = new Container;
		$container->register('dep', 'PlainClass', function($entry){
			$entry->resolveParamAlias(false)
				->resolveParamClass(false);
		});

		$result = $container->resolve('dep');
		$this->assertInstanceOf('PlainClass', $result);
	}

	public function testMethodInjection()
	{
		$container = new Container;
		$object = new stdClass;

		$container->register('dep', 'InjectableThroughMethod', function($entry) use ($object){
			$entry->methodInjection('setString', 'stdClass');
			$entry->methodInjection('setObject', $object);
		});

		$dep = $container->resolve('dep');
		$this->assertEquals($object, $dep->string);
		$this->assertInstanceOf('stdClass', $dep->object);
	}
}