<?php
namespace MailPoet\Test\Newsletter\Editor;

use AspectMock\Test as Mock;
use MailPoet\Newsletter\Editor\PostTransformer;

class PostContentTransformerTest extends \MailPoetTest {
  /** @var array */
  private $content_mock;

  /** @var array */
  private $title_mock;

  /** @var array */
  private $image_mock;

  function _before() {
    $this->content_mock = array(
      array(
        'type' => 'button',
        'text' => 'foo',
      ),
    );
    $this->title_mock = array(
      'text' => 'Title',
    );
    $this->image_mock = array(
      'type' => 'image',
    );
  }

  function testShouldAddImageAboveTitleForExcerptWithoutLayout() {
    $args = array (
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result)->equals(array($this->image_mock, $this->title_mock, $this->content_mock[0]));
  }

  function testShouldAddImageBelowTitleForExcerptWithoutLayout() {
    $args = array (
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result)->equals(array($this->title_mock, $this->image_mock, $this->content_mock[0]));
  }

  function testShouldTransformContentWithoutLayoutWhenImageIsMissing() {
    $args = array (
      'withLayout' => false,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'belowTitle',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, null);
    $result = $transformer->transform(array());
    expect($result)->equals(array($this->title_mock, $this->content_mock[0]));
  }

  function testShouldNotAddImageForTitleOnlyWhenImageIsPresentWithoutLayout() {
    $args = array (
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result)->equals(array($this->title_mock, $this->content_mock[0]));
  }

  function testShouldPrependTitleTextToContentTextIfFirstContentBlockIsTextual() {
    $args = array (
      'withLayout' => false,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'aboveTitle',
    );

    $this->content_mock[0]['type'] = 'text';

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    $expected = $this->content_mock[0];
    $expected['text'] = 'Titlefoo';
    expect($result)->equals(array($expected));
  }

  function testShouldCreateLayoutStructureForCenteredImageWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['type'])->equals('container');
    expect($result[0]['orientation'])->equals('horizontal');
    expect($result[0]['styles'])->notEmpty();
    expect($result[0]['blocks'][0]['type'])->equals('container');
    expect($result[0]['blocks'][0]['orientation'])->equals('vertical');
    expect($result[0]['blocks'][0]['styles'])->notEmpty();
    expect(count($result[0]['blocks'][0]['blocks']))->equals(3);
  }

  function testShouldCreateLayoutStructureForOtherThanCenteredPositionedImageWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'alternate',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['type'])->equals('container');
    expect($result[0]['orientation'])->equals('horizontal');
    expect($result[0]['styles'])->notEmpty();
    expect($result[0]['blocks'][0]['type'])->equals('container');
    expect($result[0]['blocks'][0]['orientation'])->equals('vertical');
    expect($result[0]['blocks'][0]['styles'])->notEmpty();
    expect(count($result[0]['blocks'][0]['blocks']))->equals(1);
    expect(count($result[1]['blocks']))->equals(2);
  }

  function testShouldAddCenteredImageForExcerptWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'centered',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['blocks'][0]['blocks'])->equals(array($this->title_mock, $this->image_mock, $this->content_mock[0]));
  }

  function testShouldHandleOldStructureImagePositionValueAndAddImageForExcerptWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'aboveTitle',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['blocks'][0]['blocks'])->equals(array($this->title_mock, $this->image_mock, $this->content_mock[0]));
  }

  function testShouldAddLeftPositionedImageForExcerptWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'left',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['blocks'][0]['blocks'])->equals(array($this->title_mock));
    expect($result[1]['blocks'][0]['blocks'])->equals(array($this->image_mock));
    expect($result[1]['blocks'][1]['blocks'])->equals(array($this->content_mock[0]));
  }

  function testShouldAddRightPositionedImageForExcerptWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'right',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['blocks'][0]['blocks'])->equals(array($this->title_mock));
    expect($result[1]['blocks'][0]['blocks'])->equals(array($this->content_mock[0]));
    expect($result[1]['blocks'][1]['blocks'])->equals(array($this->image_mock));
  }

  function testShouldNotAddImageForTitleOnlyWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'titleOnly',
      'featuredImagePosition' => 'centered',
    );

    $transformer = $this->getTransformer($args, $this->content_mock, $this->title_mock, $this->image_mock);
    $result = $transformer->transform(array());
    expect($result[0]['blocks'][0]['blocks'])->equals(array($this->title_mock, $this->content_mock[0]));
  }

  function testShouldAddClassToParagraphsInFullPostsWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'full',
      'featuredImagePosition' => 'right',
    );

    $post = [];
    $expected_with_post_class = true;

    $transformer = new PostTransformer($args);
    $mock_get_content = Mock::double($transformer, ['getContent' => $this->content_mock]);
    Mock::double($transformer, ['getFeaturedImage' => null]);
    Mock::double($transformer, ['getTitle' => 'Title']);
    $transformer->transform($post);
    $mock_get_content->verifyInvokedOnce('getContent', [$post, $expected_with_post_class, 'full']);
  }

  function testShouldNotAddClassToParagraphsInExcerptWithLayout() {
    $args = array (
      'withLayout' => true,
      'displayType' => 'excerpt',
      'featuredImagePosition' => 'right',
    );

    $post = [];
    $expected_with_post_class = false;

    $transformer = new PostTransformer($args);
    $mock_get_content = Mock::double($transformer, ['getContent' => $this->content_mock]);
    Mock::double($transformer, ['getFeaturedImage' => null]);
    Mock::double($transformer, ['getTitle' => 'Title']);
    $transformer->transform($post);
    $mock_get_content->verifyInvokedOnce('getContent', [$post, $expected_with_post_class, 'excerpt']);
  }

  /**
   * @return PostTransformer
   */
  private function getTransformer(array $args, array $content, array $title, array $image = null) {
    $transformer = new PostTransformer($args);
    Mock::double($transformer, array('getContent' => $content));
    Mock::double($transformer, array('getFeaturedImage' => $image));
    Mock::double($transformer, array('getTitle' => $title));
    return $transformer;
  }

  function _after() {
    Mock::clean();
  }
}