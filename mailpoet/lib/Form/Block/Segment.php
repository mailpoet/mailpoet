<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;

class Segment {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    BlockWrapperRenderer $wrapper,
    WPFunctions $wp,
    SegmentsRepository $segmentsRepository
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->wp = $wp;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function render(array $block, array $formSettings, ?int $formId = null): string {
    if (($block['params']['display_mode'] ?? null) === 'manage_subscription_choices') {
      return $this->renderManageSubscriptionChoices($block, $formSettings, $formId);
    }

    $html = '';

    $fieldName = 'data[' . $this->rendererHelper->getFieldName($block) . ']';
    $fieldValidation = $this->rendererHelper->getInputValidation($block, [], $formId);

    // Add fieldset around the checkboxes
    $html .= '<fieldset>';
    $html .= $this->rendererHelper->renderLegend($block, $formSettings);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $options = array_map(function ($option) {
      $option['id'] = intval($option['id']);
      return $option;
    }, $options);
    $segmentsNamesMap = $this->getSegmentsNames($options);

    foreach ($options as $option) {
      if (!isset($option['id']) || !isset($segmentsNamesMap[$option['id']])) continue;

      $id = $this->wp->wpUniqueId('mailpoet_segment_');
      $isChecked = (isset($option['is_checked']) && $option['is_checked']) ? 'checked="checked"' : '';

      $html .= '<label class="mailpoet_checkbox_label" for="' . $id . '" '
        . $this->rendererHelper->renderFontStyle($formSettings)
        . '>';
      $html .= '<input type="checkbox" class="mailpoet_checkbox" ';
      $html .= 'id="' . $id . '" ';
      $html .= 'name="' . $fieldName . '[]" ';
      $html .= 'value="' . $option['id'] . '" ' . $isChecked . ' ';
      $html .= $fieldValidation;
      $html .= ' /> ' . $this->wp->escAttr($segmentsNamesMap[$option['id']]);
      $html .= '</label>';
    }

    $html .= $this->rendererHelper->renderErrorsContainer($block, $formId);

    // End fieldset around checkboxes
    $html .= '</fieldset>';

    return $this->wrapper->render($block, $html);
  }

  private function renderManageSubscriptionChoices(array $block, array $formSettings, ?int $formId = null): string {
    $options = $this->getManageSubscriptionOptions($block['params']['values'] ?? []);
    if (!$options) {
      return '';
    }

    $html = '<fieldset class="mailpoet-manage-subscription-lists" data-automation-id="manage_subscription_lists">';
    $html .= $this->rendererHelper->renderLegend($block, $formSettings);
    if (!empty($block['params']['description'])) {
      $html .= '<p class="mailpoet-manage-subscription-lists-description">' . $this->wp->escHtml($block['params']['description']) . '</p>';
    }

    foreach ($options as $option) {
      $segmentId = $option['id'];
      $name = $option['name'];
      $description = $option['public_description'];
      $yesId = $this->wp->wpUniqueId('mailpoet_segment_choice_');
      $noId = $this->wp->wpUniqueId('mailpoet_segment_choice_');
      $fieldsetLabelId = 'mailpoet_segment_choice_' . $segmentId . '_label';
      $fieldsetDescriptionId = 'mailpoet_segment_choice_' . $segmentId . '_description';
      $describedBy = $description !== '' ? ' aria-describedby="' . $this->wp->escAttr($fieldsetDescriptionId) . '"' : '';
      $yesChecked = $option['is_checked'] ? ' checked="checked"' : '';
      $noChecked = $option['is_checked'] ? '' : ' checked="checked"';

      $html .= '<div class="mailpoet-manage-subscription-list-row" data-automation-id="manage_subscription_list_' . $this->wp->escAttr($segmentId) . '">';
      $html .= '<div class="mailpoet-manage-subscription-list-copy">';
      $html .= '<div class="mailpoet-manage-subscription-list-name" id="' . $this->wp->escAttr($fieldsetLabelId) . '">' . $this->wp->escHtml($name) . '</div>';
      if ($description !== '') {
        $html .= '<div class="mailpoet-manage-subscription-list-description" id="' . $this->wp->escAttr($fieldsetDescriptionId) . '">' . $this->wp->escHtml($description) . '</div>';
      }
      $html .= '</div>';

      $html .= '<fieldset class="mailpoet-manage-subscription-list-choice" aria-labelledby="' . $this->wp->escAttr($fieldsetLabelId) . '"' . $describedBy . '>';
      $html .= '<legend class="mailpoet-manage-subscription-choice-legend">' . $this->wp->escHtml(sprintf(
        // translators: %s is the name of a mailing list.
        __('Receive %s?', 'mailpoet'),
        $name
      )) . '</legend>';

      $html .= '<label class="mailpoet-manage-subscription-choice-label" for="' . $this->wp->escAttr($yesId) . '">';
      $html .= '<input type="radio" class="mailpoet_radio" id="' . $this->wp->escAttr($yesId) . '" name="data[segment_choices][' . $this->wp->escAttr($segmentId) . ']" value="subscribed"' . $yesChecked . ' data-automation-id="manage_subscription_list_' . $this->wp->escAttr($segmentId) . '_yes" />';
      $html .= '<span>' . $this->wp->escHtml(__('Yes', 'mailpoet')) . '</span>';
      $html .= '</label>';

      $html .= '<label class="mailpoet-manage-subscription-choice-label" for="' . $this->wp->escAttr($noId) . '">';
      $html .= '<input type="radio" class="mailpoet_radio" id="' . $this->wp->escAttr($noId) . '" name="data[segment_choices][' . $this->wp->escAttr($segmentId) . ']" value="unsubscribed"' . $noChecked . ' data-automation-id="manage_subscription_list_' . $this->wp->escAttr($segmentId) . '_no" />';
      $html .= '<span>' . $this->wp->escHtml(__('No', 'mailpoet')) . '</span>';
      $html .= '</label>';
      $html .= '</fieldset>';
      $html .= '</div>';
    }

    $html .= $this->rendererHelper->renderErrorsContainer($block, $formId);
    $html .= '</fieldset>';

    return $this->wrapper->render($block, $html);
  }

  private function getManageSubscriptionOptions($values): array {
    if (!is_array($values)) {
      return [];
    }

    $options = [];
    foreach ($values as $value) {
      if (!is_array($value) || empty($value['id']) || !isset($value['name'])) {
        continue;
      }
      $idValue = $value['id'];
      $nameValue = $value['name'];
      $descriptionValue = $value['public_description'] ?? '';

      if ((!is_int($idValue) && !is_string($idValue)) || !is_scalar($nameValue)) {
        continue;
      }

      $id = (int)$idValue;
      if ($id <= 0) {
        continue;
      }
      $options[] = [
        'id' => $id,
        'name' => (string)$nameValue,
        'public_description' => is_scalar($descriptionValue) ? trim((string)$descriptionValue) : '',
        'is_checked' => !empty($value['is_checked']),
      ];
    }
    return $options;
  }

  private function getSegmentsNames($values): array {
    $ids = array_column($values, 'id');
    $segments = $this->segmentsRepository->findByIds($ids);
    $namesMap = [];
    foreach ($segments as $segment) {
      $namesMap[$segment->getId()] = $segment->getName();
    }
    return $namesMap;
  }
}
