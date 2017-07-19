<?php

use Codeception\Util\Fixtures;
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

class NewsletterRendererTest extends MailPoetTest {
  function __construct() {
    parent::__construct();
    $this->newsletter = array(
      'body' => json_decode(
        file_get_contents(dirname(__FILE__) . '/RendererTestData.json'), true
      ),
      'id' => 1,
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'active'
    );
    $this->renderer = new Renderer($this->newsletter);
    $this->column_renderer = new ColumnRenderer();
    $this->DOM_parser = new \pQuery();
  }

  function testItRendersCompleteNewsletter() {
    $this->renderer->preview = true; // do not render logo
    $template = $this->renderer->render();
    expect(isset($template['html']))->true();
    expect(isset($template['text']))->true();
    $DOM = $this->DOM_parser->parseStr($template['html']);
    // we expect to have 7 columns:
    //  1x column including header
    //  2x column
    //  3x column
    //  1x footer
    expect(count($DOM('.mailpoet_cols-one')))->equals(2);
    expect(count($DOM('.mailpoet_cols-two')))->equals(2);
    expect(count($DOM('.mailpoet_cols-three')))->equals(3);
  }

  function testItRendersOneColumn() {
    $column_content = array(
      'one'
    );
    $column_styles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->DOM_parser->parseStr(
      $this->column_renderer->render(
        $column_styles,
        count($column_content),
        $column_content)
    );
    foreach($DOM('table.mailpoet_cols-one > tbody') as $column) {
      $rendered_column_content[] = trim($column->text());
    };
    expect($rendered_column_content)->equals($column_content);
  }

  function testItRendersTwoColumns() {
    $column_content = array(
      'one',
      'two'
    );
    $column_styles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->DOM_parser->parseStr(
      $this->column_renderer->render(
        $column_styles,
        count($column_content),
        $column_content)
    );
    foreach($DOM('table.mailpoet_cols-two > tbody') as $column) {
      $rendered_column_content[] = trim($column->text());
    };
    expect($rendered_column_content)->equals($column_content);
  }

  function testItRendersThreeColumns() {
    $column_content = array(
      'one',
      'two',
      'three'
    );
    $column_styles = array(
      'block' => array(
        'backgroundColor' => "#999999"
      )
    );
    $DOM = $this->DOM_parser->parseStr(
      $this->column_renderer->render(
        $column_styles,
        count($column_content),
        $column_content)
    );
    foreach($DOM('table.mailpoet_cols-three > tbody') as $column) {
      $rendered_column_content[] = trim($column->text());
    };
    expect($rendered_column_content)->equals($column_content);
  }

