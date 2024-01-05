<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Layout;

use MailPoet\EmailEditor\Engine\Renderer\BlocksRegistry;
use MailPoet\EmailEditor\Engine\Renderer\DummyBlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

require_once __DIR__ . '/../DummyBlockRenderer.php';

class FlexLayoutRendererTest extends \MailPoetTest {

  /** @var BlocksRegistry */
  private $registry;

  /** @var FlexLayoutRenderer */
  private $renderer;

  /** @var SettingsController */
  private $settingsController;

  public function _before(): void {
    parent::_before();
    $this->settingsController = new SettingsController();
    $this->registry = new BlocksRegistry($this->settingsController);
    $this->renderer = new FlexLayoutRenderer();
    $this->registry->addBlockRenderer('dummy/block', new DummyBlockRenderer());
    register_block_type('dummy/block', []);
  }

  public function testItRendersInnerBlocks(): void {
    $parsedBlock = [
      'innerBlocks' => [
        [
          'blockName' => 'dummy/block',
          'innerHtml' => 'Dummy 1',
        ],
        [
          'blockName' => 'dummy/block',
          'innerHtml' => 'Dummy 2',
        ],
      ],
      'email_attrs' => [],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    verify($output)->stringContainsString('Dummy 1');
    verify($output)->stringContainsString('Dummy 2');
  }

  public function testItHandlesJustification(): void {
    $parsedBlock = [
      'innerBlocks' => [
        [
          'blockName' => 'dummy/block',
          'innerHtml' => 'Dummy 1',
        ],
      ],
      'email_attrs' => [],
    ];
    // Default justification is left
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    verify($output)->stringContainsString('text-align: left');
    verify($output)->stringContainsString('align="left"');
    // Right justification
    $parsedBlock['attrs']['layout']['justifyContent'] = 'right';
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    verify($output)->stringContainsString('text-align: right');
    verify($output)->stringContainsString('align="right"');
    // Center justification
    $parsedBlock['attrs']['layout']['justifyContent'] = 'center';
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    verify($output)->stringContainsString('text-align: center');
    verify($output)->stringContainsString('align="center"');
  }

  public function testItEscapesAttributes(): void {
    $parsedBlock = [
      'innerBlocks' => [
        [
          'blockName' => 'dummy/block',
          'innerHtml' => 'Dummy 1',
        ],
      ],
      'email_attrs' => [],
    ];
    $parsedBlock['attrs']['layout']['justifyContent'] = '"> <script>alert("XSS")</script><div style="text-align: right';
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    verify($output)->stringNotContainsString('<script>alert("XSS")</script>');
  }

  public function testInComputesProperWidthsForReasonableSettings(): void {
    $parsedBlock = [
      'innerBlocks' => [],
      'email_attrs' => [
        'width' => '640px',
      ],
    ];

    // 50% and 25%
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '50'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => ['width' => '25'],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:312px;');
    verify($flexItems[1])->stringContainsString('width:148px;');

    // 25% and 25% and auto
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '25'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => ['width' => '25'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 3',
        'attrs' => [],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:148px;');
    verify($flexItems[1])->stringContainsString('width:148px;');
    verify($flexItems[2])->stringNotContainsString('width:');

    // 50% and 50%
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '50'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => ['width' => '50'],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:312px;');
    verify($flexItems[1])->stringContainsString('width:312px;');
  }

  public function testInComputesWidthsForStrangeSettingsValues(): void {
    $parsedBlock = [
      'innerBlocks' => [],
      'email_attrs' => [
        'width' => '640px',
      ],
    ];

    // 100% and 25%
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '100'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => ['width' => '25'],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:508px;');
    verify($flexItems[1])->stringContainsString('width:105px;');

    // 100% and 100%
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '100'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => ['width' => '100'],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:312px;');
    verify($flexItems[1])->stringContainsString('width:312px;');


    // 100% and auto
    $parsedBlock['innerBlocks'] = [
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 1',
        'attrs' => ['width' => '100'],
      ],
      [
        'blockName' => 'dummy/block',
        'innerHtml' => 'Dummy 2',
        'attrs' => [],
      ],
    ];
    $output = $this->renderer->renderInnerBlocksInLayout($parsedBlock, $this->settingsController);
    $flexItems = $this->getFlexItemsFromOutput($output);
    verify($flexItems[0])->stringContainsString('width:508px;');
    verify($flexItems[1])->stringNotContainsString('width:');
  }

  private function getFlexItemsFromOutput(string $output): array {
    $matches = [];
    preg_match_all('/<td class="layout-flex-item" style="(.*)">/', $output, $matches);
    return explode('><', $matches[0][0] ?? []);
  }

  public function _after(): void {
    parent::_after();
    unregister_block_type('dummy/block');
  }
}
