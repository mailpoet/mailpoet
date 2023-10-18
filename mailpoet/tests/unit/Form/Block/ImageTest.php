<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Image;
use MailPoet\Form\FormHtmlSanitizer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class ImageTest extends \MailPoetUnitTest {
  /** @var Image */
  private $image;

  /** @var MockObject & WPFunctions */
  private $wpMock;

  /** @var MockObject & FormHtmlSanitizer */
  private $htmlSanitizerMock;

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
      'caption' => '<strong>Caption</strong>',
      'link_destination' => 'none',
      'link' => null,
      'href' => null,
      'link_class' => null,
      'link_target' => null,
      'rel' => null,
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
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->wpMock->method('escHtml')->will($this->returnArgument(0));
    $this->htmlSanitizerMock = $this->createMock(FormHtmlSanitizer::class);
    $this->htmlSanitizerMock->method('sanitize')->will($this->returnArgument(0));
    $this->image = new Image($this->wpMock, $this->htmlSanitizerMock);
    $this->htmlParser = new HtmlParser();

  }

  public function testItShouldRenderImageBlock() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpGetAttachmentImageSrcset')
      ->willReturn('srcsetvalue');
    $html = $this->image->render($this->block);
    $block = $this->htmlParser->getElementByXpath($html, '//div');
    $blockClass = $this->htmlParser->getAttribute($block, 'class');
    verify($blockClass->value)->equals('mailpoet_form_image my-class');

    $figure = $this->htmlParser->getChildElement($block, 'figure');
    $figureClass = $this->htmlParser->getAttribute($figure, 'class');
    verify($figureClass->value)->equals('size-medium alignleft');

    $img = $this->htmlParser->getChildElement($figure, 'img');
    $imgSrc = $this->htmlParser->getAttribute($img, 'src');
    verify($imgSrc->value)->equals('http://example.com/image.jpg');
    $imgSrcset = $this->htmlParser->getAttribute($img, 'srcset');
    verify($imgSrcset->value)->equals('srcsetvalue');
    $imgWidth = $this->htmlParser->getAttribute($img, 'width');
    verify($imgWidth->value)->equals(100);
    $imgHeight = $this->htmlParser->getAttribute($img, 'height');
    verify($imgHeight->value)->equals(200);
    $imgTitle = $this->htmlParser->getAttribute($img, 'title');
    verify($imgTitle->value)->equals('Title');
    $imgAlt = $this->htmlParser->getAttribute($img, 'alt');
    verify($imgAlt->value)->equals('Alt text');
    $style = $this->htmlParser->getAttribute($img, 'style');
    expect($style->value)->stringContainsString('width: 100px');
    expect($style->value)->stringContainsString('height: 200px');

    $caption = $this->htmlParser->getChildElement($figure, 'figcaption');
    $captionContent = $this->htmlParser->getChildElement($caption, 'strong');
    verify($captionContent->textContent)->equals('Caption');
  }

  public function testItShouldRenderImageBlockWithLink() {
    $this->wpMock->expects($this->never())->method('wpGetAttachmentImageSrcset');
    $block = $this->block;
    $block['params']['id'] = null;
    $block['params']['link_class'] = 'link-class';
    $block['params']['link_target'] = '_blank';
    $block['params']['rel'] = 'relrel';
    $block['params']['href'] = 'http://example.com/';
    $html = $this->image->render($block);
    $figure = $this->htmlParser->getElementByXpath($html, '//figure');
    $link = $this->htmlParser->getChildElement($figure, 'a');
    $linkHref = $this->htmlParser->getAttribute($link, 'href');
    verify($linkHref->value)->equals('http://example.com/');
    $linkTarget = $this->htmlParser->getAttribute($link, 'target');
    verify($linkTarget->value)->equals('_blank');
    $linkRel = $this->htmlParser->getAttribute($link, 'rel');
    verify($linkRel->value)->equals('relrel');
    $linkClass = $this->htmlParser->getAttribute($link, 'class');
    verify($linkClass->value)->equals('link-class');
  }

  public function testItRendersNothingWhenUrlIsEmpty() {
    $block = $this->block;
    $block['params']['url'] = null;
    $html = $this->image->render($block);
    verify($html)->equals('');
    $block['params']['url'] = '';
    $html = $this->image->render($block);
    verify($html)->equals('');
  }
}
