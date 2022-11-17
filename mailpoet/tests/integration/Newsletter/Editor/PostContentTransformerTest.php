<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use Codeception\Stub\Expected;
use MailPoet\Newsletter\Editor\PostTransformer;
use MailPoet\Newsletter\Editor\PostTransformerContentsExtractor;
use PHPUnit\Framework\MockObject\MockObject;

class PostContentTransformerTest extends \MailPoetTest {
  /** @var array */
  private $contentMock;

  /** @var array */
  private $titleMock;

  /** @var array */
  private $imageMock;

  public function _before() {
    parent::_before();
    $this->contentMock = [
      [
        'type' => 'button',
        'text' => 'foo',
      ],
    ];
    $this->titleMock = [
      'text' => 'Title',
    ];
    $this->imageMock = [
      'type' => 'image',
    ];
  }

  public function testShouldAddImageAboveTitleForExcerptWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->imageMock, $this->titleMock, $this->contentMock[0]]);
  }

  public function testShouldAddImageBelowTitleForExcerptWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->titleMock, $this->imageMock, $this->contentMock[0]]);
  }

  public function testShouldTransformContentWithoutLayoutWhenImageIsMissing() {
    $args = [
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, null);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->titleMock, $this->contentMock[0]]);
  }

  public function testShouldNotAddImageForTitleOnlyWhenImageIsPresentWithoutLayout() {
    $args = [
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result)->equals([$this->titleMock, $this->contentMock[0]]);
  }

  public function testShouldPrependTitleTextToContentTextIfFirstContentBlockIsTextual() {
    $args = [
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $this->contentMock[0]['type'] = 'text';

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    $expected = $this->contentMock[0];
    $expected['text'] = 'Titlefoo';
    expect($result)->equals([$expected]);
  }

  public function testShouldCreateLayoutStructureForCenteredImageWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['type'])->equals('container');
    expect($result[0]['orientation'])->equals('horizontal');
    expect($result[0]['styles'])->notEmpty();
    expect($result[0]['blocks'][0]['type'])->equals('container');
    expect($result[0]['blocks'][0]['orientation'])->equals('vertical');
    expect($result[0]['blocks'][0]['styles'])->notEmpty();
    $resultBlocks = $result[0]['blocks'][0]['blocks'];
    expect(count($resultBlocks))->equals(3);
    expect($resultBlocks[0]['text'])->equals('Title');
    expect($resultBlocks[1]['type'])->equals('image');
  }

  public function testShouldCreateLayoutStructureForCenteredImageWithLayoutWithTitleAboveExcerpt() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
      'titlePosition' => 'aboveExcerpt',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    $resultBlocks = $result[0]['blocks'][0]['blocks'];
    expect(count($resultBlocks))->equals(3);
    expect($resultBlocks[0])->equals($this->imageMock);
    expect($resultBlocks[1])->equals($this->titleMock);

  }

  public function testShouldCreateLayoutStructureForOtherThanCenteredPositionedImageWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'alternate',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
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

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->titleMock, $this->imageMock, $this->contentMock[0]]);
  }

  public function testShouldHandleOldStructureImagePositionValueAndAddImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->titleMock, $this->imageMock, $this->contentMock[0]]);
  }

  public function testShouldAddLeftPositionedImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'left',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->titleMock]);
    expect($result[1]['blocks'][0]['blocks'])->equals([$this->imageMock]);
    expect($result[1]['blocks'][1]['blocks'])->equals([$this->contentMock[0]]);
  }

  public function testShouldAddLeftPositionedImageForExcerptWithTitleAboveExcerpt() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'left',
      'titlePosition' => 'aboveExcerpt',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->imageMock]);
    expect($result[0]['blocks'][1]['blocks'][0])->equals($this->titleMock);
    expect($result[0]['blocks'][1]['blocks'][1])->equals($this->contentMock[0]);
  }

  public function testShouldAddRightPositionedImageForExcerptWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'right',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->titleMock]);
    expect($result[1]['blocks'][0]['blocks'])->equals([$this->contentMock[0]]);
    expect($result[1]['blocks'][1]['blocks'])->equals([$this->imageMock]);
  }

  public function testShouldNotAddImageForTitleOnlyWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'])->equals([$this->titleMock, $this->contentMock[0]]);
  }

  public function testShouldAddImageForFullPost() {
    $args = [
      'withLayout' => true,
      'displayType' => 'full',
      'fullPostFeaturedImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'][0])->equals($this->titleMock);
    expect($result[0]['blocks'][0]['blocks'][1])->equals($this->imageMock);
    expect($result[0]['blocks'][0]['blocks'][2])->equals($this->contentMock[0]);
    expect($result[0]['blocks'][0]['blocks'])->count(3);
  }

  public function testShouldNotAddImageForExistingFullPost() {
    $args = [
      'withLayout' => true,
      'displayType' => 'full',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'][0])->equals($this->titleMock);
    expect($result[0]['blocks'][0]['blocks'][1])->equals($this->contentMock[0]);
    expect($result[0]['blocks'][0]['blocks'])->count(2);
  }

  public function testShouldAddImageForExistingFullPostProduct() {
    $args = [
      'withLayout' => true,
      'displayType' => 'full',
      'featuredImagePosition' => 'centered',
    ];

    $transformer = $this->getTransformer($args, $this->contentMock, $this->titleMock, $this->imageMock, true);
    $result = $transformer->transform([]);
    expect($result[0]['blocks'][0]['blocks'][0])->equals($this->titleMock);
    expect($result[0]['blocks'][0]['blocks'][1])->equals($this->imageMock);
    expect($result[0]['blocks'][0]['blocks'][2])->equals($this->contentMock[0]);
    expect($result[0]['blocks'][0]['blocks'])->count(3);
  }

  public function testShouldAddClassToParagraphsInFullPostsWithLayout() {
    $args = [
      'withLayout' => true,
      'displayType' => 'full',
      'featuredImagePosition' => 'right',
    ];

    $post = (object)[
      'post_type' => 'post',
    ];
    $expectedWithPostClass = true;

    /** @var PostTransformerContentsExtractor&MockObject $extractor */
    $extractor = $this->make(
      PostTransformerContentsExtractor::class,
      [
        'getContent' => Expected::once($this->contentMock),
        'getFeaturedImage' => null,
        'getTitle' => 'Title',
      ]
    );
    $extractor->expects($this->once())
      ->method('getContent')
      ->with(
        $this->equalTo($post),
        $this->equalTo($expectedWithPostClass),
        $this->equalTo('full')
      );

    $transformer = new PostTransformer($args, $extractor);
    $transformer->transform($post);
  }

  /**
   * @return PostTransformer
   */
  private function getTransformer(array $args, array $content, array $title, array $image = null, bool $isProduct = false) {
    $extractor = $this->make(
      PostTransformerContentsExtractor::class,
      [
        'getContent' => $content,
        'getFeaturedImage' => $image,
        'getTitle' => $title,
        'isProduct' => $isProduct,
      ]
    );
    $transformer = new PostTransformer($args, $extractor);
    return $transformer;
  }
}