  function testItRendersHeader() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][0];
    $DOM = $this->DOM_parser->parseStr(Header::render($template));
    // element should be properly nested, and styles should be applied
    expect($DOM('tr > td.mailpoet_header', 0)->html())->notEmpty();
    expect($DOM('tr > td > a', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }

  function testItRendersImage() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $DOM = $this->DOM_parser->parseStr(Image::render($template, $columnCount = 1));
    // element should be properly nested, it's width set and style applied
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(620);
    expect($DOM('tr > td > img', 0)->attr('style'))->notEmpty();
  }

  function testItDoesNotRenderImageWithoutSrc() {
    $image = array(
      'src' => '',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'alt' => 'some test alt text'
    );
    $rendered_image = Image::render($image, $columnCount = 1);
    expect($rendered_image)->equals('');
  }

  function testItRendersImageWithLink() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $template['link'] = 'http://example.com';
    $DOM = $this->DOM_parser->parseStr(Image::render($template, $columnCount = 1));
    // element should be wrapped in <a> tag
    expect($DOM('tr > td > a', 0)->html())->contains('<img');
    expect($DOM('tr > td > a', 0)->attr('href'))->equals($template['link']);
  }

  function testItAdjustsImageDimensions() {
    // image gets scaled down when image width > column width
    $image = array(
      'width' => 800,
      'height' => 600,
      'fullWidth' => true
    );
    $new_image_dimensions = Image::adjustImageDimensions($image, $columnCount = 1);
    expect($new_image_dimensions['width'])->equals(660);
    expect($new_image_dimensions['height'])->equals(495);
    // nothing happens when image width = column width
    $image['width'] = 661;
    $new_image_dimensions = Image::adjustImageDimensions($image, $columnCount = 1);
    expect($new_image_dimensions['width'])->equals(660);
    // nothing happens when image width < column width
    $image['width'] = 659;
    $new_image_dimensions = Image::adjustImageDimensions($image, $columnCount = 1);
    expect($new_image_dimensions['width'])->equals(659);
    // image is reduced by 40px when it's width > padded column width
    $image['width'] = 621;
    $image['fullWidth'] = false;
    $new_image_dimensions = Image::adjustImageDimensions($image, $columnCount = 1);
    expect($new_image_dimensions['width'])->equals(620);
    // nothing happens when image with < padded column width
    $image['width'] = 619;
    $new_image_dimensions = Image::adjustImageDimensions($image, $columnCount = 1);
    expect($new_image_dimensions['width'])->equals(619);
  }

  function testItRendersText() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][2];
    $DOM = $this->DOM_parser->parseStr(Text::render($template));
    // blockquotes and paragraphs should be converted to spans and placed inside a table
    expect(
      $DOM('tr > td > table > tr > td.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    expect(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->notEmpty();
    // blockquote should contain heading elements but not paragraphs
    expect(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->contains('<h2');
    expect(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->notContains('<p');
    // ul/ol/li should have mailpoet_paragraph class added & styles applied
    expect(
      $DOM('tr > td > ul.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    expect(
      $DOM('tr > td > ol.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    expect($DOM('tr > td.mailpoet_text > ul.mailpoet_paragraph', 0)->attr('style'))
      ->contains('padding-top:0;padding-bottom:0;margin-top:10px;text-align:left;margin-bottom:10px;');
    // headings should be styled
    expect($DOM('tr > td.mailpoet_text > h1', 0)->attr('style'))
      ->contains('padding:0;font-style:normal;font-weight:normal;');

    // trailing line breaks should be cut off, but not inside an element
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][8];
    $DOM = $this->DOM_parser->parseStr(Text::render($template));
    expect(count($DOM('tr > td > br', 0)))
      ->equals(0);
    expect($DOM('tr > td > h3', 0)->html())
      ->contains('<a');
  }

  function testItRendersDivider() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][3];
    $DOM = $this->DOM_parser->parseStr(Divider::render($template));
    // element should be properly nested and its border-top-width set
    expect(
      preg_match(
        '/border-top-width: 3px/',
        $DOM('tr > td.mailpoet_divider > table > tr > td.mailpoet_divider-cell', 0)->attr('style')
      ))->equals(1);
  }

  function testItRendersSpacer() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->DOM_parser->parseStr(Spacer::render($template));
    // element should be properly nested and its height set
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('height'))->equals(50);
  }

  function testItSetsSpacerBackground() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->DOM_parser->parseStr(Spacer::render($template));
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))->null();
    $template['styles']['block']['backgroundColor'] = '#ffff';
    $DOM = $this->DOM_parser->parseStr(Spacer::render($template));
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))
      ->equals('#ffff');
  }

  function testItCalculatesButtonWidth() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $template['styles']['block']['width'] = '700px';
    $button_width = Button::calculateWidth($template, $columnCunt = 1);
    expect($button_width)->equals('618px'); //(width - (2 * border width)
  }

  function testItRendersButton() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $DOM = $this->DOM_parser->parseStr(Button::render($template, $columnCount = 1));
    // element should be properly nested with arcsize/styles/fillcolor set
    expect(
      $DOM('tr > td > div > table > tr > td > a.mailpoet_button', 0)->html()
    )->notEmpty();
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
        '/style="height:30px.*?width:98px/',
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

  function testItRendersSocialIcons() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][6];
    $DOM = $this->DOM_parser->parseStr(Social::render($template));
    // element should be properly nested, contain social icons and
    // image source/link href/alt should be  properly set
    expect($DOM('tr > td', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('href'))->equals('http://example.com');
    expect($DOM('td > a:nth-of-type(10) > img')->attr('src'))->contains('custom.png');
    expect($DOM('td > a:nth-of-type(10) > img')->attr('alt'))->equals('custom');
    // there should be 10 icons
    expect(count($DOM('a > img')))->equals(10);
  }

  function testItDoesNotRenderSocialIconsWithoutImageSrc() {
    $block = array(
      'icons' => array(
        'image' => '',
        'width' => '100',
        'height' => '100',
        'link' => '',
        'iconType' => 'custom',
      )
    );
    $rendered_block = Social::render($block, $columnCount = 1);
    expect($rendered_block)->equals('');
  }

  function testItRendersFooter() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][3]['blocks'][0]['blocks'][0];
    $DOM = $this->DOM_parser->parseStr(Footer::render($template));
    // element should be properly nested, and styles should be applied
    expect($DOM('tr > td.mailpoet_footer', 0)->html())->notEmpty();
    expect($DOM('tr > td > a', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }

  function testItSetsSubject() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    $DOM = $this->DOM_parser->parseStr($template['html']);
    $subject = trim($DOM('title')->text());
    expect($subject)->equals($this->newsletter['subject']);
  }

  function testItSetsPreheader() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    $DOM = $this->DOM_parser->parseStr($template['html']);
    $preheader = trim($DOM('td.mailpoet_preheader')->text());
    expect($preheader)->equals($this->newsletter['preheader']);
  }

  function testItDoesNotAddMailpoetLogoWhenPremiumIsActive() {
    $this->renderer->preview = false;
    $this->renderer->mss_activated = false;
    $this->renderer->premium_activated = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  function testItDoesNotAddMailpoetLogoWhenMSSIsActive() {
    $this->renderer->preview = false;
    $this->renderer->premium_activated = false;
    $this->renderer->mss_activated = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  function testItDoesNotAddMailpoetLogoWhenPreviewIsEnabled() {
    $this->renderer->mss_activated = false;
    $this->renderer->premium_activated = false;
    $this->renderer->preview = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  function testItAddsMailpoetLogo() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->renderer->mss_activated = false;
    $this->renderer->premium_activated = false;
    $this->renderer->preview = false;

    $template = $this->renderer->render();
    expect($template['html'])->contains('mailpoet_logo_newsletter.png');
  }

  function testItPostProcessesTemplate() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    // !important should be stripped from everywhere except from
    // with the <style> tag
    expect(preg_match('/<style.*?important/s', $template['html']))
      ->equals(1);
    expect(preg_match('/mailpoet_template.*?important/s', $template['html']))
      ->equals(0);
  }
}