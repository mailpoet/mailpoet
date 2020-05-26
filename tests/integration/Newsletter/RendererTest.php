<?php

namespace MailPoet\Test\Newsletter;

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

class RendererTest extends \MailPoetTest {
  public $dOMParser;
  public $columnRenderer;
  public $renderer;
  public $newsletter;
  const COLUMN_BASE_WIDTH = 660;

  public function __construct() {
    parent::__construct();
    $this->newsletter = [
      'body' => json_decode(
        (string)file_get_contents(dirname(__FILE__) . '/RendererTestData.json'), true
      ),
      'id' => 1,
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'active',
    ];
    $this->renderer = new Renderer($this->newsletter);
    $this->columnRenderer = new ColumnRenderer();
    $this->dOMParser = new \pQuery();
  }

  public function testItRendersCompleteNewsletter() {
    $this->renderer->preview = true; // do not render logo
    $template = $this->renderer->render();
    expect(isset($template['html']))->true();
    expect(isset($template['text']))->true();
    $DOM = $this->dOMParser->parseStr($template['html']);
    // we expect to have 7 columns:
    //  1x column including header
    //  2x column
    //  3x column
    //  1x footer
    expect(count($DOM('.mailpoet_cols-one')))->equals(2);
    expect(count($DOM('.mailpoet_cols-two')))->equals(2);
    expect(count($DOM('.mailpoet_cols-three')))->equals(3);

    // nested vertical container should be rendered
    expect(count($DOM('.nested-vertical-container')))->equals(1);
  }

  public function testItRendersOneColumn() {
    $columnContent = [
      'one',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-one > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
    expect((string)$DOM)->contains(' bgcolor="#999999"');
  }

  public function testItRendersTwoColumns() {
    $columnContent = [
      'one',
      'two',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[], []],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-two > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
    expect((string)$DOM)->contains(' bgcolor="#999999"');
  }

  public function testItRendersThreeColumns() {
    $columnContent = [
      'one',
      'two',
      'three',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[], [], []],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-three > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    expect($renderedColumnContent)->equals($columnContent);
    expect((string)$DOM)->contains(' bgcolor="#999999"');
  }

  public function testItRendersScaledColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'scale', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    expect($columnCss)->contains('background: #999999 url(https://example.com/image.jpg) no-repeat center/cover;');
    expect($columnCss)->contains('background-color: #999999;');
    expect($columnCss)->contains('background-image: url(https://example.com/image.jpg);');
    expect($columnCss)->contains('background-repeat: no-repeat;');
    expect($columnCss)->contains('background-position: center;');
    expect($columnCss)->contains('background-size: cover;');
  }

