<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterPost;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  public $posts;
  public $ALC;

  public function __construct(AutomatedLatestContent $ALC) {
    $this->posts = [];
    $this->ALC = $ALC;
  }

  public function render($newsletter, $data) {
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

  private function renderBlocksInColumn($newsletter, $block, $columnBaseWidth) {
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

  public function createElementFromBlockType($newsletter, $block, $columnBaseWidth) {
    if ($block['type'] === 'automatedLatestContent') {
      $content = $this->processAutomatedLatestContent($newsletter, $block, $columnBaseWidth);
      return $content;
    }
    $block = StylesHelper::applyTextAlignment($block);
    $blockClass = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    if (!class_exists($blockClass)) {
      return '';
    }
    return $blockClass::render($block, $columnBaseWidth);
  }

  public function automatedLatestContentTransformedPosts($newsletter, $args) {
    $newerThanTimestamp = false;
    $newsletterId = false;
    if ($newsletter['type'] === Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $newsletterId = $newsletter['parent_id'];

      $lastPost = NewsletterPost::getNewestNewsletterPost($newsletterId);
      if ($lastPost) {
        $newerThanTimestamp = $lastPost->createdAt;
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

  public function processAutomatedLatestContent($newsletter, $args, $columnBaseWidth) {
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
