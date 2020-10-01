<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Tasks\Sending as SendingTask;

class Renderer {
  /**
   * Cache for rendered posts in newsletter.
   * Used to prevent duplicate post in case a newsletter contains 2 ALC blocks
   * @var array
   */
  public $renderedPostsInNewsletter;

  /** @var AutomatedLatestContent  */
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

  /** @var NewsletterPostsRepository */
  private $newsletterPostsRepository;

  public function __construct(
    NewsletterPostsRepository $newsletterPostsRepository,
    AutomatedLatestContent $ALC,
    Button $button,
    Divider $divider,
    Footer $footer,
    Header $header,
    Image $image,
    Social $social,
    Spacer $spacer,
    Text $text
  ) {
    $this->renderedPostsInNewsletter = [];
    $this->ALC = $ALC;
    $this->button = $button;
    $this->divider = $divider;
    $this->footer = $footer;
    $this->header = $header;
    $this->image = $image;
    $this->social = $social;
    $this->spacer = $spacer;
    $this->text = $text;
    $this->newsletterPostsRepository = $newsletterPostsRepository;
  }

  public function render(NewsletterEntity $newsletter, $data) {
    $columnCount = count($data['blocks']);
    $columnsLayout = isset($data['columnLayout']) ? $data['columnLayout'] : null;
    $columnWidths = ColumnsHelper::columnWidth($columnCount, $columnsLayout);
    $columnContent = [];

    foreach ($data['blocks'] as $index => $columnBlocks) {
      $renderedBlockElement = $this->renderBlocksInColumn($newsletter, $columnBlocks, $columnWidths[$index]);
      $columnContent[] = $renderedBlockElement;
    }

    return $columnContent;
  }

  private function renderBlocksInColumn(NewsletterEntity $newsletter, $block, $columnBaseWidth) {
    $blockContent = '';
    $_this = $this;
    array_map(function($block) use (&$blockContent, $columnBaseWidth, $newsletter, $_this) {
      $renderedBlockElement = $_this->createElementFromBlockType($newsletter, $block, $columnBaseWidth);
      if (isset($block['blocks'])) {
        $renderedBlockElement = $_this->renderBlocksInColumn($newsletter, $block, $columnBaseWidth);
        // nested vertical column container is rendered as an array
        if (is_array($renderedBlockElement)) {
          $renderedBlockElement = implode('', $renderedBlockElement);
        }
      }

      $blockContent .= $renderedBlockElement;
    }, $block['blocks']);
    return $blockContent;
  }

  public function createElementFromBlockType(NewsletterEntity $newsletter, $block, $columnBaseWidth) {
    if ($block['type'] === 'automatedLatestContent') {
      return $this->processAutomatedLatestContent($newsletter, $block, $columnBaseWidth);
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
    }
    return '';
  }

  public function automatedLatestContentTransformedPosts(NewsletterEntity $newsletter, $args) {
    $newerThanTimestamp = false;
    $newsletterId = false;
    if ($newsletter->getType() === Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $parent = $newsletter->getParent();
      if ($parent instanceof NewsletterEntity) {
        $newsletterId = $parent->getId();

        $lastPost = $this->newsletterPostsRepository->findOneBy(['newsletter' => $parent], ['createdAt' => 'desc']);
        if ($lastPost instanceof NewsletterPostEntity) {
          $newerThanTimestamp = $lastPost->getCreatedAt();
        }

      }
    }
    $postsToExclude = $this->getRenderedPosts((int)$newsletterId);
    $aLCPosts = $this->ALC->getPosts($args, $postsToExclude, $newsletterId, $newerThanTimestamp);
    foreach ($aLCPosts as $post) {
      $postsToExclude[] = $post->ID;
    }
    $this->setRenderedPosts((int)$newsletterId, $postsToExclude);
    return $this->ALC->transformPosts($args, $aLCPosts);
  }

  public function processAutomatedLatestContent(NewsletterEntity $newsletter, $args, $columnBaseWidth) {
    $transformedPosts = [
      'blocks' => $this->automatedLatestContentTransformedPosts($newsletter, $args),
    ];
    $transformedPosts = StylesHelper::applyTextAlignment($transformedPosts);
    return $this->renderBlocksInColumn($newsletter, $transformedPosts, $columnBaseWidth);
  }

  public function abandonedCartContentTransformedProducts(
    NewsletterEntity $newsletter,
    array $args,
    bool $preview = false,
    SendingTask $sendingTask = null
  ): array {
    if ($newsletter->getType() !== NewsletterEntity::TYPE_AUTOMATIC) {
      // Do not display the block if not an automatic email
      return [];
    }
    $groupOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'group';
    })->first();
    $eventOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'event';
    })->first();
    if ($groupOption->getValue() !== WooCommerceEmail::SLUG
      || $eventOption->getValue() !== AbandonedCart::SLUG
    ) {
      // Do not display the block if not an AbandonedCart email
      return [];
    }
    if ($preview) {
      // Display latest products for preview (no 'posts' argument specified)
      return $this->automatedLatestContentTransformedPosts($newsletter, $args);
    }
    if (!($sendingTask instanceof SendingTask)) {
      // Do not display the block if we're not sending an email
      return [];
    }
    $meta = $sendingTask->getMeta();
    if (empty($meta[AbandonedCart::TASK_META_NAME])) {
      // Do not display the block if a cart is empty
      return [];
    }
    $args['amount'] = 50;
    $args['posts'] = $meta[AbandonedCart::TASK_META_NAME];
    return $this->automatedLatestContentTransformedPosts($newsletter, $args);
  }

  private function getRenderedPosts(int $newsletterId) {
    return $this->renderedPostsInNewsletter[$newsletterId] ?? [];
  }

  private function setRenderedPosts(int $newsletterId, array $posts) {
    return $this->renderedPostsInNewsletter[$newsletterId] = $posts;
  }
}
