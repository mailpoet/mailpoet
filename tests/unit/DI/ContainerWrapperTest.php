<?php

namespace MailPoet\Test\DI;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ContainerWrapperTest extends \MailPoetUnitTest {
  public function testItCanConstruct() {
    $instance = new ContainerWrapper(new ContainerBuilder());
    expect($instance)->isInstanceOf(ContainerWrapper::class);
    expect($instance)->isInstanceOf(ContainerInterface::class);

    $instance = new ContainerWrapper(new ContainerBuilder(), new ContainerBuilder());
    expect($instance)->isInstanceOf(ContainerWrapper::class);
    expect($instance)->isInstanceOf(ContainerInterface::class);
  }

  public function testItProvidesPremiumContainerIfAvailable() {
    $instance = new ContainerWrapper(new ContainerBuilder());
    expect($instance->getPremiumContainer())->null();

    $instance = new ContainerWrapper(new ContainerBuilder(), new ContainerBuilder());
    expect($instance->getPremiumContainer())->isInstanceOf(ContainerBuilder::class);
  }

  public function testItProvidesFreePluginServices() {
    $freeContainerStub = Stub::make(Container::class, [
      'get' => function () {
          return 'service';
      },
    ]);
    $instance = new ContainerWrapper($freeContainerStub);
    $service = $instance->get('service_id');
    expect($service)->equals('service');
  }

  public function testItThrowsFreePluginServices() {
    $freeContainerStub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $instance = new ContainerWrapper($freeContainerStub);
    $exception = null;
    try {
      $instance->get('service');
    } catch (ServiceNotFoundException $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(ServiceNotFoundException::class);
  }

  public function testItReturnServiceFromPremium() {
    $freeContainerStub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $premiumContainerStub = Stub::make(Container::class, [
      'has' => function () {
        return true;
      },
      'get' => function () {
        return 'service_1';
      },
    ]);
    $instance = new ContainerWrapper($freeContainerStub, $premiumContainerStub);
    expect($instance->get('service'))->equals('service_1');
  }

  public function testItThrowsIfServiceNotFoundInBothContainers() {
    $containerStub = Stub::make(Container::class, [
      'get' => function ($id) {
        throw new ServiceNotFoundException($id);
      },
    ]);
    $instance = new ContainerWrapper($containerStub, $containerStub);
    $exception = null;
    try {
      $instance->get('service');
    } catch (ServiceNotFoundException $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(ServiceNotFoundException::class);
  }
}
