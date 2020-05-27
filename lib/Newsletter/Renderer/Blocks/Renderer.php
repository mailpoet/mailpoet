<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  public $posts;
  public $ALC;

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
    $this->posts = [];
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

        $lastPost = $this->newsletterPostsRepository->findOneBy(['newsletter' => $newsletter], ['createdAt' => 'desc']);
        if ($lastPost instanceof NewsletterPostEntity) {
          $newerThanTimestamp = $lastPost->getCreatedAt();
        }

      }
    }
    $postsToExclude = $this->getPosts();
    $aLCPosts = $this->ALC->getPosts($args, $postsToExclude, $newsletterId, $newerThanTimestamp);
    foreach ($aLCPosts as $post) {
      $postsToExclude[] = $post->ID;
    }
    $this->setPosts($postsToExclude);
    return $this->ALC->transformPosts($args, $aLCPosts);
  }

  public function processAutomatedLatestContent(NewsletterEntity $newsletter, $args, $columnBaseWidth) {
    $transformedPosts = [
      'blocks' => $this->automatedLatestContentTransformedPosts($newsletter, $args),
    ];
    $transformedPosts = StylesHelper::applyTextAlignment($transformedPosts);
    return $this->renderBlocksInColumn($newsletter, $transformedPosts, $columnBaseWidth);
  }

  public function getPosts() {
    return $this->posts;
  }

  public function setPosts($posts) {
    return $this->posts = $posts;
  }
}
