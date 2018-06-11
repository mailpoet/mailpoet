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
}