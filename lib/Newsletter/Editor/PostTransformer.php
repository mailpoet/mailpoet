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

  public function __construct($args, PostTransformerContentsExtractor $extractor = null) {
    $this->args = $args;
    $this->withLayout = isset($args['withLayout']) ? (bool)filter_var($args['withLayout'], FILTER_VALIDATE_BOOLEAN) : false;
    $this->imagePosition = 'left';
    if ($extractor === null) {
      $extractor = new PostTransformerContentsExtractor($args);
    }
    $this->extractor = $extractor;
  }

  public function getDivider() {
    if (empty($this->withLayout)) {
      return $this->args['divider'];
    }
    return LayoutHelper::row([
      LayoutHelper::col([$this->args['divider']]),
    ]);
  }

  public function transform($post) {
    if (empty($this->withLayout)) {
      return $this->getStructure($post);
    }
    return $this->getStructureWithLayout($post);
  }

  private function getStructure($post) {
    $content = $this->extractor->getContent($post, true, $this->args['displayType']);
    $title = $this->extractor->getTitle($post);
    $featuredImage = $this->extractor->getFeaturedImage($post);
    $featuredImagePosition = $this->args['featuredImagePosition'];

    if (
      $featuredImage
      && $featuredImagePosition === 'belowTitle'
      && (
        $this->args['displayType'] === 'excerpt'
        || $this->extractor->isProduct($post)
      )
    ) {
      array_unshift($content, $title, $featuredImage);
      return $content;
    }

    if ($content[0]['type'] === 'text') {
      $content[0]['text'] = $title['text'] . $content[0]['text'];
    } else {
      array_unshift($content, $title);
    }

    if ($featuredImage && $this->args['displayType'] === 'excerpt') {
      array_unshift($content, $featuredImage);
    }

    return $content;
  }

  private function getStructureWithLayout($post) {
    $withPostClass = $this->args['displayType'] === 'full';
    $content = $this->extractor->getContent($post, $withPostClass, $this->args['displayType']);
    $title = $this->extractor->getTitle($post);
    $featuredImage = $this->extractor->getFeaturedImage($post);

    $featuredImagePosition = $this->args['featuredImagePosition'];

    if (
      !$featuredImage
      || $featuredImagePosition === 'none'
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
    $titlePosition = isset($this->args['titlePosition']) ? $this->args['titlePosition'] : '';

    if ($featuredImagePosition === 'aboveTitle' || $featuredImagePosition === 'belowTitle') {
      $featuredImagePosition = 'centered';
    }

    if ($featuredImagePosition === 'centered') {
      if ($titlePosition === 'aboveExcerpt') {
        array_unshift($content, $featuredImage, $title);
      } else {
        array_unshift($content, $title, $featuredImage);
      }
      return [
        LayoutHelper::row([
          LayoutHelper::col($content),
        ]),
      ];
    }

    if ($titlePosition === 'aboveExcerpt') {
      array_unshift($content, $title);
    }

    if ($featuredImagePosition === 'alternate') {
      $featuredImagePosition = $this->nextImagePosition();
    }

    $content = ($featuredImagePosition === 'left')
      ? [
        LayoutHelper::col([$featuredImage]),
        LayoutHelper::col($content),
      ]
      : [
        LayoutHelper::col($content),
        LayoutHelper::col([$featuredImage]),
      ];

    $result = [
      LayoutHelper::row($content),
    ];

    if ($titlePosition !== 'aboveExcerpt') {
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
    $this->imagePosition = ($this->imagePosition === 'left') ? 'right' : 'left';
    return $this->imagePosition;
  }
}
