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
    $this->newsletterData = json_decode(
      file_get_contents(dirname(__FILE__) . '/RendererTestData.json'),
      true
    );
    $this->renderer = new Renderer($this->newsletterData);
    $this->columnRenderer = new ColumnRenderer();
    $this->queryDOM = new \pQuery();
  }

  function itRendersCompleteNewsletter() {
    $template = $this->renderer->render();
    $DOM = $this->queryDOM->parseStr($template);
    // we expect to have 7 columns:
    //  1x column including header
    //  2x column
    //  3x column
    //  1x footer
    expect(count($DOM('.mailpoet_cols-one')))->equals(2);
    expect(count($DOM('.mailpoet_cols-two')))->equals(2);
    expect(count($DOM('.mailpoet_cols-three')))->equals(3);
  }

  function itRendersOneColumn() {
    $columnContent = array(
      'one'
    );
    $columnStyles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->queryDOM->parseStr(
      $this->columnRenderer->render(
        $columnStyles,
        count($columnContent),
        $columnContent)
    );
    foreach ($DOM('table.mailpoet_cols-one > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
  }

  function itRendersTwoColumns() {
    $columnContent = array(
      'one',
      'two'
    );
    $columnStyles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->queryDOM->parseStr(
      $this->columnRenderer->render(
        $columnStyles,
        count($columnContent),
        $columnContent)
    );
    foreach ($DOM('table.mailpoet_cols-two > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
  }

  function itRendersThreeColumns() {
    $columnContent = array(
      'one',
      'two',
      'three'
    );
    $columnStyles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->queryDOM->parseStr(
      $this->columnRenderer->render(
        $columnStyles,
        count($columnContent),
        $columnContent)
    );
    foreach ($DOM('table.mailpoet_cols-three > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
  }

  function itRendersHeader() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][0];
    $DOM = $this->queryDOM->parseStr(Header::render($template));
    // element should be proplerly nested, and styles should be applied
    expect(!empty($DOM('tr > td.mailpoet_header', 0)->html()))->true();
    expect(!empty($DOM('tr > td > a', 0)->html()))->true();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }


  function itRendersImage() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $DOM = $this->queryDOM->parseStr(Image::render($template, $columnCount = 1));
    // element should be properly nested, it's width set and style applied
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(620);
    expect($DOM('tr > td > img', 0)->attr('style'))->notEmpty();
  }

  function itAdjustImageSizeBasedOnColumnWidth() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $template['width'] = '800px';
    $DOM = $this->queryDOM->parseStr(Image::render($template, $columnCount = 2));
    // 800px resized to 330px (2-column layout) and 40px padding applied
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(290);
    $template['width'] = '280px';
    $DOM = $this->queryDOM->parseStr(Image::render($template, $columnCount = 2));
    // 280px image should not be resized and padding should not be applied
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(280);
  }


  function itRendersText() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][2];
    $DOM = $this->queryDOM->parseStr(Text::render($template));
    //!d($DOM->__toString());exit;
    // blockquotes and paragraphs should be converted to spans and placed inside a table
    expect(
      !empty($DOM('tr > td > table > tr > td.mailpoet_paragraph', 0)->html())
    )->true();
    expect(
      !empty($DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
      ))->true();
    // ul/ol/li should have mailpoet_paragraph class added & styles applied
    expect(
      !empty(
      $DOM('tr > td > ul.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
      )
    )->true();
    expect(
      !empty(
      $DOM('tr > td > ol.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
      )
    )->true();
    expect($DOM('tr > td.mailpoet_text > ul.mailpoet_paragraph', 0)->attr('style'))
      ->contains('padding-top:0;padding-bottom:0;margin-top:0;margin-bottom:0;');
    // headings should be styled
    expect($DOM('tr > td.mailpoet_text > h1', 0)->attr('style'))
      ->contains('margin:0;font-style:normal;font-weight:normal;');

  }

  function itRendersDivider() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][3];
    $DOM = $this->queryDOM->parseStr(Divider::render($template));
    // element should be properly nested and its border-top-width set
    expect(
      preg_match(
        '/border-top-width: 3px/',
        $DOM('tr > td.mailpoet_divider > table > tr > td.mailpoet_divider-cell', 0)->attr('style')
      ))->equals(1);
  }


  function itRendersSpacer() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->queryDOM->parseStr(Spacer::render($template));
    // element should be properly nested and its height set
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('height'))->equals(50);
  }

  function itRendersButton() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $DOM = $this->queryDOM->parseStr(Button::render($template));
    // element should be properly nested with arcsize/styles/fillcolor set
    expect(
      !empty($DOM('tr > td > div > table > tr > td > a.mailpoet_button', 0)->html())
    )->true();
    expect(
      preg_match(
        '/line-height: 30px/',
        $DOM('a.mailpoet_button', 0)->attr('style'))
    )->equals(1);
    expect(
      preg_match(
        '/arcsize="' . round(20 / 30 * 100) . '%"/',
        $DOM('tr > td > div > table > tr > td', 0)->text())
    )->equals(1);
    expect(
      preg_match(
        '/style="height:30px.*?width:100px/',
        $DOM('tr > td > div > table > tr > td', 0)->text())
    )->equals(1);
    expect(
      preg_match(
        '/style="color:#ffffff.*?font-family:Arial.*?font-size:14px/',
        $DOM('tr > td > div > table > tr > td', 0)->text())
    )->equals(1);
    expect(
      preg_match(
        '/fillcolor="#666666/',
        $DOM('tr > td > div > table > tr > td', 0)->text())
    )->equals(1);
  }

  function itRendersSocialIcons() {
    $template = $this->newsletterData['content']['blocks'][0]['blocks'][0]['blocks'][6];
    $DOM = $this->queryDOM->parseStr(Social::render($template));
    // element should be properly nested, contain social icons and
    // image source/link href/alt should be  properly set
    expect(!empty($DOM('tr > td > div.mailpoet_social-icon', 0)->html()))->true();
    expect($DOM('a', 0)->attr('href'))->equals('http://example.com');
    expect($DOM('div > a:nth-of-type(10) > img')->attr('src'))->contains('custom.png');
    expect($DOM('div > a:nth-of-type(10) > img')->attr('alt'))->equals('custom');
    // there should be 10 icons and 19 spacer images
    expect(count($DOM('a > img')))->equals(10);
    expect(count($DOM('img')))->equals(19);
  }

  function itRendersFooter() {
    $template = $this->newsletterData['content']['blocks'][3]['blocks'][0]['blocks'][0];
    $DOM = $this->queryDOM->parseStr(Footer::render($template));
    // element should be proplerly nested, and styles should be applied
    expect(!empty($DOM('tr > td.mailpoet_footer', 0)->html()))->true();
    expect(!empty($DOM('tr > td > a', 0)->html()))->true();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }

  function itPostProcessesTemplate() {
    $template = $this->renderer->render();
    // !important should be stripped from everywhere except from
    // with the <style> tag
    expect(preg_match('/<style.*?important/s', $template))
      ->equals(1);
    expect(preg_match('/mailpoet_template.*?important/s', $template))
      ->equals(0);
  }
}
