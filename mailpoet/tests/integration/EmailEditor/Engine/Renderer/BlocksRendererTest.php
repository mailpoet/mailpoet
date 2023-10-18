<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\StylesController;

require_once __DIR__ . '/DummyBlockRenderer.php';

class BlocksRendererTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    add_action('mailpoet_blocks_renderer_initialized', [$this, 'registerDummyBlock']);
  }

  public function testItRendersContentForRegisteredBlocks() {
    $renderer = $this->getBlocksRenderer();
    $content = $renderer->render([
      [
        'blockName' => 'dummy/block',
        'innerHTML' => 'Hello',
      ],
      [
        'blockName' => 'dummy/block',
        'innerHTML' => 'Buddy!',
      ],
    ]);
    verify($content)->equals('HelloBuddy!');
  }

  public function testItSkipsUnknownBlocks() {
    $renderer = $this->getBlocksRenderer();
    $content = $renderer->render([
      [
        'blockName' => 'dummy/block',
        'innerHTML' => 'Hello',
      ],
      [
        'blockName' => 'unknown/block',
        'innerHTML' => 'Buddy!',
      ],
    ]);
    verify($content)->equals('Hello');
  }

  public function testItCanProcessNestedBlocks() {
    $renderer = $this->getBlocksRenderer();
    $content = $renderer->render([
      [
        'blockName' => 'dummy/block',
        'innerBlocks' => [[
            'blockName' => 'dummy/block',
            'innerHTML' => 'Hello',
          ],
          [
            'blockName' => 'dummy/block',
            'innerHTML' => 'Buddy!',
          ],
        ],
      ],
    ]);
    verify($content)->equals('[HelloBuddy!]');
  }

  public function _after() {
    parent::_after();
    remove_action('mailpoet_blocks_renderer_initialized', [$this, 'postRegisterCallback']);
  }

  public function registerDummyBlock(BlocksRegistry $registry) {
    $registry->addBlockRenderer('dummy/block', new DummyBlockRenderer());
  }

  private function getBlocksRenderer() {
    return new BlocksRenderer(
      new BlocksRegistry(),
      new StylesController()
    );
  }
}
