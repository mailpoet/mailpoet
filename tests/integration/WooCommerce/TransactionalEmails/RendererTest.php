<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use Codeception\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoetVendor\csstidy;

class RendererTest extends \MailPoetTest {
  /** @var Newsletter */
  private $newsletter;

  public function _before() {
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

  public function testGetHTMLBeforeContent() {
    $renderer = new Renderer(new csstidy);
    $newsletterRenderer = new NewsletterRenderer();
    $newsletterRenderer->preprocessor = new Preprocessor(
      $newsletterRenderer->blocksRenderer,
      Stub::make(
        \MailPoet\WooCommerce\TransactionalEmails::class,
        [
          'getWCEmailSettings' => [
            'base_text_color' => '',
            'base_color' => '',
          ],
        ]
      )
    );
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLBeforeContent('Heading Text');
    expect($html)->contains('Some text before heading');
    expect($html)->contains('Heading Text');
    expect($html)->contains('Some text between heading and content');
    expect($html)->notContains('Some text after content');
  }

  public function testGetHTMLAfterContent() {
    $newsletterRenderer = new NewsletterRenderer();
    $renderer = new Renderer(new csstidy, $newsletterRenderer);
    $newsletterRenderer->preprocessor = new Preprocessor(
      $newsletterRenderer->blocksRenderer,
      Stub::make(
        \MailPoet\WooCommerce\TransactionalEmails::class,
        [
          'getWCEmailSettings' => [
            'base_text_color' => '',
            'base_color' => '',
          ],
        ]
      )
    );
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLAfterContent();
    expect($html)->notContains('Some text before heading');
    expect($html)->notContains('Heading Text');
    expect($html)->notContains('Some text between heading and content');
    expect($html)->contains('Some text after content');
  }

  public function testPrefixCss() {
    $renderer = new Renderer(new csstidy);
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
