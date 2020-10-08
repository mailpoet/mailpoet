<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use Codeception\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\License;
use MailPoetVendor\csstidy;

class RendererTest extends \MailPoetTest {
  /** @var Newsletter */
  private $newsletter;

  public function _before() {
    parent::_before();
    $this->newsletter = Newsletter::createOrUpdate([
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
    ]);
  }

  public function testGetHTMLBeforeContent() {
    $newsletterRenderer = new NewsletterRenderer(
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\Renderer::class),
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Columns\Renderer::class),
      new Preprocessor(
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent::class),
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock::class),
        Stub::make(
          \MailPoet\WooCommerce\TransactionalEmails::class,
          [
            'getWCEmailSettings' => [
              'base_text_color' => '',
              'base_color' => '',
            ],
          ]
        )
      ),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(License::class)
    );

    $renderer = new Renderer(new csstidy, $newsletterRenderer);
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLBeforeContent('Heading Text');
    expect($html)->stringContainsString('Some text before heading');
    expect($html)->stringContainsString('Heading Text');
    expect($html)->stringContainsString('Some text between heading and content');
    expect($html)->stringNotContainsString('Some text after content');
  }

  public function testGetHTMLAfterContent() {
    $newsletterRenderer = new NewsletterRenderer(
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\Renderer::class),
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Columns\Renderer::class),
      new Preprocessor(
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent::class),
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock::class),
        Stub::make(
          \MailPoet\WooCommerce\TransactionalEmails::class,
          [
            'getWCEmailSettings' => [
              'base_text_color' => '',
              'base_color' => '',
            ],
          ]
        )
      ),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(License::class)
    );
    $renderer = new Renderer(new csstidy, $newsletterRenderer);
    $renderer->render($this->newsletter);
    $html = $renderer->getHTMLAfterContent();
    expect($html)->stringNotContainsString('Some text before heading');
    expect($html)->stringNotContainsString('Heading Text');
    expect($html)->stringNotContainsString('Some text between heading and content');
    expect($html)->stringContainsString('Some text after content');
  }

  public function testPrefixCss() {
    $renderer = new Renderer(new csstidy, $this->diContainer->get(NewsletterRenderer::class));
    $css = $renderer->prefixCss('
      #some_id {color: black}
      .some-class {height: 50px; width: 30px}
      h1 {
        font-weight:bold;
      }
    ');
    expect($css)->stringContainsString("#mailpoet_woocommerce_container #some_id {\ncolor:black\n}");
    expect($css)->stringContainsString("#mailpoet_woocommerce_container .some-class {\nheight:50px;\nwidth:30px\n}");
    expect($css)->stringContainsString("#mailpoet_woocommerce_container h1 {\nfont-weight:700\n}");
  }
}
