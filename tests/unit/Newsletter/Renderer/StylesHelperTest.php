<?php

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
}
