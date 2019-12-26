<?php

namespace MailPoet\Test\Newsletter\Editor;

use Codeception\Stub\Expected;
use MailPoet\Newsletter\Editor\PostTransformer;
use MailPoet\Newsletter\Editor\PostTransformerContentsExtractor;

class PostContentTransformerTest extends \MailPoetTest {
  /** @var array */
  private $content_mock;

  /** @var array */
  private $title_mock;

  /** @var array */
  private $image_mock;

  public function _before() {
    parent::_before();
    $this->content_mock = [
      [
        'type' => 'button',
        'text' => 'foo',
      ],
    ];
    $this->title_mock = [
      'text' => 'Title',
    ];
    $this->image_mock = [
      'type' => 'image',
    ];
  }

  public function testShouldAddImageAboveTitleForExcerptWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->image_mock, $this->title_mock, $this->content_mock[0]]);
  }

  public function testShouldAddImageBelowTitleForExcerptWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->title_mock, $this->image_mock, $this->content_mock[0]]);
  }

  public function testShouldTransformContentWithoutLayoutWhenImageIsMissing() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, null);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->title_mock, $this->content_mock[0]]);
  }

  public function testShouldNotAddImageForTitleOnlyWhenImageIsPresentWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->title_mock, $this->content_mock[0]]);
  }

  public function testShouldPrependTitleTextToContentTextIfFirstContentBlockIsTextual() {
    $args = [
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $this->content_mock[0]['type'] = 'text';

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    $expected = $this->content_mock[0];
    $expected['text'] = 'Titlefoo';
    expect($result)->equals([$expected]);
  }

  public function testShouldCreateLayoutStructureForCenteredImageWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['type'])->equals('container');
    expect($result[0]['orientation'])->equals('horizontal');
    expect($result[0]['styles'])->notEmpty();
    expect($result[0]['blocks'][0]['type'])->equals('container');
    expect($result[0]['blocks'][0]['orientation'])->equals('vertical');
    expect($result[0]['blocks'][0]['styles'])->notEmpty();
    $result_blocks = $result[0]['blocks'][0]['blocks'];
    expect(count($result_blocks))->equals(3);
    expect($result_blocks[0]['text'])->equals('Title');
    expect($result_blocks[1]['type'])->equals('image');
  }

  public function testShouldCreateLayoutStructureForCenteredImageWithLayoutWithTitleAboveExcerpt() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
      'titlePosition' => 'aboveExcerpt',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    $result_blocks = $result[0]['blocks'][0]['blocks'];
    expect(count($result_blocks))->equals(3);
    expect($result_blocks[0])->equals($this->image_mock);
    expect($result_blocks[1])->equals($this->title_mock);

  }

  public function testShouldCreateLayoutStructureForOtherThanCenteredPositionedImageWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'alternate',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['type'])->equals('container');
    expect($result[0]['orientation'])->equals('horizontal');
    expect($result[0]['styles'])->notEmpty();
    expect($result[0]['blocks'][0]['type'])->equals('container');
    expect($result[0]['blocks'][0]['orientation'])->equals('vertical');
    expect($result[0]['blocks'][0]['styles'])->notEmpty();
    expect(count($result[0]['blocks'][0]['blocks']))->equals(1);
    expect(count($result[1]['blocks']))->equals(2);
  }

  public function testShouldAddCenteredImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->title_mock, $this->image_mock, $this->content_mock[0]]);
  }

  public function testShouldHandleOldStructureImagePositionValueAndAddImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->title_mock, $this->image_mock, $this->content_mock[0]]);
  }

  public function testShouldAddLeftPositionedImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'left',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->title_mock]);
    expect($result[1]['blocks'][0]['blocks'])->equals([$this->image_mock]);
    expect($result[1]['blocks'][1]['blocks'])->equals([$this->content_mock[0]]);
  }

  public function testShouldAddLeftPositionedImageForExcerptWithTitleAboveExcerpt() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'left',
      'titlePosition' => 'aboveExcerpt',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->image_mock]);
    expect($result[0]['blocks'][1]['blocks'][0])->equals($this->title_mock);
    expect($result[0]['blocks'][1]['blocks'][1])->equals($this->content_mock[0]);
  }

  public function testShouldAddRightPositionedImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'right',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->title_mock]);
    expect($result[1]['blocks'][0]['blocks'])->equals([$this->content_mock[0]]);
    expect($result[1]['blocks'][1]['blocks'])->equals([$this->image_mock]);
  }

  public function testShouldNotAddImageForTitleOnlyWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->title_mock, $this->content_mock[0]]);
  }

  public function testShouldAddClassToParagraphsInFullPostsWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'full',
      'featuredImagePosition' => 'right',
    ];

    $post = [];
    $expected_with_post_class = true;

    $extractor = $this->make(
      PostTransformerContentsExtractor::class,
      [
        'getContent' => Expected::once($this->content_mock),
        'getFeaturedImage' => null,
        'getTitle' => 'Title',
      ],
      $this
    );
    $extractor->expects($this->once())
      ->method('getContent')
      ->with(
        $this->equalTo($post),
        $this->equalTo($expected_with_post_class),
        $this->equalTo('full')
      );

    $transformer = new PostTransformer($args, $extractor);
    $transformer->transform($post);
  }

  public function testShouldNotAddClassToParagraphsInExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'right',
    ];

    $post = [];
    $expected_with_post_class = false;

    $extractor = $this->make(
      PostTransformerContentsExtractor::class,
      [
        'getContent' => Expected::once($this->content_mock),
        'getFeaturedImage' => null,
        'getTitle' => 'Title',
      ],
      $this
    );
    $extractor->expects($this->once())
      ->method('getContent')
      ->with(
        $this->equalTo($post),
        $this->equalTo($expected_with_post_class),
        $this->equalTo('excerpt')
      );

    $transformer = new PostTransformer($args, $extractor);
    $transformer->transform($post);
  }

  /**
   * @return PostTransformer
   */
  private function getTransformer(array $args, array $content, array $title, array $image = null) {
    $extractor = $this->make(
      PostTransformerContentsExtractor::class,
      [
        'getContent' => $content,
        'getFeaturedImage' => $image,
        'getTitle' => $title,
        'isProduct' => false,
      ],
      $this
    );
    $transformer = new PostTransformer($args, $extractor);
    return $transformer;
  }
}
