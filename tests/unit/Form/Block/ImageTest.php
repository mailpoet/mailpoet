<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Image;
use MailPoet\Test\Form\HtmlParser;

require_once __DIR__ . '/../HtmlParser.php';

class ImageTest extends \MailPoetUnitTest {
  /** @var Image */
  private $image;

  /** @var array  */
  private $block = [
    'type' => 'image',
    'id' => 'image',
    'params' => [
      'class_name' => 'my-class',
      'align' => 'left',
      'url' => 'http://example.com/image.jpg',
      'alt' => 'Alt text',
      'title' => 'Title',
      'caption' => 'Caption',
      'link_destination' => 'none',
      'link' => null,
      'id' => 123,
      'size_slug' => 'medium',
      'width' => 100,
      'height' => 200,
    ],
  ];

  /** @var HtmlParser */
  private $htmlParser;

  public function _before() {
    parent::_before();
    $this->image = new Image();
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderImageBlock() {
    $html = $this->image->render($this->block);
    $block = $this->htmlParser->getElementByXpath($html, '//div');
    $blockClass = $this->htmlParser->getAttribute($block, 'class');
    expect($blockClass->value)->equals('mailpoet_form_image my-class');

    $figure = $this->htmlParser->getChildElement($block, 'figure');
    $figureClass = $this->htmlParser->getAttribute($figure, 'class');
    expect($figureClass->value)->equals('size-medium alignleft');

    $img = $this->htmlParser->getChildElement($figure, 'img');
    $imgSrc = $this->htmlParser->getAttribute($img, 'src');
    expect($imgSrc->value)->equals('http://example.com/image.jpg');
    $imgWidth = $this->htmlParser->getAttribute($img, 'width');
    expect($imgWidth->value)->equals(100);
    $imgHeight = $this->htmlParser->getAttribute($img, 'height');
    expect($imgHeight->value)->equals(200);
    $imgTitle = $this->htmlParser->getAttribute($img, 'title');
    expect($imgTitle->value)->equals('Title');
    $imgAlt = $this->htmlParser->getAttribute($img, 'alt');
    expect($imgAlt->value)->equals('Alt text');

    $caption = $this->htmlParser->getChildElement($figure, 'figcaption');
    expect($caption->textContent)->equals('Caption');
  }

  public function testItRendersNothingWhenUrlIsEmpty() {
    $block = $this->block;
    $block['params']['url'] = null;
    $html = $this->image->render($block);
    expect($html)->equals('');
    $block['params']['url'] = '';
    $html = $this->image->render($block);
    expect($html)->equals('');
  }
}
