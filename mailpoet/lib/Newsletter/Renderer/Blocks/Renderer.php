<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\Columns\Renderer as ColumnsRenderer;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  /** @var AutomatedLatestContentBlock  */
  private $ALC;

  /** @var Button */
  private $button;

  /** @var Divider */
  private $divider;

  /** @var Footer */
  private $footer;

  /** @var Header */
  private $header;

  /** @var Image */
  private $image;

  /** @var Social */
  private $social;

  /** @var Spacer */
  private $spacer;

  /** @var Text */
  private $text;

  /** @var Placeholder */
  private $placeholder;

  /** @var Coupon */
  private $coupon;

  private Preprocessor $preprocessor;
  private ColumnsRenderer $columnsRenderer;

  public function __construct(
    AutomatedLatestContentBlock $ALC,
    Button $button,
    Divider $divider,
    Footer $footer,
    Header $header,
    Image $image,
    Social $social,
    Spacer $spacer,
    Text $text,
    Placeholder $placeholder,
    Preprocessor $preprocessor,
    ColumnsRenderer $columnsRenderer,
    Coupon $coupon
  ) {
    $this->ALC = $ALC;
    $this->button = $button;
    $this->divider = $divider;
    $this->footer = $footer;
    $this->header = $header;
    $this->image = $image;
    $this->social = $social;
    $this->spacer = $spacer;
    $this->text = $text;
    $this->placeholder = $placeholder;
    $this->coupon = $coupon;
    $this->columnsRenderer = $columnsRenderer;
    $this->preprocessor = $preprocessor;
  }

  public function render(NewsletterEntity $newsletter, $data, bool $preview, SendingQueueEntity $sendingQueue = null) {
    if (is_null($data['blocks']) && isset($data['type'])) {
      return null;
    }
    $columnCount = count($data['blocks']);
    $columnsLayout = isset($data['columnLayout']) ? $data['columnLayout'] : null;
    $columnWidths = ColumnsHelper::columnWidth($columnCount, $columnsLayout);
    $columnContent = [];

    foreach ($data['blocks'] as $index => $columnBlocks) {
      $renderedBlockElement = $this->renderBlocksInColumn($newsletter, $columnBlocks, $columnWidths[$index], $preview, $sendingQueue);
      $columnContent[] = $renderedBlockElement;
    }

    return $columnContent;
  }

  private function renderBlocksInColumn(NewsletterEntity $newsletter, $block, $columnBaseWidth, bool $preview, ?SendingQueueEntity $sendingQueue = null) {
    $blockContent = '';
    $_this = $this;
    array_map(function($block) use (&$blockContent, $columnBaseWidth, $newsletter, $preview, $sendingQueue, $_this) {
      $renderedBlockElement = $_this->createElementFromBlockType($newsletter, $block, $columnBaseWidth, $preview, $sendingQueue);
      if (isset($block['blocks'])) {
        $renderedBlockElement = $_this->renderBlocksInColumn($newsletter, $block, $columnBaseWidth, $preview, $sendingQueue);
        // nested vertical column container is rendered as an array
        if (is_array($renderedBlockElement)) {
          $renderedBlockElement = implode('', $renderedBlockElement);
        }
      }

      $blockContent .= $renderedBlockElement;
    }, $block['blocks']);
    return $blockContent;
  }

  public function createElementFromBlockType(NewsletterEntity $newsletter, $block, $columnBaseWidth, bool $preview, ?SendingQueueEntity $sendingQueue) {
    if (
      $block['type'] === 'automatedLatestContentLayout'
      || $block['type'] === 'woocommerceHeading'
      || $block['type'] === 'woocommerceContent'
      || $block['type'] === 'abandonedCartContent'
    ) {
      $preprocessed = $this->preprocessor->processBlock($newsletter, $block, $preview, $sendingQueue);
      return $this->renderContentBlocks($newsletter, $preprocessed, $preview, $sendingQueue);
    }

    if ($block['type'] === 'automatedLatestContent') {
      return $this->processAutomatedLatestContent($newsletter, $block, $columnBaseWidth, $preview, $sendingQueue);
    }
    $block = StylesHelper::applyTextAlignment($block);
    switch ($block['type']) {
      case 'button':
        return $this->button->render($block, $columnBaseWidth);
      case 'divider':
        return $this->divider->render($block);
      case 'footer':
        return $this->footer->render($block);
      case 'header':
        return $this->header->render($block);
      case 'image':
        return $this->image->render($block, $columnBaseWidth);
      case 'social':
        return $this->social->render($block);
      case 'spacer':
        return $this->spacer->render($block);
      case 'text':
        return $this->text->render($block);
      case 'placeholder':
        return $this->placeholder->render($block);
      case Coupon::TYPE:
        return $this->coupon->render($block, $columnBaseWidth);
    }
    return "<!-- Skipped unsupported block type: {$block['type']} -->";
  }

  public function processAutomatedLatestContent(NewsletterEntity $newsletter, $args, $columnBaseWidth, bool $preview, ?SendingQueueEntity $sendingQueue) {
    $transformedPosts = [
      'blocks' => $this->ALC->render($newsletter, $args),
    ];
    $transformedPosts = StylesHelper::applyTextAlignment($transformedPosts);
    return $this->renderBlocksInColumn($newsletter, $transformedPosts, $columnBaseWidth, $preview, $sendingQueue);
  }

  private function renderContentBlocks(NewsletterEntity $newsletter, $contentBlocks, bool $preview, ?SendingQueueEntity $sendingQueue) {
    $renderedContent = [];
    foreach ($contentBlocks as $contentBlock) {
      $columnsData = $this->render($newsletter, $contentBlock, $preview, $sendingQueue);

      $renderedContent[] = $this->columnsRenderer->render(
        $contentBlock,
        $columnsData
      );
    }
    return implode('', $renderedContent);
  }
}
