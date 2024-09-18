<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks\Text;

require_once __DIR__ . '/DummyBlockRenderer.php';

class BlocksRegistryTest extends \MailPoetTest {

  /** @var BlocksRegistry */
  private $registry;

  public function _before() {
    parent::_before();
    $this->registry = $this->diContainer->get(BlocksRegistry::class);
  }

  public function testItReturnsNullForUnknownRenderer() {
    $storedRenderer = $this->registry->getBlockRenderer('test');
    verify($storedRenderer)->null();
  }

  public function testItStoresAddedRenderer() {
    $renderer = new Text();
    $this->registry->addBlockRenderer('test', $renderer);
    $storedRenderer = $this->registry->getBlockRenderer('test');
    verify($storedRenderer)->equals($renderer);
  }

  public function testItReportsWhichRenderersAreRegistered() {
    $renderer = new Text();
    $this->registry->addBlockRenderer('test', $renderer);
    verify($this->registry->hasBlockRenderer('test'))->true();
    verify($this->registry->hasBlockRenderer('unknown'))->false();
  }
}
