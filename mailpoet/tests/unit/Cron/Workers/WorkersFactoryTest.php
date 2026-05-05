<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\DI\ContainerWrapper;

class WorkersFactoryTest extends \MailPoetUnitTest {
  public function testItRegistersSubscriberLimitNotificationAsSimpleWorker(): void {
    $this->assertContains(SubscriberLimitNotificationWorker::TASK_TYPE, WorkersFactory::SIMPLE_WORKER_TYPES);
  }

  public function testItCreatesSubscriberLimitNotificationWorker(): void {
    $worker = $this->getMockBuilder(SubscriberLimitNotificationWorker::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container = $this->getMockBuilder(ContainerWrapper::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->once())
      ->method('get')
      ->with(SubscriberLimitNotificationWorker::class)
      ->willReturn($worker);

    $factory = new WorkersFactory($container);
    $this->assertSame($worker, $factory->createSubscriberLimitNotificationWorker());
  }
}
