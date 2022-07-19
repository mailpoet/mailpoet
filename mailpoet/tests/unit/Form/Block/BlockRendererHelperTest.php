<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class BlockRendererHelperTest extends \MailPoetUnitTest {
  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var MockObject & WPFunctions */
  private $wpMock;

  /** @var MockObject & FieldNameObfuscator */
  private $obfuscatorMock;

  private $block = [
    'type' => 'text',
    'name' => 'Custom text',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Input label',
      'required' => '',
      'hide_label' => '',
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->wpMock->method('escHtml')->will($this->returnArgument(0));
    $this->obfuscatorMock = $this->createMock(FieldNameObfuscator::class);
    $this->obfuscatorMock->method('obfuscate')->will($this->returnArgument(0));
    $this->rendererHelper = new BlockRendererHelper($this->obfuscatorMock, $this->wpMock);
  }

  public function testItShouldRenderLabel() {
    $block = $this->block;
    $label = $this->rendererHelper->renderLabel($block, []);
    expect($label)->regExp('#<label.*class="mailpoet_text_label".*>Input label</label>#m');

    $block['styles'] = ['bold' => '1'];
    $label = $this->rendererHelper->renderLabel($block, []);
    expect($label)->equals('<label class="mailpoet_text_label" style="font-weight: bold;">Input label</label>');

    $block['params']['required'] = '1';
    $block['styles'] = [];
    $label = $this->rendererHelper->renderLabel($block, []);
    expect($label)->equals('<label class="mailpoet_text_label" >Input label <span class="mailpoet_required">*</span></label>');

    $block['params']['hide_label'] = '1';
    $label = $this->rendererHelper->renderLabel($block, []);
    expect($label)->equals('');
  }

  public function testItShouldRenderLegend() {
    $block = $this->block;
    $label = $this->rendererHelper->renderLegend($block, []);
    expect($label)->regExp('#<legend.*class="mailpoet_text_label".*>Input label</legend>#m');

    $block['styles'] = ['bold' => '1'];
    $label = $this->rendererHelper->renderLegend($block, []);
    expect($label)->equals('<legend class="mailpoet_text_label" style="font-weight: bold;">Input label</legend>');

    $block['params']['required'] = '1';
    $block['styles'] = [];
    $label = $this->rendererHelper->renderLegend($block, []);
    expect($label)->equals('<legend class="mailpoet_text_label" >Input label <span class="mailpoet_required">*</span></legend>');

    $block['params']['hide_label'] = '1';
    $label = $this->rendererHelper->renderLegend($block, []);
    expect($label)->equals('');
  }

  public function testItShouldRenderPlaceholder() {
    $block = $this->block;
    $placeholder = $this->rendererHelper->renderInputPlaceholder($block);
    expect($placeholder)->equals('');

    $block['params']['label_within'] = '1';
    $placeholder = $this->rendererHelper->renderInputPlaceholder($block);
    expect($placeholder)->equals(' placeholder="Input label" ');

    $block['params']['required'] = '1';
    $placeholder = $this->rendererHelper->renderInputPlaceholder($block);
    expect($placeholder)->equals(' placeholder="Input label *" ');
  }

  public function testItShouldRenderInputValidations() {
    $block = $this->block;
    $validation = $this->rendererHelper->getInputValidation($block);
    expect($validation)->equals('');

    $block['params']['required'] = '1';
    $validation = $this->rendererHelper->getInputValidation($block, [], 2);
    expect($validation)->equals('data-parsley-required="true" data-parsley-errors-container=".mailpoet_error_1_2" data-parsley-required-message="This field is required."');

    $block['params']['required'] = '0';
    $block['id'] = 'email';
    $validation = $this->rendererHelper->getInputValidation($block);
    expect($validation)->equals('data-parsley-required="true" data-parsley-minlength="6" data-parsley-maxlength="150" data-parsley-type-message="This value should be a valid email."');

    $block = $this->block;
    $block['params']['validate'] = 'phone';
    $validation = $this->rendererHelper->getInputValidation($block);
    expect($validation)->equals('data-parsley-pattern="^[\d\+\-\.\(\)\/\s]*$" data-parsley-error-message="Please specify a valid phone number."');

    $block = $this->block;
    $block['type'] = 'radio';
    $validation = $this->rendererHelper->getInputValidation($block);
    expect($validation)->equals('data-parsley-group="custom_field_1" data-parsley-errors-container=".mailpoet_error_1" data-parsley-required-message="Please select at least one option."');

    $block = $this->block;
    $block['type'] = 'date';
    $validation = $this->rendererHelper->getInputValidation($block);
    expect($validation)->equals('data-parsley-group="custom_field_1" data-parsley-errors-container=".mailpoet_error_1"');

    $block = $this->block;
    $validation = $this->rendererHelper->getInputValidation($block, ['custom']);
    expect($validation)->equals('data-parsley-0="custom"');
  }

  public function testItShouldRenderInputValidationsWithFormId(): void {
    $block = $this->block;
    $block['type'] = 'radio';
    $validation = $this->rendererHelper->getInputValidation($block, [], 1);
    expect($validation)->equals('data-parsley-group="custom_field_1" data-parsley-errors-container=".mailpoet_error_1_1" data-parsley-required-message="Please select at least one option."');

    $block = $this->block;
    $block['type'] = 'checkbox';
    $validation = $this->rendererHelper->getInputValidation($block, [], 2);
    expect($validation)->equals('data-parsley-group="custom_field_1" data-parsley-errors-container=".mailpoet_error_1_2" data-parsley-required-message="Please select at least one option."');

    $block = $this->block;
    $block['type'] = 'date';
    $validation = $this->rendererHelper->getInputValidation($block, [], 3);
    expect($validation)->equals('data-parsley-group="custom_field_1" data-parsley-errors-container=".mailpoet_error_1_3"');

    $block = $this->block;
    $block['id'] = 'segments';
    $validation = $this->rendererHelper->getInputValidation($block, [], 4);
    expect($validation)->equals('data-parsley-required="true" data-parsley-group="segments" data-parsley-errors-container=".mailpoet_error_segments_4" data-parsley-required-message="Please select a list."');
  }

  public function testItShouldObfuscateFieldNameIfNeeded() {
    $block = $this->block;
    $fieldName = $this->rendererHelper->getFieldName($block);
    expect($fieldName)->equals('cf_1');

    $obfuscatorMock = $this->createMock(FieldNameObfuscator::class);
    $obfuscatorMock->expects($this->once())->method('obfuscate')->willReturn('xyz');
    $renderer = new BlockRendererHelper($obfuscatorMock, $this->wpMock);

    $block['id'] = 'email';
    $fieldName = $renderer->getFieldName($block);
    expect($fieldName)->equals('xyz');
  }
}
