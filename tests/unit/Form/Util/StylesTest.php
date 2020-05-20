<?php

namespace MailPoet\Test\Form\Util;

use Codeception\Util\Fixtures;
use MailPoet\Form\Util\Styles;

class StylesTest extends \MailPoetUnitTest {
  /** @var Styles */
  private $styles;

  public function _before() {
    parent::_before();
    $this->styles = new Styles();
  }

  public function testItSetsDefaultCSSStyles() {
    expect($this->styles->getDefaultCustomStyles())->notEmpty();
  }

  public function testItProcessesAndRendersStyles() {
    $stylesheet = '
    /* some comment */
    input[name=first_name]    , input.some_class,     .some_class { color: red  ; background: blue; } .another_style { fonT-siZe: 20px                            }
    ';
    $extractedAndPrefixedStyles = $this->styles->prefixStyles($stylesheet, $prefix = 'mailpoet');
    // 1. comments should be stripped
    // 2. each selector should be refixed
    // 3. multiple spaces, missing semicolons should be fixed
    // 4. each style should be on a separate line
    $expectedResult = "mailpoet input[name=first_name], mailpoet input.some_class, mailpoet .some_class { color: red; background: blue; }" . PHP_EOL
    . "mailpoet .another_style { font-size: 20px; }";
    expect($extractedAndPrefixedStyles)->equals($expectedResult);
  }

  public function testItShouldNotRenderStylesForFormWithoutSettings() {
    $form = Fixtures::get('simple_form_body');
    $styles = $this->styles->renderFormSettingsStyles($form);
    expect($styles)->equals('');
  }

  public function testItShouldRenderBackgroundColour() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('background-color: red');
  }

  public function testItShouldRenderFontColour() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['fontColor' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('color: red');
  }

  public function testItShouldRenderBorder() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['border_size' => '22', 'border_color' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('border: 22px solid red');
  }

  public function testItShouldRenderPadding() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['form_padding' => '22'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('form.mailpoet_form {padding: 22px');
  }

  public function testItShouldRenderAlignment() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['alignment' => 'right'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('text-align: right');
  }

  public function testItShouldRenderBorderWithRadius() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['border_size' => '22', 'border_color' => 'red', 'border_radius' => '11'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('border: 22px solid red;border-radius: 11px');
  }

  public function testItShouldRenderImageBackground() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('background-image: url(xxx)');
    expect($styles)->contains('background-position: center');
    expect($styles)->contains('background-repeat: no-repeat');
    expect($styles)->contains('background-size: cover');
  }

  public function testItShouldRenderImageBackgroundTile() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx', 'background_image_display' => 'tile'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('background-image: url(xxx)');
    expect($styles)->contains('background-position: center');
    expect($styles)->contains('background-repeat: repeat');
    expect($styles)->contains('background-size: auto');
  }

  public function testItShouldRenderImageBackgroundFit() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx', 'background_image_display' => 'fit'];
    $styles = $this->styles->renderFormSettingsStyles($form, '#prefix');
    expect($styles)->contains('background-image: url(xxx)');
    expect($styles)->contains('background-position: center top');
    expect($styles)->contains('background-repeat: no-repeat');
    expect($styles)->contains('background-size: contain');
  }
}
