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

  public function renderBody(array $parsedBlocks): string {
    return $this->blocksRenderer->render($parsedBlocks);
  }
}
