<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Captcha;

use MailPoet\Config\Env;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util\Styles;
use MailPoet\Util\Url as UrlHelper;

class CaptchaFormRenderer {
  /** @var UrlHelper */
  private $urlHelper;

  /** @var CaptchaSession */
  private $captchaSession;

  /** @var CaptchaPhrase */
  private $captchaPhrase;

  /** @var CaptchaUrlFactory */
  private $captchaUrlFactory;

  /** @var FormRenderer */
  private $formRenderer;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var Styles */
  private $styles;

  public function __construct(
    UrlHelper $urlHelper,
    CaptchaSession $captchaSession,
    CaptchaPhrase $captchaPhrase,
    CaptchaUrlFactory $urlFactory,
    FormsRepository $formsRepository,
    FormRenderer $formRenderer,
    Styles $styles
  ) {
    $this->urlHelper = $urlHelper;
    $this->captchaSession = $captchaSession;
    $this->captchaPhrase = $captchaPhrase;
    $this->captchaUrlFactory = $urlFactory;
    $this->formRenderer = $formRenderer;
    $this->formsRepository = $formsRepository;
    $this->styles = $styles;
  }

  public function render(array $data) {
    $sessionId = (isset($data['captcha_session_id']) && is_string($data['captcha_session_id']))
      ? $data['captcha_session_id']
      : null;

    if (!$sessionId) {
      return false;
    }

    if ($data['referrer_form'] == CaptchaUrlFactory::REFERER_MP_FORM) {
      return $this->renderFormInSubscriptionForm($sessionId);
    } elseif ($data['referrer_form'] == CaptchaUrlFactory::REFERER_WP_FORM) {
      return $this->renderFormInWPRegisterForm($data, 'wp-submit');
    } elseif ($data['referrer_form'] == CaptchaUrlFactory::REFERER_WC_FORM) {
      return $this->renderFormInWPRegisterForm($data, 'register');
    }

    return false;
  }

  private function renderFormInSubscriptionForm($sessionId) {
    $captchaSessionForm = $this->captchaSession->getFormData($sessionId);
    $showSuccessMessage = !empty($_GET['mailpoet_success']);
    $showErrorMessage = !empty($_GET['mailpoet_error']);

    $formId = 0;
    if (isset($captchaSessionForm['form_id'])) {
      $formId = (int)$captchaSessionForm['form_id'];
    } elseif ($showSuccessMessage) {
      $formId = (int)$_GET['mailpoet_success'];
    } elseif ($showErrorMessage) {
      $formId = (int)$_GET['mailpoet_error'];
    }

    $formModel = $this->formsRepository->findOneById($formId);
    if (!$formModel instanceof FormEntity) {
      return false;
    } elseif ($showSuccessMessage) {
      // Display a success message in a no-JS flow
      return $this->renderFormMessages($formModel, true);
    }

    $redirectUrl = htmlspecialchars($this->urlHelper->getCurrentUrl(), ENT_QUOTES);
    $hiddenFields = '<input type="hidden" name="data[form_id]" value="' . $formId . '" />';
    $hiddenFields .= '<input type="hidden" name="data[captcha_session_id]" value="' . htmlspecialchars($sessionId) . '" />';
    $hiddenFields .= '<input type="hidden" name="api_version" value="v1" />';
    $hiddenFields .= '<input type="hidden" name="endpoint" value="subscribers" />';
    $hiddenFields .= '<input type="hidden" name="mailpoet_method" value="subscribe" />';
    $hiddenFields .= '<input type="hidden" name="mailpoet_redirect" value="' . $redirectUrl . '" />';

    $actionUrl = admin_url('admin-post.php?action=mailpoet_subscription_form');

    $submitBlocks = $formModel->getBlocksByTypes(['submit']);
    $submitLabel = count($submitBlocks) && $submitBlocks[0]['params']['label']
      ? $submitBlocks[0]['params']['label']
      : __('Subscribe', 'mailpoet');

    $afterSubmitElement = $this->renderFormMessages($formModel, false, $showErrorMessage);

    $styles = $this->styles->renderFormMessageStyles($formModel, '#mailpoet_captcha_form');
    $styles = '<style>' . $styles . '</style>';

    return $this->renderForm($sessionId, $hiddenFields, $actionUrl, $submitLabel, $afterSubmitElement, $styles);
  }

