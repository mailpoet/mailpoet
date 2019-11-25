<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use Codeception\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;

class RendererTest extends \MailPoetTest {
  /** @var Newsletter */
  private $newsletter;

  function _before() {
    parent::_before();
    $this->newsletter = Stub::make(Newsletter::class, [
      'asArray' => function() {
        return [
          'type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL,
          'subject' => 'WooCommerce Transactional Email',
          'preheader' => '',
          'body' => [
            'content' => L::col([
              L::row([L::col([['type' => 'text', 'text' => 'Some text before heading']])]),
              ['type' => 'woocommerceHeading'],
              L::row([L::col([['type' => 'text', 'text' => 'Some text between heading and content']])]),
              ['type' => 'woocommerceContent'],
              L::row([L::col([['type' => 'text', 'text' => 'Some text after content']])]),
            ]),
          ],
        ];
      },
    ]);
  }

  function testGetHTMLBeforeContent() {
    $renderer = new Renderer(new \csstidy);
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLBeforeContent('Heading Text');
    expect($html)->contains('Some text before heading');
    expect($html)->contains('Heading Text');
    expect($html)->contains('Some text between heading and content');
    expect($html)->notContains('Some text after content');
  }

  function testGetHTMLAfterContent() {
    $renderer = new Renderer(new \csstidy);
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLAfterContent('Heading Text');
    expect($html)->notContains('Some text before heading');
    expect($html)->notContains('Heading Text');
    expect($html)->notContains('Some text between heading and content');
    expect($html)->contains('Some text after content');
  }

  function testPrefixCss() {
    $renderer = new Renderer(new \csstidy);
    $css = $renderer->prefixCss('
      #some_id {color: black}
      .some-class {height: 50px; width: 30px}
      h1 {
        font-weight:bold;
      }
    ');
    expect($css)->contains("#mailpoet_woocommerce_container #some_id {\ncolor:black\n}");
    expect($css)->contains("#mailpoet_woocommerce_container .some-class {\nheight:50px;\nwidth:30px\n}");
    expect($css)->contains("#mailpoet_woocommerce_container h1 {\nfont-weight:700\n}");
  }

}
