<?php declare(strict_types = 1);

namespace MailPoet\Test\DI;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

require_once __DIR__ . '/TestService.php';

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
          return new TestService();
      },
    ]);
    $instance = new ContainerWrapper($freeContainerStub);
    $service = $instance->get(TestService::class);
    $this->assertInstanceOf(TestService::class, $service);
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
      /* @phpstan-ignore-next-line - normally it is not allowed to pass an arbitrary string here, but  we want to test this behaviour */
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
        return new TestService();
      },
    ]);
    $instance = new ContainerWrapper($freeContainerStub, $premiumContainerStub);
    $service = $instance->get(TestService::class);
    $this->assertInstanceOf(TestService::class, $service);
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
      /* @phpstan-ignore-next-line - normally it is not allowed to pass an arbitrary string here, but  we want to test this behaviour */
      $instance->get('service');
    } catch (ServiceNotFoundException $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(ServiceNotFoundException::class);
  }
}
