<?php

namespace MailPoet\Test\DI;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ContainerWrapperTest extends \MailPoetUnitTest {
  function testItCanConstruct() {
    $instance = new ContainerWrapper(new ContainerBuilder());
    expect($instance)->isInstanceOf(ContainerWrapper::class);
    expect($instance)->isInstanceOf(ContainerInterface::class);

    $instance = new ContainerWrapper(new ContainerBuilder(), new ContainerBuilder());
    expect($instance)->isInstanceOf(ContainerWrapper::class);
    expect($instance)->isInstanceOf(ContainerInterface::class);
  }

  function testItProvidesPremiumContainerIfAvailable() {
    $instance = new ContainerWrapper(new ContainerBuilder());
    expect($instance->getPremiumContainer())->null();

    $instance = new ContainerWrapper(new ContainerBuilder(), new ContainerBuilder());
    expect($instance->getPremiumContainer())->isInstanceOf(ContainerBuilder::class);
  }

  function testItProvidesFreePluginServices() {
    $free_container_stub = Stub::make(Container::class, [
      'get' => function () {
          return 'service';
      },
    ]);
    $instance = new ContainerWrapper($free_container_stub);
    $service = $instance->get('service_id');
    expect($service)->equals('service');
  }

  function testItThrowsFreePluginServices() {
    $free_container_stub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $instance = new ContainerWrapper($free_container_stub);
    $exception = null;
    try {
      $instance->get('service');
    } catch (ServiceNotFoundException $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(ServiceNotFoundException::class);
  }

  function testItReturnServiceFromPremium() {
    $free_container_stub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $premium_container_stub = Stub::make(Container::class, [
      'get' => function () {
        return 'service_1';
      },
    ]);
    $instance = new ContainerWrapper($free_container_stub, $premium_container_stub);
    expect($instance->get('service'))->equals('service_1');
  }

  function testItThrowsIfServiceNotFoundInBothContainers() {
    $container_stub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $instance = new ContainerWrapper($container_stub, $container_stub);
    $exception = null;
    try {
      $instance->get('service');
    } catch (ServiceNotFoundException $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(ServiceNotFoundException::class);
  }
}
