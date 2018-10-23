<?php
namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\Renderer\StylesHelper;

class StylesHelperTest extends \MailPoetTest {

  function __construct() {
    parent::__construct();
    $this->styles = [
      "text" => [
        "fontColor" => "#565656",
        "fontFamily" => "Arial",
        "fontSize" => "16px"
      ],
      "h1" => [
        "fontColor" => "#565656",
        "fontFamily" => "Roboto",
        "fontSize" => "36px"
      ],
      "h2" => [
        "fontColor" => "#565656",
        "fontFamily" => "Source Sans Pro",
        "fontSize" => "26px"
      ],
      "h3" => [
        "fontColor" => "#565656",
        "fontFamily" => "Roboto",
        "fontSize" => "18px"
      ],
      "link" => [
        "fontColor" => "#561ab9",
        "textDecoration" => "underline"
      ],
    ];
  }

  function testItGetsCustomFontsNames() {
    expect(StylesHelper::getCustomFontsNames($this->styles))
      ->equals(['Roboto', 'Source Sans Pro']);
  }

  function testItGetsCustomFontsLinks() {
    expect(StylesHelper::getCustomFontsLinks($this->styles))
      ->equals(implode("\n", [
        '<!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i" rel="stylesheet"><!--<![endif]-->',
        '<!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,400i,700,700i" rel="stylesheet"><!--<![endif]-->',
      ]));
  }
}