  public function testItRendersFitColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'fit', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    expect($columnCss)->contains('background: #999999 url(https://example.com/image.jpg) no-repeat center/contain;');
    expect($columnCss)->contains('background-color: #999999;');
    expect($columnCss)->contains('background-image: url(https://example.com/image.jpg);');
    expect($columnCss)->contains('background-repeat: no-repeat;');
    expect($columnCss)->contains('background-position: center;');
    expect($columnCss)->contains('background-size: contain;');
  }

  public function testItRendersTiledColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'tile', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    expect($columnCss)->contains('background: #999999 url(https://example.com/image.jpg) repeat center/contain;');
    expect($columnCss)->contains('background-color: #999999;');
    expect($columnCss)->contains('background-image: url(https://example.com/image.jpg);');
    expect($columnCss)->contains('background-repeat: repeat;');
    expect($columnCss)->contains('background-position: center;');
    expect($columnCss)->contains('background-size: contain;');
  }

  public function testItRendersFallbackColumnBackgroundColorForBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => 'transparent']];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'tile', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    expect($columnCss)->contains('background: #ffffff url(https://example.com/image.jpg) repeat center/contain;');
    expect($columnCss)->contains('background-color: #ffffff;');
  }

  public function testItRendersHeader() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][0];
    $DOM = $this->dOMParser->parseStr((new Header)->render($template));
    // element should be properly nested, and styles should be applied
    expect($DOM('tr > td.mailpoet_header', 0)->html())->notEmpty();
    expect($DOM('tr > td > a', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }

  public function testItRendersImage() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, self::COLUMN_BASE_WIDTH));
    // element should be properly nested, it's width set and style applied
    expect($DOM('tr > td > img', 0)->attr('width'))->equals(620);
  }

  public function testItRendersAlignedImage() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    // default alignment (center)
    unset($template['styles']['block']['textAlign']);
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    expect($DOM('tr > td', 0)->attr('align'))->equals('center');
    $template['styles']['block']['textAlign'] = 'center';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    expect($DOM('tr > td', 0)->attr('align'))->equals('center');
    $template['styles']['block']['textAlign'] = 'something odd';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    expect($DOM('tr > td', 0)->attr('align'))->equals('center');
    // left alignment
    $template['styles']['block']['textAlign'] = 'left';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    expect($DOM('tr > td', 0)->attr('align'))->equals('left');
    // right alignment
    $template['styles']['block']['textAlign'] = 'right';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    expect($DOM('tr > td', 0)->attr('align'))->equals('right');
  }

  public function testItDoesNotRenderImageWithoutSrc() {
    $image = [
      'src' => '',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    expect($renderedImage)->equals('');
  }

  public function testItForcesAbsoluteSrcForImages() {
    $image = [
      'src' => '/relative-path',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    $siteUrl = get_option('siteurl');
    expect($renderedImage)->contains('src="' . $siteUrl . '/relative-path"');

    $image = [
      'src' => '//path-without-protocol',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    expect($renderedImage)->contains('src="//path-without-protocol"');
  }

  public function testItRendersImageWithLink() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $template['link'] = 'http://example.com';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, self::COLUMN_BASE_WIDTH));
    // element should be wrapped in <a> tag
    expect($DOM('tr > td > a', 0)->html())->contains('<img');
    expect($DOM('tr > td > a', 0)->attr('href'))->equals($template['link']);
  }

  public function testItAdjustsImageDimensions() {
    // image gets scaled down when image width > column width
    $image = [
      'width' => 800,
      'height' => 600,
      'fullWidth' => true,
    ];
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    expect($newImageDimensions['width'])->equals(660);
    expect($newImageDimensions['height'])->equals(495);
    // nothing happens when image width = column width
    $image['width'] = 661;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    expect($newImageDimensions['width'])->equals(660);
    // nothing happens when image width < column width
    $image['width'] = 659;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    expect($newImageDimensions['width'])->equals(659);
    // image is reduced by 40px when it's width > padded column width
    $image['width'] = 621;
    $image['fullWidth'] = false;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    expect($newImageDimensions['width'])->equals(620);
    // nothing happens when image with < padded column width
    $image['width'] = 619;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    expect($newImageDimensions['width'])->equals(619);
  }

  public function testItRendersImageWithAutoDimensions() {
    $image = [
      'width' => 'auto',
      'height' => 'auto',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    expect($renderedImage)->contains('width="auto"');
  }

  public function testItAdjustImageDimensionsWithPx() {
    $image = [
      'width' => '1000px',
      'height' => '1000px',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    expect($renderedImage)->contains('width="620"');
  }

  public function testItAdjustImageDimensionsWithoutPx() {
    $image = [
      'width' => '1000',
      'height' => '1000',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    expect($renderedImage)->contains('width="620"');
  }

  public function testItRendersText() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][2];
    $DOM = $this->dOMParser->parseStr((new Text)->render($template));
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
    $DOM = $this->dOMParser->parseStr((new Text)->render($template));
    expect(count($DOM('tr > td > br', 0)))
      ->equals(0);
    expect($DOM('tr > td > h3', 0)->html())
      ->contains('<a');
  }

  public function testItRendersDivider() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][3];
    $DOM = $this->dOMParser->parseStr((new Divider)->render($template));
    // element should be properly nested and its border-top-width set
    expect(
      preg_match(
        '/border-top-width: 3px/',
        $DOM('tr > td.mailpoet_divider > table > tr > td.mailpoet_divider-cell', 0)->attr('style')
      ))->equals(1);
  }

  public function testItRendersSpacer() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    // element should be properly nested and its height set
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('height'))->equals(50);
  }

  public function testItSetsSpacerBackground() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))->null();
    $template['styles']['block']['backgroundColor'] = '#ffff';
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    expect($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))
      ->equals('#ffff');
  }

  public function testItCalculatesButtonWidth() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $template['styles']['block']['width'] = '700px';
    $buttonWidth = (new Button)->calculateWidth($template, self::COLUMN_BASE_WIDTH);
    expect($buttonWidth)->equals('618px'); //(width - (2 * border width)
  }

  public function testItRendersButton() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $DOM = $this->dOMParser->parseStr((new Button)->render($template, self::COLUMN_BASE_WIDTH));
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

  public function testItUsesFullFontFamilyNameInElementStyles() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $template['styles']['block']['fontFamily'] = 'Lucida';
    $DOM = $this->dOMParser->parseStr((new Button)->render($template, self::COLUMN_BASE_WIDTH));
    expect(
      preg_match(
        '/font-family: \'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif/',
        $DOM('a.mailpoet_button', 0)->attr('style'))
    )->equals(1);
  }

  public function testItRendersSocialIcons() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][6];
    $DOM = $this->dOMParser->parseStr((new Social)->render($template));
    // element should be properly nested, contain social icons and
    // image source/link href/alt should be  properly set
    expect($DOM('tr > td', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('href'))->equals('http://example.com');
    expect($DOM('td > a:nth-of-type(10) > img')->attr('src'))->contains('custom.png');
    expect($DOM('td > a:nth-of-type(10) > img')->attr('alt'))->equals('custom');
    // there should be 10 icons
    expect(count($DOM('a > img')))->equals(10);
  }

  public function testItDoesNotRenderSocialIconsWithoutImageSrc() {
    $block = [
      'icons' => [
        'image' => '',
        'width' => '100',
        'height' => '100',
        'link' => '',
        'iconType' => 'custom',
      ],
    ];
    $renderedBlock = (new Social)->render($block);
    expect($renderedBlock)->equals('');
  }

  public function testItRendersFooter() {
    $newsletter = $this->newsletter['body'];
    $template = $newsletter['content']['blocks'][3]['blocks'][0]['blocks'][0];
    $DOM = $this->dOMParser->parseStr((new Footer)->render($template));
    // element should be properly nested, and styles should be applied
    expect($DOM('tr > td.mailpoet_footer', 0)->html())->notEmpty();
    expect($DOM('tr > td > a', 0)->html())->notEmpty();
    expect($DOM('a', 0)->attr('style'))->notEmpty();
    expect($DOM('td', 0)->attr('style'))->notEmpty();
  }

  public function testItSetsSubject() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    $DOM = $this->dOMParser->parseStr($template['html']);
    $subject = trim($DOM('title')->text());
    expect($subject)->equals($this->newsletter['subject']);
  }

  public function testItSetsPreheader() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    $DOM = $this->dOMParser->parseStr($template['html']);
    $preheader = trim($DOM('td.mailpoet_preheader')->text());
    expect($preheader)->equals($this->newsletter['preheader']);
  }

  public function testItDoesNotAddMailpoetLogoWhenPremiumIsActive() {
    $this->renderer->preview = false;
    $this->renderer->mssActivated = false;
    $this->renderer->premiumActivated = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  public function testItDoesNotAddMailpoetLogoWhenMSSIsActive() {
    $this->renderer->preview = false;
    $this->renderer->premiumActivated = false;
    $this->renderer->mssActivated = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  public function testItDoesNotAddMailpoetLogoWhenPreviewIsEnabled() {
    $this->renderer->mssActivated = false;
    $this->renderer->premiumActivated = false;
    $this->renderer->preview = true;

    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    expect($template['html'])->notContains('mailpoet_logo_newsletter.png');
  }

  public function testItAddsMailpoetLogo() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->renderer->mssActivated = false;
    $this->renderer->premiumActivated = false;
    $this->renderer->preview = false;

    $template = $this->renderer->render();
    expect($template['html'])->contains('mailpoet_logo_newsletter.png');
  }

  public function testItPostProcessesTemplate() {
    $this->renderer->newsletter['body'] = json_decode(Fixtures::get('newsletter_body_template'), true);
    $template = $this->renderer->render();
    // !important should be stripped from everywhere except from with the <style> tag
    expect(preg_match('/<style.*?important/s', $template['html']))->equals(1);
    expect(preg_match('/mailpoet_template.*?important/s', $template['html']))->equals(0);

    // spaces are only replaces in image tag URLs
    expect(preg_match('/image%20with%20space.jpg/s', $template['html']))->equals(1);
    expect(preg_match('/link%20with%20space.jpg/s', $template['html']))->equals(0);
  }
}
