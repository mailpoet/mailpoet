<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;

class BodyRenderer {
  /** @var Blocks\Renderer */
  private $blocksRenderer;

  /** @var Columns\Renderer */
  private $columnsRenderer;

  public function __construct(
    Blocks\Renderer $blocksRenderer,
    Columns\Renderer $columnsRenderer
  ) {
    $this->blocksRenderer = $blocksRenderer;
    $this->columnsRenderer = $columnsRenderer;
  }

  /*
    - Preview parameters is needed for proper rendering of the AbandonedCart block
    - We need to pass SendingQueueEntity as a parameter to renderBody method because it allows us rendering AbandonedCart block inside a column block
   */
  public function renderBody(NewsletterEntity $newsletter, array $content, bool $preview, SendingQueueEntity $sendingQueue = null): string {
    $blocks = (array_key_exists('blocks', $content))
      ? $content['blocks']
      : [];

    $renderedContent = [];
    foreach ($blocks as $contentBlock) {
      $columnsData = $this->blocksRenderer->render($newsletter, $contentBlock, $preview, $sendingQueue);

      $renderedContent[] = $this->columnsRenderer->render(
        $contentBlock,
        $columnsData
      );
    }
    return implode('', $renderedContent);
  }
}
