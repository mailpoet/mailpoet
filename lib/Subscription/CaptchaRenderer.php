<?php

namespace MailPoet\Subscription;

use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\Form as FormModel;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaRenderer {
  /** @var UrlHelper */
  private $urlHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var CaptchaSession */
  private $captchaSession;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var FormRenderer */
  private $formRenderer;

  public function __construct(
    UrlHelper $urlHelper,
    WPFunctions $wp,
    CaptchaSession $captchaSession,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    FormRenderer $formRenderer
  ) {
    $this->urlHelper = $urlHelper;
    $this->wp = $wp;
    $this->captchaSession = $captchaSession;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->formRenderer = $formRenderer;
  }

  public function getCaptchaPageTitle() {
    return $this->wp->__("Confirm you’re not a robot", 'mailpoet');
  }

  public function getCaptchaPageContent($sessionId) {
    $this->captchaSession->init($sessionId);
    $fields = [
      [
        'id' => 'captcha',
        'type' => 'text',
        'params' => [
          'label' => $this->wp->__('Type in the characters you see in the picture above:', 'mailpoet'),
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

    $captchaSessionForm = $this->captchaSession->getFormData();
    $formId = 0;

    $showSuccessMessage = !empty($_GET['mailpoet_success']);
    $showErrorMessage = !empty($_GET['mailpoet_error']);

    if (isset($captchaSessionForm['form_id'])) {
      $formId = (int)$captchaSessionForm['form_id'];
    } elseif ($showSuccessMessage) {
      $formId = (int)$_GET['mailpoet_success'];
    } elseif ($showErrorMessage) {
      $formId = (int)$_GET['mailpoet_error'];
    }

    $formModel = FormModel::findOne($formId);
    if (!$formModel instanceof FormModel) {
      return false;
    }
    $formModel = $formModel->asArray();

    if ($showSuccessMessage) {
      // Display a success message in a no-JS flow
      return $this->renderFormMessages($formModel, $showSuccessMessage);
    }

    $formHtml = '<form method="POST" ' .
      'action="' . admin_url('admin-post.php?action=mailpoet_subscription_form') . '" ' .
      'class="mailpoet_form mailpoet_captcha_form" ' .
      'novalidate>';
    $formHtml .= '<input type="hidden" name="data[form_id]" value="' . $formId . '" />';
    $formHtml .= '<input type="hidden" name="data[captcha_session_id]" value="' . $this->captchaSession->getId() . '" />';
    $formHtml .= '<input type="hidden" name="api_version" value="v1" />';
    $formHtml .= '<input type="hidden" name="endpoint" value="subscribers" />';
    $formHtml .= '<input type="hidden" name="mailpoet_method" value="subscribe" />';
    $formHtml .= '<input type="hidden" name="mailpoet_redirect" ' .
      'value="' . htmlspecialchars($this->urlHelper->getCurrentUrl(), ENT_QUOTES) . '" />';

    $width = 220;
    $height = 60;
    $captchaUrl = $this->subscriptionUrlFactory->getCaptchaImageUrl($width, $height, $this->captchaSession->getId());

    $formHtml .= '<div class="mailpoet_form_hide_on_success">';
    $formHtml .= '<p class="mailpoet_paragraph">';
    $formHtml .= '<img class="mailpoet_captcha mailpoet_captcha_update" src="' . $captchaUrl . '" width="' . $width . '" height="' . $height . '" title="' . $this->wp->__('Click to refresh the CAPTCHA', 'mailpoet') . '" />';
    $formHtml .= '</p>';

    // subscription form
    $formHtml .= $this->formRenderer->renderBlocks($form, [], $honeypot = false);
    $formHtml .= '</div>';
    $formHtml .= $this->renderFormMessages($formModel, $showSuccessMessage, $showErrorMessage);
    $formHtml .= '</form>';
    return $formHtml;
  }

  private function renderFormMessages(
    array $formModel,
    $showSuccessMessage = false,
    $showErrorMessage = false
  ) {
    $formHtml = '<div class="mailpoet_message">';
    $formHtml .= '<p class="mailpoet_validate_success" ' . ($showSuccessMessage ? '' : ' style="display:none;"') . '>' . $formModel['settings']['success_message'] . '</p>';
    $formHtml .= '<p class="mailpoet_validate_error" ' . ($showErrorMessage ? '' : ' style="display:none;"') . '>' . $this->wp->__('The characters you entered did not match the CAPTCHA image. Please try again with this new image.', 'mailpoet') . '</p>';
    $formHtml .= '</div>';
    return $formHtml;
  }
}