  private function renderFormInWPRegisterForm(array $data, string $submitLabelKey) {
    $sessionId = $data['captcha_session_id'];

    unset($data['captcha_session_id']);
    // The 'name' attr is required in this format for the refresh button to work
    $hiddenFields = '<input type="hidden" name="data[captcha_session_id]" value="' . htmlspecialchars($sessionId) . '" />';

    $actionUrl = $data['referrer_form_url'];
    unset($data['referrer_form_url']);

    unset($data['referrer_form']);
    foreach ($data as $key => $value) {
      $hiddenFields .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />';
    }

    $submitLabel = $data[$submitLabelKey] ?? esc_attr_e('Register'); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain

    return $this->renderForm($sessionId, $hiddenFields, $actionUrl, $submitLabel);
  }

  private function renderForm(
    $sessionId,
    $hiddenFields,
    $actionUrl,
    $submitLabel,
    $afterSubmitElement = null,
    $styles = null
  ) {
    $this->captchaPhrase->createPhrase($sessionId);

    $fields = [
      [
        'id' => 'captcha',
        'type' => 'text',
        'params' => [
          'label' => __('Type in the characters you see in the picture above:', 'mailpoet'),
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
            'label' => $submitLabel,
          ],
        ],
      ],
    );

    if ($afterSubmitElement) {
      // The 'mailpoet_form' class alter the form's submission behavior
      // Refer to mailpoet/assets/js/src/public.tsx
      $classes = 'mailpoet_form mailpoet_captcha_form';
    } else {
      $classes = 'mailpoet_captcha_form';
    }

    $formHtml = '<form method="POST" action="' . $actionUrl . '" class="' . $classes . '" id="mailpoet_captcha_form" novalidate>';
    $formHtml .= $hiddenFields;

    $width = 220;
    $height = 60;
    $captchaUrl = $this->captchaUrlFactory->getCaptchaImageUrl($width, $height, $sessionId);
    $mp3CaptchaUrl = $this->captchaUrlFactory->getCaptchaAudioUrl($sessionId);
    $reloadIcon = Env::$assetsUrl . '/img/icons/image-rotate.svg';
    $playIcon = Env::$assetsUrl . '/img/icons/controls-volumeon.svg';

    $formHtml .= '<div class="mailpoet_form_hide_on_success">';
    $formHtml .= '<p class="mailpoet_paragraph">';
    $formHtml .= '<img class="mailpoet_captcha" src="' . $captchaUrl . '" width="' . $width . '" height="' . $height . '" title="' . esc_attr__('CAPTCHA', 'mailpoet') . '" />';
    $formHtml .= '</p>';
    $formHtml .= '<button type="button" class="mailpoet_icon_button mailpoet_captcha_update" title="' . esc_attr(__('Reload CAPTCHA', 'mailpoet')) . '"><img src="' . $reloadIcon . '" alt="" /></button>';
    $formHtml .= '<button type="button" class="mailpoet_icon_button mailpoet_captcha_audio" title="' . esc_attr(__('Play CAPTCHA', 'mailpoet')) . '"><img src="' . $playIcon . '" alt="" /></button>';
    $formHtml .= '<audio class="mailpoet_captcha_player">';
    $formHtml .= '<source src="' . $mp3CaptchaUrl . '" type="audio/mpeg">';
    $formHtml .= '</audio>';

    $formHtml .= $this->formRenderer->renderBlocks($form, [], null, $honeypot = false);
    $formHtml .= '</div>';

    if ($afterSubmitElement) {
      $formHtml .= $afterSubmitElement;
    }

    $formHtml .= '</form>';

    if ($styles) {
      $formHtml .= $styles;
    }

    return $formHtml;
  }

  private function renderFormMessages(
    FormEntity $formModel,
    $showSuccessMessage = false,
    $showErrorMessage = false
  ) {
    $settings = $formModel->getSettings() ?? [];
    $errorMessage = __('The characters you entered did not match the CAPTCHA image. Please try again with this new image.', 'mailpoet');

    $formHtml = '<div class="mailpoet_message" role="alert" aria-live="assertive">';
    $formHtml .= '<p class="mailpoet_validate_success" ' . ($showSuccessMessage ? '' : ' style="display:none;"') . '>' . $settings['success_message'] . '</p>';
    $formHtml .= '<p class="mailpoet_validate_error" ' . ($showErrorMessage ? '' : ' style="display:none;"') . '>' . $errorMessage . '</p>';
    $formHtml .= '</div>';

    return $formHtml;
  }
}
