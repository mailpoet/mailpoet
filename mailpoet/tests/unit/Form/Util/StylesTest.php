<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Util;

use Codeception\Util\Fixtures;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Util\Styles;

class StylesTest extends \MailPoetUnitTest {
  /** @var Styles */
  private $styles;

  public function _before() {
    parent::_before();
    $this->styles = new Styles();
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
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->equals('');
  }

  public function testItShouldRenderBackgroundColour() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('background: red');
  }

  public function testItShouldRenderBackgroundGradient() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['gradient' => 'linear-gradient(#fff, #000)'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('background: linear-gradient(#fff, #000)');
  }

  public function testItShouldRenderFontColour() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['fontColor' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('color: red');
  }

  public function testItShouldRenderBorder() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['border_size' => '22', 'border_color' => 'red'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('border: 22px solid red');
  }

  public function testItShouldRenderPadding() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['form_padding' => '22'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('form.mailpoet_form {padding: 22px');
  }

  public function testItShouldNotRenderPaddingForMobile() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['form_padding' => '22'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_SLIDE_IN);
    expect($styles)->stringContainsString('min-width: 500px) {#prefix {padding: 22px;');
  }

  public function testItShouldRenderAlignment() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['alignment' => 'right'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('text-align: right');
  }

  public function testItShouldRenderBorderWithRadius() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['border_size' => '22', 'border_color' => 'red', 'border_radius' => '11'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('border: 22px solid red;border-radius: 11px');
  }

  public function testItShouldRenderImageBackground() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    list($styleWithoutMedia, $mediaStyles) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('background: url(xxx) center / cover no-repeat');
    expect($mediaStyles)->stringContainsString('background-image: none');
  }

  public function testItShouldRenderImageBackgroundTile() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx', 'background_image_display' => 'tile'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('background: url(xxx) center / auto repeat');
  }

  public function testItShouldRenderImageBackgroundFit() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_image_url' => 'xxx', 'background_image_display' => 'fit'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('background: url(xxx) center top / contain no-repeat');
  }

  public function testItShouldRenderImageWithGradientBackground() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = [
      'background_image_url' => 'xxx',
      'gradient' => 'linear-gradient(#fff, #000)',
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    list($styleWithoutMedia, $mediaStyles) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('background: url(xxx) center / cover no-repeat, linear-gradient(#fff, #000)');
    expect($mediaStyles)->stringContainsString('background: linear-gradient(#fff, #000)');
  }

  public function testItShouldRenderImageWithColorBackground() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = [
      'background_image_url' => 'xxx',
      'backgroundColor' => 'red',
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    list($styleWithoutMedia, $mediaStyles) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('background: url(xxx) center / cover no-repeat, red');
    expect($mediaStyles)->stringContainsString('background: red');
  }

  public function testItShouldRenderErrorMessageColor() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['error_validation_color' => 'xxx'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('#prefix .mailpoet_validate_error {color: xxx}');

    $styles = $this->styles->renderFormMessageStyles($this->createForm($form), '#prefix');
    expect($styles)->stringContainsString('#prefix .mailpoet_validate_error {color: xxx}');
  }

  public function testItShouldRenderSuccessMessageColor() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['success_validation_color' => 'xxx'];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    expect($styles)->stringContainsString('#prefix .mailpoet_validate_success {color: xxx}');

    $styles = $this->styles->renderFormMessageStyles($this->createForm($form), '#prefix');
    expect($styles)->stringContainsString('#prefix .mailpoet_validate_success {color: xxx}');
  }

  public function testItRendersWidthCssForBellowPost() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_BELOW_POST);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringNotContainsString('width:');
    expect($styleWithoutMedia)->stringNotContainsString('max-width:');
    // Pixel
    $width = [
      'unit' => 'pixel',
      'value' => '900',
    ];
    $form['settings'] = [
      'form_placement' => [
        'below_posts' => ['styles' => ['width' => $width]],
      ],
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_BELOW_POST);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 900px;');
    expect($styleWithoutMedia)->stringNotContainsString('max-width:');
  }

  public function testItRendersWidthCssForFixedBar() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_FIXED_BAR);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('max-width: 960px;');
    // Percent
    $width = [
      'unit' => 'percent',
      'value' => '90',
    ];
    $form['settings'] = [
      'form_placement' => [
        'fixed_bar' => ['styles' => ['width' => $width]],
      ],
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_FIXED_BAR);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 90%;max-width: 100%;');
  }

  public function testItRendersWidthCssForPopup() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_POPUP);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 560px;max-width: 560px;');
    // Pixel
    $width = [
      'unit' => 'pixel',
      'value' => '900',
    ];
    $form['settings'] = [
      'form_placement' => [
        'popup' => ['styles' => ['width' => $width]],
      ],
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_POPUP);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 900px;max-width: 100vw;');
  }

  public function testItRendersWidthCssForSlideIn() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_SLIDE_IN);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('max-width: 600px;min-width: 350px;');
    // Percent
    $width = [
      'unit' => 'percent',
      'value' => '90',
    ];
    $form['settings'] = [
      'form_placement' => [
        'slide_in' => ['styles' => ['width' => $width]],
      ],
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_SLIDE_IN);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 90%;max-width: 100vw;');
  }

  public function testItRendersWidthCssForOthers() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['backgroundColor' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringNotContainsString('width:');
    expect($styleWithoutMedia)->stringNotContainsString('max-width:');
    // Percent
    $width = [
      'unit' => 'percent',
      'value' => '90',
    ];
    $form['settings'] = [
      'form_placement' => [
        'others' => ['styles' => ['width' => $width]],
      ],
    ];
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_OTHERS);
    list($styleWithoutMedia) = explode('@media ', $styles);
    expect($styleWithoutMedia)->stringContainsString('width: 90%;');
    expect($styleWithoutMedia)->stringNotContainsString('max-width:');
  }

  public function testItRendersSlideInSpecificStyles() {
    $form = Fixtures::get('simple_form_body');
    $form['settings'] = ['background_color' => 'red'];
    // BC Style
    $styles = $this->styles->renderFormSettingsStyles($this->createForm($form), '#prefix', FormEntity::DISPLAY_TYPE_SLIDE_IN);
    expect($styles)->stringContainsString('#prefix.mailpoet_form_slide_in { border-bottom-left-radius: 0; border-bottom-right-radius: 0; }');
    expect($styles)->stringContainsString('#prefix.mailpoet_form_position_right { border-top-right-radius: 0; }');
    expect($styles)->stringContainsString('#prefix.mailpoet_form_position_left { border-top-left-radius: 0; }');
  }

  private function createForm(array $formData): FormEntity {
    $form = new FormEntity($formData['name'] ?? 'name');
    if (isset($formData['settings'])) {
      $form->setSettings($formData['settings']);
    }if (isset($formData['body'])) {
      $form->setBody($formData['body']);
    }
    return $form;
  }
}
