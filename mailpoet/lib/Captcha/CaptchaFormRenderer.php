<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Captcha;

use MailPoet\Config\Env;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util\Styles;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WooCommerce\Helper as WooHelper;
use MailPoet\WP\Functions as WPFunctions;

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

  /** @var CaptchaHooks */
  private $captchaHooks;

  /** @var WooHelper */
  private $wooHelper;

  private $wp;

  /** @var array<string, string> */
  private $renderedForms = [];

  public function __construct(
    UrlHelper $urlHelper,
    CaptchaSession $captchaSession,
    CaptchaPhrase $captchaPhrase,
    CaptchaUrlFactory $urlFactory,
    FormsRepository $formsRepository,
    FormRenderer $formRenderer,
    Styles $styles,
    CaptchaHooks $captchaHooks,
    WooHelper $wooHelper,
    WPFunctions $wp
  ) {
    $this->urlHelper = $urlHelper;
    $this->captchaSession = $captchaSession;
    $this->captchaPhrase = $captchaPhrase;
    $this->captchaUrlFactory = $urlFactory;
    $this->formRenderer = $formRenderer;
    $this->formsRepository = $formsRepository;
    $this->styles = $styles;
    $this->captchaHooks = $captchaHooks;
    $this->wooHelper = $wooHelper;
    $this->wp = $wp;
  }

  public function render(array $data) {
    $sessionId = (isset($data['captcha_session_id']) && is_string($data['captcha_session_id']))
      ? $data['captcha_session_id']
      : null;

    if (!$sessionId) {
      return false;
    }

    $ref = $data['referrer_form'] ?? null;
    if ($ref === CaptchaUrlFactory::REFERER_MP_FORM) {
      return $this->renderFormInSubscriptionForm($sessionId);
    }

    if (!$this->captchaHooks->isEnabled()) {
      return false;
    }

    if ($ref === CaptchaUrlFactory::REFERER_WP_FORM) {
      return $this->renderFormInWPRegisterForm($sessionId, $ref, 'wp-submit');
    } elseif ($ref === CaptchaUrlFactory::REFERER_WC_FORM) {
      return $this->renderFormInWPRegisterForm($sessionId, $ref, 'register');
    }

    return false;
  }

  private function renderFormInSubscriptionForm($sessionId) {
    $captchaSessionForm = $this->captchaSession->getFormData($sessionId);
    $showSuccessMessage = !empty($_GET['mailpoet_success']);
    $showErrorMessage = !empty($_GET['mailpoet_error']);

    $formId = 0;
    if (isset($captchaSessionForm['form_id']) && is_numeric($captchaSessionForm['form_id'])) {
      $formId = (int)$captchaSessionForm['form_id'];
    } elseif ($showSuccessMessage && is_numeric($_GET['mailpoet_success'])) {
      $formId = (int)$_GET['mailpoet_success'];
    } elseif ($showErrorMessage && is_numeric($_GET['mailpoet_error'])) {
      $formId = (int)$_GET['mailpoet_error'];
    }

    $formModel = $this->formsRepository->findOneById($formId);
    if (!$formModel instanceof FormEntity) {
      return false;
    } elseif ($showSuccessMessage) {
      // Display a success message in a no-JS flow
      return $this->renderFormMessages($formModel, true);
    }

    $redirectUrl = $this->urlHelper->getCurrentUrl();
    $hiddenFields = '<input type="hidden" name="data[form_id]" value="' . $this->wp->escAttr($formId) . '" />';
    $hiddenFields .= '<input type="hidden" name="data[captcha_session_id]" value="' . $this->wp->escAttr($sessionId) . '" />';
    $hiddenFields .= '<input type="hidden" name="api_version" value="v1" />';
    $hiddenFields .= '<input type="hidden" name="endpoint" value="subscribers" />';
    $hiddenFields .= '<input type="hidden" name="mailpoet_method" value="subscribe" />';
    $hiddenFields .= '<input type="hidden" name="mailpoet_redirect" value="' . $this->wp->escUrl($redirectUrl) . '" />';
    $hiddenFields .= '<input type="hidden" name="token" value="' . $this->wp->escAttr($this->wp->wpCreateNonce('mailpoet_token')) . '" />';

    $actionUrl = admin_url('admin-post.php?action=mailpoet_subscription_form');

    $submitBlocks = $formModel->getBlocksByTypes(['submit']);
    $submitLabel = count($submitBlocks) && $submitBlocks[0]['params']['label']
      ? $submitBlocks[0]['params']['label']
      : __('Subscribe', 'mailpoet');

    $afterSubmitElement = $this->renderFormMessages($formModel, false, $showErrorMessage);

    $styles = $this->styles->renderFormMessageStyles($formModel, '#mailpoet_captcha_form');
    $styles = '<style>' . $styles . '</style>';

    if ($this->captchaSession->exists($sessionId) && $this->captchaPhrase->getPhrase($sessionId) === null) {
      try {
        $this->captchaSession->registerNewSession();
      } catch (CaptchaSessionLimitException $e) {
        return '<p>' . $this->wp->escHtml($e->getMessage()) . '</p>';
      }
    }

    return $this->renderForm($sessionId, $hiddenFields, $actionUrl, $submitLabel, $afterSubmitElement, $styles);
  }

  private function renderFormInWPRegisterForm(string $sessionId, string $referrer, string $submitLabelKey) {
    if (isset($this->renderedForms[$sessionId])) {
      return $this->renderedForms[$sessionId];
    }

    // The form fields come only from the server-side stash. Request data is never rendered.
    $stash = $this->captchaSession->getFormData($sessionId);
    if (!is_array($stash) || ($stash['referrer_form'] ?? null) !== $referrer) {
      return $this->renderBackLink($referrer, __('This CAPTCHA page has expired. Go back to the registration form to try again.', 'mailpoet'), null);
    }

    if (!empty($stash['rendered'])) {
      return $this->renderBackLink($referrer, __('This CAPTCHA page has already been used. Go back to the registration form to try again.', 'mailpoet'), $stash['action_url'] ?? null);
    }

    $actionUrl = $this->getRegisterFormActionUrl($referrer, $stash['referrer_form_url'] ?? null);

    // The 'name' attr is required in this format for the refresh button to work
    $hiddenFields = '<input type="hidden" name="data[captcha_session_id]" value="' . $this->wp->escAttr($sessionId) . '" />';

    $submitLabel = isset($stash[$submitLabelKey]) && is_scalar($stash[$submitLabelKey])
      ? (string)$stash[$submitLabelKey]
      : __('Register', 'mailpoet');

    $excluded = ['captcha_session_id', 'referrer_form', 'referrer_form_url', 'rendered', 'action_url'];
    foreach ($stash as $key => $value) {
      if (!is_scalar($value) || in_array($key, $excluded, true)) continue;
      $hiddenFields .= '<input type="hidden" name="' . $this->wp->escAttr($key) . '" value="' . $this->wp->escAttr($value) . '" />';
    }

    $html = $this->renderForm($sessionId, $hiddenFields, $actionUrl, $submitLabel);
    if ($html === false) {
      return false;
    }

    // The fields are shown once. Replacing the stash drops them (including any password)
    // and keeps the session alive for the image, audio and refresh requests.
    $this->captchaSession->setFormData($sessionId, [
      'referrer_form' => $referrer,
      'action_url' => $actionUrl,
      'rendered' => true,
    ]);
    $this->renderedForms[$sessionId] = $html;
    return $html;
  }

  /**
   * @param mixed $candidateUrl
   */
  private function renderBackLink(string $referrer, string $message, $candidateUrl): string {
    $actionUrl = $this->getRegisterFormActionUrl($referrer, $candidateUrl);
    return '<p><a href="' . $this->wp->escUrl($actionUrl) . '">' . $this->wp->escHtml($message) . '</a></p>';
  }

  /**
   * The URL the register form is submitted to. Only a URL with the site's own scheme, host and port is used.
   *
   * @param mixed $candidate
   */
  private function getRegisterFormActionUrl(string $referrer, $candidate): string {
    $validated = is_string($candidate) ? (string)$this->wp->wpValidateRedirect($candidate, '') : '';
    if ($validated !== '') {
      if (!$this->wp->wpParseUrl($validated, PHP_URL_HOST)) {
        if (strpos($validated, '/') === 0 && strpos($validated, '//') !== 0) {
          return $validated;
        }
      } elseif ($this->getOrigin($validated) === $this->getOrigin($this->wp->homeUrl())) {
        return $validated;
      }
    }

    if ($referrer === CaptchaUrlFactory::REFERER_WP_FORM) {
      return (string)$this->wp->wpRegistrationUrl();
    }
    return $this->wooHelper->wcGetPagePermalink('myaccount') ?: $this->wp->homeUrl();
  }

  /**
   * Scheme, host and port in lower case, with the default port filled in.
   */
  private function getOrigin(string $url): ?string {
    $parts = $this->wp->wpParseUrl($url);
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
      return null;
    }
    $scheme = strtolower((string)$parts['scheme']);
    $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
    return $scheme . '://' . strtolower((string)$parts['host']) . ':' . $port;
  }

  private function renderForm(
    $sessionId,
    $hiddenFields,
    $actionUrl,
    $submitLabel,
    $afterSubmitElement = null,
    $styles = null
  ) {
    if (!$this->captchaSession->exists($sessionId)) {
      return false;
    }
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

    $formHtml = '<form method="POST" action="' . $this->wp->escUrl($actionUrl) . '" class="' . $this->wp->escAttr($classes) . '" id="mailpoet_captcha_form" novalidate>';
    $formHtml .= $hiddenFields;

    $width = CaptchaRenderer::DEFAULT_WIDTH;
    $height = CaptchaRenderer::DEFAULT_HEIGHT;
    $captchaUrl = $this->captchaUrlFactory->getCaptchaImageUrl($sessionId);
    $audioCaptchaUrl = $this->captchaUrlFactory->getCaptchaAudioUrl($sessionId);
    $reloadIcon = Env::$assetsUrl . '/img/icons/image-rotate.svg';
    $playIcon = Env::$assetsUrl . '/img/icons/controls-volumeon.svg';

    $formHtml .= '<div class="mailpoet_form_hide_on_success">';
    $formHtml .= '<p class="mailpoet_paragraph">';
    $formHtml .= '<img class="mailpoet_captcha" src="' . $this->wp->escUrl($captchaUrl) . '" width="' . $this->wp->escAttr($width) . '" height="' . $this->wp->escAttr($height) . '" title="' . esc_attr__('CAPTCHA', 'mailpoet') . '" />';
    $formHtml .= '</p>';
    $formHtml .= '<button type="button" class="mailpoet_icon_button mailpoet_captcha_update" title="' . esc_attr(__('Reload CAPTCHA', 'mailpoet')) . '"><img src="' . $this->wp->escUrl($reloadIcon) . '" alt="" /></button>';
    $formHtml .= '<button type="button" class="mailpoet_icon_button mailpoet_captcha_audio" title="' . esc_attr(__('Play CAPTCHA', 'mailpoet')) . '"><img src="' . $this->wp->escUrl($playIcon) . '" alt="" /></button>';
    $formHtml .= '<audio class="mailpoet_captcha_player" preload="none">';
    $formHtml .= '<source src="' . $this->wp->escUrl($audioCaptchaUrl) . '" type="audio/wav">';
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

    $success = isset($settings['success_message']) ? (string)$settings['success_message'] : '';

    $formHtml = '<div class="mailpoet_message" role="alert" aria-live="assertive">';
    $formHtml .= '<p class="mailpoet_validate_success" ' . ($showSuccessMessage ? '' : ' style="display:none;"') . '>' . $this->wp->escHtml($success) . '</p>';
    $formHtml .= '<p class="mailpoet_validate_error" ' . ($showErrorMessage ? '' : ' style="display:none;"') . '>' . $this->wp->escHtml($errorMessage) . '</p>';
    $formHtml .= '</div>';

    return $formHtml;
  }
}
