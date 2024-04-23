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

  public function testItAllowsToReplaceRendererViaFilter() {
    $renderer = new Text();
    $dummyRenderer = new DummyBlockRenderer();
    $this->registry->addBlockRenderer('test', $renderer);
    $callback = function () use ($dummyRenderer) {
      return $dummyRenderer;
    };
    add_filter('mailpoet_block_renderer_test', $callback);
    $storedRenderer = $this->registry->getBlockRenderer('test');
    verify($storedRenderer)->equals($dummyRenderer);
    remove_filter('mailpoet_block_renderer_test', $callback);
  }

  public function testItRemovesAllBlockRenderers() {
    $renderer = new Text();
    verify(has_filter('render_block_test'))->false();
    verify(has_filter('render_block_test2'))->false();

    $this->registry->addBlockRenderer('test', $renderer);
    $this->registry->addBlockRenderer('test2', $renderer);
    verify(has_filter('render_block_test'))->true();
    verify(has_filter('render_block_test2'))->true();
    verify($this->registry->getBlockRenderer('test'))->notNull();
    verify($this->registry->getBlockRenderer('test2'))->notNull();

    $this->registry->removeAllBlockRenderers();
    verify(has_filter('render_block_test'))->false();
    verify(has_filter('render_block_test2'))->false();
    verify($this->registry->getBlockRenderer('test'))->null();
    verify($this->registry->getBlockRenderer('test2'))->null();
  }
}
