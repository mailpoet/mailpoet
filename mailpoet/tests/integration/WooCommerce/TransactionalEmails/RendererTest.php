<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use Codeception\Stub;
use MailPoet\Config\ServicesChecker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoetVendor\csstidy;

class RendererTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->newsletter = new NewsletterEntity();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletter->setSubject('WooCommerce Transactional Email');
    $this->newsletter->setType(Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL);
    $this->newsletter->setPreheader('');
    $this->newsletter->setBody([
      'content' => L::col([
        L::row([L::col([['type' => 'text', 'text' => 'Some text before heading']])]),
        ['type' => 'woocommerceHeading'],
        L::row([L::col([['type' => 'text', 'text' => 'Some text between heading and content']])]),
        ['type' => 'woocommerceContent'],
        L::row([L::col([['type' => 'text', 'text' => 'Some text after content']])]),
      ]),
    ]);
  }

  public function testGetHTMLBeforeContent() {
    $renderer = new Renderer(new csstidy, $this->getNewsletterRenderer());
    $renderer->render($this->newsletter, 'Heading Text');
    $html = $renderer->getHTMLBeforeContent();
    expect($html)->stringContainsString('Some text before heading');
    expect($html)->stringContainsString('Heading Text');
    expect($html)->stringContainsString('Some text between heading and content');
    expect($html)->stringNotContainsString('Some text after content');
  }

  public function testGetHTMLAfterContent() {
    $renderer = new Renderer(new csstidy, $this->getNewsletterRenderer());
    $renderer->render($this->newsletter, 'Heading Text');
    $html = $renderer->getHTMLAfterContent();
    expect($html)->stringNotContainsString('Some text before heading');
    expect($html)->stringNotContainsString('Heading Text');
    expect($html)->stringNotContainsString('Some text between heading and content');
    expect($html)->stringContainsString('Some text after content');
  }

  public function testRenderHeadingTextWhenHeadingBlockMovedToFooter() {
    $this->newsletter->setBody([
      'content' => L::col([
        L::row([L::col([['type' => 'text', 'text' => 'Some text before heading']])]),
        L::row([L::col([['type' => 'text', 'text' => 'Some text between heading and content']])]),
        ['type' => 'woocommerceContent'],
        ['type' => 'woocommerceHeading'],
        L::row([L::col([['type' => 'text', 'text' => 'Some text after content']])]),
      ]),
    ]);
    $this->newslettersRepository->persist($this->newsletter);
    $renderer = new Renderer(new csstidy, $this->getNewsletterRenderer());
    $renderer->render($this->newsletter, 'Heading Text');
    $html = $renderer->getHTMLAfterContent();
    expect($html)->stringContainsString('Heading Text');
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

  private function getNewsletterRenderer(): NewsletterRenderer {
    $wooPreprocessor = new ContentPreprocessor(Stub::make(
      \MailPoet\WooCommerce\TransactionalEmails::class,
      [
        'getWCEmailSettings' => [
          'base_text_color' => '',
          'base_color' => '',
        ],
      ]
    ));
    return new NewsletterRenderer(
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\Renderer::class),
      $this->diContainer->get(\MailPoet\Newsletter\Renderer\Columns\Renderer::class),
      new Preprocessor(
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent::class),
        $this->diContainer->get(\MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock::class),
        $wooPreprocessor
      ),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(ServicesChecker::class)
    );
  }
}
