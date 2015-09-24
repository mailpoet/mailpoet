<?php

use MailPoet\Newsletter\Renderer\Blocks\Button;
use MailPoet\Newsletter\Renderer\Blocks\Divider;
use MailPoet\Newsletter\Renderer\Blocks\Footer;
use MailPoet\Newsletter\Renderer\Blocks\Header;
use MailPoet\Newsletter\Renderer\Blocks\Image;
use MailPoet\Newsletter\Renderer\Blocks\Social;
use MailPoet\Newsletter\Renderer\Blocks\Spacer;
use MailPoet\Newsletter\Renderer\Blocks\Text;
use MailPoet\Newsletter\Renderer\Columns\Renderer as ColumnRenderer;
use MailPoet\Newsletter\Renderer\Renderer;

class NewsletterRendererCest {
  function __construct() {
    $this->newsletterData = json_decode(file_get_contents(dirname(__FILE__) . '/RendererTestData.json'), true);
    $this->renderer = new Renderer($this->newsletterData);
    $this->columnRenderer = new ColumnRenderer();
    $this->queryDOM = new \pQuery();
  }

  function itRendersCompleteNewsletter() {
    $template = $this->renderer->renderAll();
    $DOM = $this->queryDOM->parseStr($template);

    // we expect to have 4 column containers and 7 columns (1x1, 1x2, 1x3, 1x1)
    expect(count($DOM('.mailpoet_cols-wrapper')))->equals(4);
    expect(count($DOM('.mailpoet_force-row')))->equals(7);
  }

  function itRendersColumns() {
    $columnContent = array(
      'one',
      'two',
      'three'
    );
    $DOM = $this->queryDOM->parseStr($this->columnRenderer->render(count($columnContent), $columnContent));

    // rendered object should cocntain three columns
    foreach ($DOM('.mailpoet_force-row > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect(count(array_diff($renderedColumnContent, $columnContent)))->equals(0);
  }

  function itRendersHeader() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][0];
    $DOM = $this->queryDOM->parseStr(Header::render($template));

    // element should be proplerly nested, and styles should be applied to <td>, <a> and <p> elements
    expect(is_object($DOM('tr > td > p', 0)))->true();
    expect(is_object($DOM('tr > td > p > a', 0)))->true();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('p', 0)->attr('style'))->notEmpty();
  }

  function itRendersImage() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $DOM = $this->queryDOM->parseStr(Image::render($template));

    // element should be properly nested, it's width set and style applied to <td>
    expect(is_object($DOM('tr > td > img', 0)))->true();
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(560);
    expect($DOM('tr > td', 0)->attr('style'))->notEmpty();
  }

  function itRendersText() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][2];
    $DOM = $this->queryDOM->parseStr(Text::render($template));

    // blockquotes and paragraphs should be converted to spans and placed inside a table
    expect(is_object($DOM('tr > td.mailpoet_text > table > tr > td > span.paragraph', 0)))->true();
    expect(is_object($DOM('tr > td.mailpoet_text > table.mailpoet_blockquote > tbody > tr > td > table > tr > td > span.paragraph', 0)))->true();
  }

  function itRendersDivider() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][3];
    $DOM = $this->queryDOM->parseStr(Divider::render($template));

    // element should be properly nested and its border-top-width set
    expect(is_object($DOM('tr > td.mailpoet_divider > table > tr > td', 0)))->true();
    expect(preg_match('/border-top-width: 3px/', $DOM('tr > td.mailpoet_divider > table > tr > td', 0)->attr('style')))->equals(1);
  }

  function itRendersSpacer() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->queryDOM->parseStr(Spacer::render($template));

    // element should be properly nested and its height set
    expect(is_object($DOM('tr > td.mailpoet_spacer', 0)))->true();
    expect(preg_match('/height: 50px/', $DOM('tr > td.mailpoet_spacer', 0)->attr('style')))->equals(1);
  }

  function itRendersButton() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $DOM = $this->queryDOM->parseStr(Button::render($template));

    // element should be properly nested with arcsize/styles/fillcolor set
    expect(is_object($DOM('tr > td.mailpoet_button > div > table > tr > td > a.mailpoet_button', 0)))->true();
    expect(preg_match('/line-height: 30px/', $DOM('a.mailpoet_button', 0)->attr('style')))->equals(1);
    expect(preg_match('/arcsize="' . round(20 / 30 * 100) . '%"/', $DOM('tr > td.mailpoet_button > div > table > tr > td', 0)->text()))->equals(1);
    expect(preg_match('/style="height:30px.*?width:100px/', $DOM('tr > td.mailpoet_button > div > table > tr > td', 0)->text()))->equals(1);
    expect(preg_match('/style="color:#ffffff.*?font-family:Arial.*?font-size:13px/', $DOM('tr > td.mailpoet_button > div > table > tr > td', 0)->text()))->equals(1);
    expect(preg_match('/fillcolor="#666666/', $DOM('tr > td.mailpoet_button > div > table > tr > td', 0)->text()))->equals(1);
  }

  function itRendersSocialIcons() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][6];
    $DOM = $this->queryDOM->parseStr(Social::render($template));

    // element should be properly nested, contain social icons and image source/link href/alt should be  properly set
    expect(is_object($DOM('tr > td.mailpoet_social > div', 0)))->true();
    expect(count($DOM('a > img')))->equals(10);
    expect($DOM('a', 0)->attr('href'))->equals('http://example.org');
    expect($DOM('a > img', 0)->attr('src'))->equals('http://mp3.mailpoet.net/various/social-icons/custom.png');
    expect($DOM('a > img', 0)->attr('alt'))->equals('custom');
  }

  function itRendersFooter() {
    $template = $this->newsletterData['content']['blocks'][3]['blocks'][0]['blocks'][0];
    $DOM = $this->queryDOM->parseStr(Footer::render($template));

    // element should be proplerly nested, and styles should be applied to <td>, <a> and <p> elements
    expect(is_object($DOM('tr > td.mailpoet_footer > div', 0)))->true();
    expect(is_object($DOM('tr > td.mailpoet_footer > div > a > p', 0)))->true();
    expect($DOM('tr > td.mailpoet_footer', 0)->attr('style'))->notEmpty();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('p', 0)->attr('style'))->notEmpty();
  }
}
