<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class BodyRenderer {

  /** @var BlocksRenderer */
  private $blocksRenderer;

  public function __construct(
    BlocksRenderer $blocksRenderer
  ) {
    $this->blocksRenderer = $blocksRenderer;
  }

  public function renderBody(string $postContent): string {
    $parser = new \WP_Block_Parser();
    $parsedBlocks = $parser->parse($postContent);
    // @todo We need to wrap top level blocks which are not in columns into a column
    return $this->blocksRenderer->render($parsedBlocks);
  }
}
