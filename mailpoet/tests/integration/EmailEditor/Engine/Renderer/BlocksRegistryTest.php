<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks\Paragraph;

require_once __DIR__ . '/DummyBlockRenderer.php';

class BlocksRegistryTest extends \MailPoetTest {

  /** @var BlocksRegistry */
  private $registry;

  public function _before() {
    parent::_before();
    $this->registry = new BlocksRegistry(new SettingsController());
  }

  public function testItReturnsNullForUnknownRenderer() {
    $storedRenderer = $this->registry->getBlockRenderer('test');
    verify($storedRenderer)->null();
  }

  public function testItStoresAddedRenderer() {
    $renderer = new Paragraph();
    $this->registry->addBlockRenderer('test', $renderer);
    $storedRenderer = $this->registry->getBlockRenderer('test');
    verify($storedRenderer)->equals($renderer);
  }

  public function testItAllowsToReplaceRendererViaFilter() {
    $renderer = new Paragraph();
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

  public function testItRemovesAllBlockRendererFilters() {
    $renderer = new Paragraph();
    verify(has_filter('render_block_test'))->false();
    verify(has_filter('render_block_test2'))->false();

    $this->registry->addBlockRenderer('test', $renderer);
    $this->registry->addBlockRenderer('test2', $renderer);
    verify(has_filter('render_block_test'))->true();
    verify(has_filter('render_block_test2'))->true();
    verify($this->registry->getBlockRenderer('test'))->notNull();
    verify($this->registry->getBlockRenderer('test2'))->notNull();

    $this->registry->removeAllBlockRendererFilters();
    verify(has_filter('render_block_test'))->false();
    verify(has_filter('render_block_test2'))->false();
    verify($this->registry->getBlockRenderer('test'))->null();
    verify($this->registry->getBlockRenderer('test2'))->null();
  }
}
