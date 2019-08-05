<?php

namespace MailPoet\Subscription;

use MailPoet\Models\Form as FormModel;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaRenderer {
  /** @var UrlHelper */
  private $url_helper;

  /** @var WPFunctions */
  private $wp;

  /** @var CaptchaSession */
  private $captcha_session;

  function __construct(UrlHelper $url_helper, WPFunctions $wp, CaptchaSession $captcha_session) {
    $this->url_helper = $url_helper;
    $this->wp = $wp;
    $this->captcha_session = $captcha_session;
  }

  public function getCaptchaPageTitle() {
    return $this->wp->__("Confirm you’re not a robot", 'mailpoet');
  }

  public function getCaptchaPageContent() {
    $fields = [
      [
        'id' => 'captcha',
        'type' => 'text',
        'params' => [
          'label' => $this->wp->__('Type in the input the characters you see in the picture above:', 'mailpoet'),
          'value' => '',
          'obfuscate' => false,
        ],
      ],
    ];

    $form = array_merge(
      $fields,
      [
        [
          'id' => 'submit',
          'type' => 'submit',
          'params' => [
            'label' => $this->wp->__('Subscribe', 'mailpoet'),
          ],
        ],
      ]
    );

    $captcha_session_form = $this->captcha_session->getFormData();
    $form_id = isset($captcha_session_form['form_id']) ? (int)$captcha_session_form['form_id'] : 0;
    $form_model = FormModel::findOne($form_id);
    if (!$form_model instanceof FormModel) {
      return false;
    }
    $form_model = $form_model->asArray();

    $form_html = '<form method="POST" ' .
      'action="' . admin_url('admin-post.php?action=mailpoet_subscription_form') . '" ' .
      'class="mailpoet_form mailpoet_captcha_form" ' .
      'novalidate>';
    $form_html .= '<input type="hidden" name="data[form_id]" value="' . $form_id . '" />';
    $form_html .= '<input type="hidden" name="api_version" value="v1" />';
    $form_html .= '<input type="hidden" name="endpoint" value="subscribers" />';
    $form_html .= '<input type="hidden" name="mailpoet_method" value="subscribe" />';
    $form_html .= '<input type="hidden" name="mailpoet_redirect" ' .
      'value="' . htmlspecialchars($this->url_helper->getCurrentUrl(), ENT_QUOTES) . '" />';

    $width = 220;
    $height = 60;
    $captcha_url = Url::getCaptchaImageUrl($width, $height);

    $form_html .= '<div class="mailpoet_form_hide_on_success">';
    $form_html .= '<p class="mailpoet_paragraph">';
    $form_html .= '<img class="mailpoet_captcha mailpoet_captcha_update" src="' . $captcha_url . '" width="' . $width . '" height="' . $height . '" title="' . $this->wp->__('Click to refresh the captcha', 'mailpoet') . '" />';
    $form_html .= '</p>';

    // subscription form
    $form_html .= FormRenderer::renderBlocks($form, $honeypot = false);
    $form_html .= '</div>';
    $form_html .= '<div class="mailpoet_message">';
    $form_html .= '<p class="mailpoet_validate_success" style="display:none;">' . $form_model['settings']['success_message'] . '</p>';
    $form_html .= '<p class="mailpoet_validate_error" style="display:none;"></p>';
    $form_html .= '</div>';
    $form_html .= '</form>';
    return $form_html;
  }
}
