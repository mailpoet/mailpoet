<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\Renderer\StylesHelper;

class StylesHelperTest extends \MailPoetUnitTest {
  public function testItGetsCustomFontsLinks() {
    $stylesWithCustomFonts = [
      "text" => [
        "fontColor" => "#565656",
        "fontFamily" => "Arial",
        "fontSize" => "16px",
      ],
      "h1" => [
        "fontColor" => "#565656",
        "fontFamily" => "Roboto",
        "fontSize" => "36px",
      ],
      "h2" => [
        "fontColor" => "#565656",
        "fontFamily" => "Source Sans Pro",
        "fontSize" => "26px",
      ],
      "h3" => [
        "fontColor" => "#565656",
        "fontFamily" => "Roboto",
        "fontSize" => "18px",
      ],
      "link" => [
        "fontColor" => "#561ab9",
        "textDecoration" => "underline",
      ],
    ];

    $stylesWithoutCustomFonts = [
      "text" => [
        "fontColor" => "#565656",
        "fontFamily" => "Arial",
        "fontSize" => "16px",
      ],
      "h1" => [
        "fontColor" => "#565656",
        "fontFamily" => "Arial",
        "fontSize" => "36px",
      ],
      "h2" => [
        "fontColor" => "#565656",
        "fontFamily" => "Times New Roman",
        "fontSize" => "26px",
      ],
      "h3" => [
        "fontColor" => "#565656",
        "fontFamily" => "Georgia",
        "fontSize" => "18px",
      ],
      "link" => [
        "fontColor" => "#561ab9",
        "textDecoration" => "underline",
      ],
    ];

    expect(StylesHelper::getCustomFontsLinks($stylesWithCustomFonts))
      ->equals('<!--[if !mso]><!-- --><link href="https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i|Source+Sans+Pro:400,400i,700,700i" rel="stylesheet"><!--<![endif]-->');

    expect(StylesHelper::getCustomFontsLinks($stylesWithoutCustomFonts))
      ->equals('');
  }

  public function testItAddsMsoStyles() {
    $styles = [
      "fontSize" => "16px",
      "lineHeight" => "1",
    ];
    $styles = StylesHelper::setStyle($styles, '.mailpoet_paragraph');
    expect($styles)->stringContainsString('mso-ansi-font-size:16px;');
    expect($styles)->stringContainsString('mso-line-height-alt:16px;');

    $styles = [
      "fontSize" => "17px",
      "lineHeight" => "1.1",
    ];
    $styles = StylesHelper::setStyle($styles, '.mailpoet_paragraph');
    expect($styles)->stringContainsString('mso-ansi-font-size:18px;');
    expect($styles)->stringContainsString('font-size:17px;');
    expect($styles)->stringContainsString('line-height:18.7px;');
    expect($styles)->stringContainsString('mso-line-height-alt:20px;');
  }
}
