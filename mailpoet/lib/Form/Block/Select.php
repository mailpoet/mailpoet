<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Select {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  /** @var BlockStylesRenderer */
  private $blockStylesRenderer;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    BlockWrapperRenderer $wrapper,
    BlockStylesRenderer $blockStylesRenderer,
    WPFunctions $wp
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->wp = $wp;
    $this->blockStylesRenderer = $blockStylesRenderer;
  }

  public function render(array $block, array $formSettings, ?int $formId = null): string {
    $html = '';

    $fieldName = 'data[' . $this->rendererHelper->getFieldName($block) . ']';
    $automationId = ($block['id'] == 'status') ? ' data-automation-id="form_status"' : '';
    $inputId = $this->getInputId($block, $formSettings);
    $descriptionId = $this->getDescriptionId($block, $inputId);

    if ($inputId !== '') {
      $block['params']['input_id'] = $inputId;
    }

    $html .= $this->rendererHelper->renderLabel($block, $formSettings);
    if (!empty($block['params']['description'])) {
      $html .= '<p class="mailpoet_field_description"';
      if ($descriptionId !== '') {
        $html .= ' id="' . $this->wp->escAttr($descriptionId) . '"';
      }
      $html .= '>' . $this->wp->escHtml($block['params']['description']) . '</p>';
    }
    $html .= '<select
      class="mailpoet_select"
      ' . ($inputId !== '' ? 'id="' . $this->wp->escAttr($inputId) . '"' : '') . '
      name="' . $fieldName . '"'
      . $automationId
      . ($descriptionId !== '' ? ' aria-describedby="' . $this->wp->escAttr($descriptionId) . '"' : '')
      . ' style="' . $this->wp->escAttr($this->blockStylesRenderer->renderForSelect([], $formSettings)) . '"'
      . '>';

    if (isset($block['params']['label_within']) && $block['params']['label_within']) {
      $label = $this->rendererHelper->getFieldLabel($block);
      if (!empty($block['params']['required'])) {
        $label .= ' *';
      }
      $html .= '<option value="" disabled selected hidden>' . $this->wp->escHtml($label) . '</option>';
    } else {
      if (empty($block['params']['required'])) {
        $html .= '<option value="">-</option>';
      }
    }

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    foreach ($options as $option) {
      if (!empty($option['is_hidden'])) {
        continue;
      }

      $isSelected = '';

      if ($this->rendererHelper->getFieldValue($block) === $option['value']) {
        // use selected value if it exist
        $isSelected = ' selected="selected"';
      } elseif ((isset($option['is_checked']) && $option['is_checked']) && !($this->rendererHelper->getFieldValue($block))) {
        // use default value otherwise
        $isSelected = ' selected="selected"';
      }

      $isDisabled = (!empty($option['is_disabled'])) ? ' disabled="disabled"' : '';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= '<option value="' . $this->wp->escAttr($value) . '"' . $isSelected . $isDisabled . '>';
      $html .= $this->wp->escAttr($label);
      $html .= '</option>';
    }
    $html .= '</select>';

    $html .= $this->rendererHelper->renderErrorsContainer($block, $formId);

    return $this->wrapper->render($block, $html);
  }

  private function getInputId(array $block, array $formSettings): string {
    if (!empty($block['params']['input_id']) && is_scalar($block['params']['input_id'])) {
      return (string)$block['params']['input_id'];
    }
    if (isset($formSettings['id'])) {
      return 'form_' . (string)$block['id'] . '_' . (string)$formSettings['id'];
    }
    return '';
  }

  private function getDescriptionId(array $block, string $inputId): string {
    if (empty($block['params']['description'])) {
      return '';
    }
    if (!empty($block['params']['description_id']) && is_scalar($block['params']['description_id'])) {
      return (string)$block['params']['description_id'];
    }
    if ($inputId !== '') {
      return $inputId . '_description';
    }
    return '';
  }
}
