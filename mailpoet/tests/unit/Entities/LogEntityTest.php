<?php declare(strict_types = 1);

namespace MailPoet\Test\Entities;

use MailPoet\Entities\LogEntity;

class LogEntityTest extends \MailPoetUnitTest {
  public function testItStoresAnExceptionInsteadOfAnEmptyObject() {
    $entity = new LogEntity();
    $entity->setContext(['error' => new \RuntimeException('Unsupported Amazon SES region', 42)]);

    $context = $entity->getContext();
    $this->assertNotNull($context);
    $this->assertArrayHasKey('error', $context);
    verify($context['error']['class'])->equals(\RuntimeException::class);
    verify($context['error']['message'])->equals('Unsupported Amazon SES region');
    verify($context['error']['code'])->equals(42);
    verify($context['error']['file'])->stringContainsString('LogEntityTest.php:');
    verify($context['error'])->arrayHasNotKey('trace');
  }

  public function testItKeepsTheCauseOfAWrappedException() {
    $entity = new LogEntity();
    $entity->setContext(['error' => new \RuntimeException('outer', 0, new \LogicException('the real cause'))]);

    $context = $entity->getContext();
    $this->assertNotNull($context);
    $this->assertArrayHasKey('error', $context);
    verify($context['error']['previous']['class'])->equals(\LogicException::class);
    verify($context['error']['previous']['message'])->equals('the real cause');
  }

  public function testItStillStoresTheContextWhenAMessageHasInvalidUtf8() {
    $entity = new LogEntity();
    $entity->setContext([
      'error' => new \RuntimeException("boom \xFF\xFE invalid"),
      'worker' => 'createQueueWorker',
    ]);

    $context = $entity->getContext();
    $this->assertNotNull($context);
    $this->assertArrayHasKey('error', $context);
    verify($context['worker'])->equals('createQueueWorker');
    verify($context['error']['message'])->stringContainsString('boom');
    verify($context['error']['message'])->stringContainsString('invalid');
  }

  public function testItLeavesOrdinaryValuesAlone() {
    $entity = new LogEntity();
    $entity->setContext(['worker' => 'createQueueWorker', 'count' => 3, 'ok' => true]);

    verify($entity->getContext())->equals(['worker' => 'createQueueWorker', 'count' => 3, 'ok' => true]);
  }
}
