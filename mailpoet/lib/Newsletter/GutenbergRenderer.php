<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Renderer;

class GutenbergRenderer {

  /** @var Renderer */
  private $renderer;

  private $columnWrap = [
    'type' => 'container',
    'columnLayout' => false,
    'orientation' => 'vertical',
    'image' =>
      [
        'src' => null,
        'display' => 'scale',
      ],
    'styles' =>
      [
        'block' =>
          [
            'backgroundColor' => 'transparent',
          ],
      ],
    'blocks' =>
      [
      ],
  ];

  private $columnsWrap = [
    'type' => 'container',
    'columnLayout' => false,
    'orientation' => 'horizontal',
    'image' =>
      [
        'src' => null,
        'display' => 'scale',
      ],
    'styles' =>
      [
        'block' =>
          [
            'backgroundColor' => 'transparent',
          ],
      ],
    'blocks' =>
      [
      ],
  ];

  private $wrap = [
  'content' =>
    [
      'type' => 'container',
      'columnLayout' => false,
      'orientation' => 'vertical',
      'image' =>
        [
          'src' => null,
          'display' => 'scale',
        ],
      'styles' =>
        [
          'block' =>
            [
              'backgroundColor' => 'transparent',
            ],
        ],
      'blocks' => [],
    ],
    'globalStyles' => [
      "text" => [
        "fontColor" => "#000000",
        "fontFamily" => "Arial",
        "fontSize" => "16px",
        "lineHeight" => "1.6",
      ],
      "h1" => [
        "fontColor" => "#111111",
        "fontFamily" => "Arial",
        "fontSize" => "40px",
        "lineHeight" => "1.6",
      ],
      "h2" => [
        "fontColor" => "#222222",
        "fontFamily" => "Tahoma",
        "fontSize" => "32px",
        "lineHeight" => "1.6",
      ],
      "h3" => [
        "fontColor" => "#333333",
        "fontFamily" => "Verdana",
        "fontSize" => "24px",
        "lineHeight" => "1.6",
      ],
      "link" => [
        "fontColor" => "#21759B",
        "textDecoration" => "underline",
      ],
      "wrapper" => [
        "backgroundColor" => "#ffffff",
      ],
      "body" => [
        "backgroundColor" => "#ffffff",
      ],
    ],
  ];

  /**
   * @param Renderer $renderer
   */
  public function __construct(
    Renderer $renderer
  ) {
    $this->renderer = $renderer;
  }

  public function render(string $gutHtml): string {
    $blocks = parse_blocks($gutHtml);
    $mappedContent = $this->wrap;
    $mappedBlocks = $this->mapBlocks($blocks);
    codecept_debug($mappedBlocks);
    $mappedContent['content']['blocks'] = $this->addTopLevelLayouts($mappedBlocks);
    $newsletter = new NewsletterEntity();
    $newsletter->setBody($mappedContent);
    codecept_debug($mappedContent);
    return $this->renderer->renderAsPreview($newsletter)['html'];
  }

  private function mapBlocks($blocks): array {
    $result = [];
    foreach ($blocks as $block) {
      if (empty($block['blockName'])) {
        continue;
      }
      if ($block['blockName'] === 'core/columns') {
        $columns = $this->columnsWrap;
        foreach ($block['innerBlocks'] as $column) {
          $col = $this->columnWrap;
          $col['blocks'] = $this->mapBlocks($column['innerBlocks']);
          $columns['blocks'][] = $col;
        }
        $result[] = $columns;
      } else {
        $result[] = $block['attrs']['legacyBlockData'];
      }
    }
    return $result;
  }

  private function addTopLevelLayouts(array $mappedBlocks): array {
    $buffer = [];
    $result = [];
    foreach ($mappedBlocks as $block) {
      if ($block['type'] === 'container') {
        if ($buffer) {
          $columns = $this->columnsWrap;
          $columns['blocks'][] = $this->columnWrap;
          $columns['blocks'][0]['blocks'] = $buffer;
          $buffer = [];
          $result[] = $columns;
        }
        $result[] = $block;
      } else {
        $buffer[] = $block;
      }
    }
    if ($buffer) {
      $columns = $this->columnsWrap;
      $columns['blocks'][] = $this->columnWrap;
      $columns['blocks'][0]['blocks'] = $buffer;
      $result[] = $columns;
    }
    return $result;
  }
}
