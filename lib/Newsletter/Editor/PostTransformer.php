<?php

namespace MailPoet\Newsletter\Editor;

class PostTransformer {
  /** @var PostTransformerContentsExtractor */
  private $extractor;

  /** @var array */
  private $args;
  /** @var bool */
  private $with_layout;
  /** @var string */
  private $image_position;

  function __construct($args, PostTransformerContentsExtractor $extractor = null) {
    $this->args = $args;
    $this->with_layout = isset($args['withLayout']) ? (bool)filter_var($args['withLayout'], FILTER_VALIDATE_BOOLEAN) : false;
    $this->image_position = 'left';
    if ($extractor === null) {
      $extractor = new PostTransformerContentsExtractor($args);
    }
    $this->extractor = $extractor;
  }

  function getDivider() {
    if (empty($this->with_layout)) {
      return $this->args['divider'];
    }
    return LayoutHelper::row([
      LayoutHelper::col([$this->args['divider']]),
    ]);
  }

  function transform($post) {
    if (empty($this->with_layout)) {
      return $this->getStructure($post);
    }
    return $this->getStructureWithLayout($post);
  }

  private function getStructure($post) {
    $content = $this->extractor->getContent($post, true, $this->args['displayType']);
    $title = $this->extractor->getTitle($post);
    $featured_image = $this->extractor->getFeaturedImage($post);
    $featured_image_position = $this->args['featuredImagePosition'];

    if (
      $featured_image
      && $featured_image_position === 'belowTitle'
      && (
        $this->args['displayType'] === 'excerpt'
        || $this->extractor->isProduct($post)
      )
    ) {
      array_unshift($content, $title, $featured_image);
      return $content;
    }

    if ($content[0]['type'] === 'text') {
      $content[0]['text'] = $title['text'] . $content[0]['text'];
    } else {
      array_unshift($content, $title);
    }

    if ($featured_image && $this->args['displayType'] === 'excerpt') {
      array_unshift($content, $featured_image);
    }

    return $content;
  }

  private function getStructureWithLayout($post) {
    $with_post_class = $this->args['displayType'] === 'full';
    $content = $this->extractor->getContent($post, $with_post_class, $this->args['displayType']);
    $title = $this->extractor->getTitle($post);
    $featured_image = $this->extractor->getFeaturedImage($post);

    $featured_image_position = $this->args['featuredImagePosition'];

    if (
      !$featured_image
      || $featured_image_position === 'none'
      || (
        $this->args['displayType'] !== 'excerpt'
        && !$this->extractor->isProduct($post)
      )
    ) {
      array_unshift($content, $title);

      return [
        LayoutHelper::row([
          LayoutHelper::col($content),
        ]),
      ];
    }
    $title_position = isset($this->args['titlePosition']) ? $this->args['titlePosition'] : '';

    if ($featured_image_position === 'aboveTitle' || $featured_image_position === 'belowTitle') {
      $featured_image_position = 'centered';
    }

    if ($featured_image_position === 'centered') {
      if ($title_position === 'aboveExcerpt') {
        array_unshift($content, $featured_image, $title);
      } else {
        array_unshift($content, $title, $featured_image);
      }
      return [
        LayoutHelper::row([
          LayoutHelper::col($content),
        ]),
      ];
    }

    if ($title_position === 'aboveExcerpt') {
      array_unshift($content, $title);
    }

    if ($featured_image_position === 'alternate') {
      $featured_image_position = $this->nextImagePosition();
    }

    $content = ($featured_image_position === 'left')
      ? [
        LayoutHelper::col([$featured_image]),
        LayoutHelper::col($content),
      ]
      : [
        LayoutHelper::col($content),
        LayoutHelper::col([$featured_image]),
      ];

    $result = [
      LayoutHelper::row($content),
    ];

    if ($title_position !== 'aboveExcerpt') {
      array_unshift(
        $result,
        LayoutHelper::row(
          [
            LayoutHelper::col([$title]),
          ]
        )
      );
    }

    return $result;
  }

  private function nextImagePosition() {
    $this->image_position = ($this->image_position === 'left') ? 'right' : 'left';
    return $this->image_position;
  }
}
