<?php

namespace MailPoet\Subscription;

use MailPoet\Form\AssetsController;
use MailPoet\Form\Block\Date as FormBlockDate;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Util\Helpers;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WP\Functions as WPFunctions;

class Pages {
  const DEMO_EMAIL = 'demo@mailpoet.com';
  const ACTION_CAPTCHA = 'captcha';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';

  private $action;
  private $data;
  private $subscriber;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationSender;

  /** @var SettingsController */
  private $settings;

  /** @var UrlHelper */
  private $urlHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var CaptchaRenderer */
  private $captchaRenderer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var AssetsController */
  private $assetsController;

  /** @var FormRenderer */
  private $formRenderer;

  /** @var FormBlockDate */
  private $dateBlock;

  public function __construct(
    NewSubscriberNotificationMailer $newSubscriberNotificationSender,
    WPFunctions $wp,
    SettingsController $settings,
    UrlHelper $urlHelper,
    CaptchaRenderer $captchaRenderer,
    WelcomeScheduler $welcomeScheduler,
    LinkTokens $linkTokens,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    AssetsController $assetsController,
    FormRenderer $formRenderer,
    FormBlockDate $dateBlock
  ) {
    $this->wp = $wp;
    $this->newSubscriberNotificationSender = $newSubscriberNotificationSender;
    $this->settings = $settings;
    $this->urlHelper = $urlHelper;
    $this->captchaRenderer = $captchaRenderer;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->linkTokens = $linkTokens;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->assetsController = $assetsController;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
  }

  public function init($action = false, $data = [], $initShortcodes = false, $initPageFilters = false) {
    $this->action = $action;
    $this->data = $data;
    $this->subscriber = $this->getSubscriber();
    if ($initPageFilters) $this->initPageFilters();
    if ($initShortcodes) $this->initShortcodes();
    return $this;
  }

  private function isPreview() {
    return (array_key_exists('preview', $_GET) || array_key_exists('preview', $this->data));
  }

  public function initPageFilters() {
    $this->wp->addFilter('wp_title', [$this,'setWindowTitle'], 10, 3);
    $this->wp->addFilter('document_title_parts', [$this,'setWindowTitleParts'], 10, 1);
    $this->wp->addFilter('the_title', [$this,'setPageTitle'], 10, 1);
    $this->wp->addFilter('the_content', [$this,'setPageContent'], 10, 1);
  }

  public function initShortcodes() {
    $this->wp->addShortcode('mailpoet_manage', [$this, 'getManageLink']);
    $this->wp->addShortcode('mailpoet_manage_subscription', [$this, 'getManageContent']);
  }

  private function getSubscriber() {
    if (!is_null($this->subscriber)) {
      return $this->subscriber;
    }

    $token = (isset($this->data['token'])) ? $this->data['token'] : null;
    $email = (isset($this->data['email'])) ? $this->data['email'] : null;
    $wpUser = $this->wp->wpGetCurrentUser();

    if (!$email && $wpUser->exists()) {
      return Subscriber::where('wp_user_id', $wpUser->ID)->findOne();
    }

    if (!$email) {
      return false;
    }

    $subscriber = Subscriber::where('email', $email)->findOne();
    return ($subscriber && $this->linkTokens->verifyToken($subscriber, $token)) ? $subscriber : false;
  }

  public function confirm() {
    $this->subscriber = $this->getSubscriber();
    if ($this->subscriber === false || $this->subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
      return false;
    }

    $subscriberData = $this->subscriber->getUnconfirmedData();

    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->confirmedIp = Helpers::getIP();
    $this->subscriber->setExpr('confirmed_at', 'NOW()');
    $this->subscriber->setExpr('last_subscribed_at', 'NOW()');
    $this->subscriber->unconfirmedData = null;
    $this->subscriber->save();

    if ($this->subscriber->getErrors() === false) {
      // send welcome notification
      $subscriberSegments = $this->subscriber->segments()->findMany();
      if ($subscriberSegments) {
        $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
          $this->subscriber->id,
          array_map(function ($segment) {
            return $segment->get('id');
          }, $subscriberSegments)
        );
      }

      $this->newSubscriberNotificationSender->send($this->subscriber, $subscriberSegments);

      // update subscriber from stored data after confirmation
      if (!empty($subscriberData)) {
        Subscriber::createOrUpdate($subscriberData);
      }
    }
  }

  public function unsubscribe() {
    if (!$this->isPreview()
      && ($this->subscriber !== false)
      && ($this->subscriber->status !== Subscriber::STATUS_UNSUBSCRIBED)
    ) {
      $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
      $this->subscriber->save();
      SubscriberSegment::unsubscribeFromSegments($this->subscriber);
    }
  }

  public function setPageTitle($pageTitle = '') {
    global $post;

    if ($this->action !== self::ACTION_CAPTCHA && $this->isPreview() === false && $this->subscriber === false) {
      return $this->wp->__("Hmmm... we don't have a record of you.", 'mailpoet');
    }

    if (
      ($post->post_title !== $this->wp->__('MailPoet Page', 'mailpoet')) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ||
      ($pageTitle !== $this->wp->singlePostTitle('', false))
    ) {
      // when it's a custom page, just return the original page title
      return $pageTitle;
    } else {
      // when it's our own page, generate page title based on requested action
      switch ($this->action) {
        case self::ACTION_CAPTCHA:
          return $this->captchaRenderer->getCaptchaPageTitle();

        case self::ACTION_CONFIRM:
          return $this->getConfirmTitle();

        case self::ACTION_MANAGE:
          return $this->getManageTitle();

        case self::ACTION_UNSUBSCRIBE:
          return $this->getUnsubscribeTitle();
      }
    }
  }

  public function setPageContent($pageContent = '[mailpoet_page]') {
    // if we're not in preview mode or captcha page and the subscriber does not exist
    if ($this->action !== self::ACTION_CAPTCHA && $this->isPreview() === false && $this->subscriber === false) {
      return $this->wp->__("Your email address doesn't appear in our lists anymore. Sign up again or contact us if this appears to be a mistake.", 'mailpoet');
    }

    $this->assetsController->setupFrontEndDependencies();

    if (strpos($pageContent, '[mailpoet_page]') !== false) {
      $content = '';

      switch ($this->action) {
        case self::ACTION_CAPTCHA:

          $captchaSessionId = isset($this->data['captcha_session_id']) ? $this->data['captcha_session_id'] : null;
          $content = $this->captchaRenderer->getCaptchaPageContent($captchaSessionId);
          break;
        case self::ACTION_CONFIRM:
          $content = $this->getConfirmContent();
          break;
        case self::ACTION_MANAGE:
          $content = $this->getManageContent();
          break;
        case self::ACTION_UNSUBSCRIBE:
          $content = $this->getUnsubscribeContent();
          break;
      }
      return str_replace('[mailpoet_page]', trim($content), $pageContent);
    } else {
      return $pageContent;
    }
  }

  public function setWindowTitle($title, $separator, $separatorLocation = 'right') {
    $titleParts = explode(" $separator ", $title);
    if ($separatorLocation === 'right') {
      // first part
      $titleParts[0] = $this->setPageTitle($titleParts[0]);
    } else {
      // last part
      $lastIndex = count($titleParts) - 1;
      $titleParts[$lastIndex] = $this->setPageTitle($titleParts[$lastIndex]);
    }
    return implode(" $separator ", $titleParts);
  }

  public function setWindowTitleParts($meta = []) {
    $meta['title'] = $this->setPageTitle($meta['title']);
    return $meta;
  }

  private function getConfirmTitle() {
    if ($this->isPreview()) {
      $title = sprintf(
        $this->wp->__("You have subscribed to: %s", 'mailpoet'),
        'demo 1, demo 2'
      );
    } else {
      $segmentNames = array_map(function($segment) {
        return $segment->name;
      }, $this->subscriber->segments()->findMany());

      if (empty($segmentNames)) {
        $title = $this->wp->__("You are now subscribed!", 'mailpoet');
      } else {
        $title = sprintf(
          $this->wp->__("You have subscribed to: %s", 'mailpoet'),
          join(', ', $segmentNames)
        );
      }
    }
    return $title;
  }

  private function getManageTitle() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return $this->wp->__("Manage your subscription", 'mailpoet');
    }
  }

  private function getUnsubscribeTitle() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return $this->wp->__("You are now unsubscribed.", 'mailpoet');
    }
  }

  private function getConfirmContent() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return $this->wp->__("Yup, we've added you to our email list. You'll hear from us shortly.", 'mailpoet');
    }
  }

  public function getManageContent() {
    if ($this->isPreview()) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate([
        'email' => self::DEMO_EMAIL,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'link_token' => 'bfd0889dbc7f081e171fa0cee7401df2',
      ]);
    } else if ($this->subscriber !== false) {
      $subscriber = $this->subscriber
      ->withCustomFields()
      ->withSubscriptions();
    } else {
      return $this->wp->__('Subscription management form is only available to mailing lists subscribers.', 'mailpoet');
    }

    $customFields = array_map(function($customField) use($subscriber) {
      $customField->id = 'cf_' . $customField->id;
      $customField = $customField->asArray();
      $customField['params']['value'] = $subscriber->{$customField['id']};

      if ($customField['type'] === 'date') {
        $dateFormats = $this->dateBlock->getDateFormats();
        $customField['params']['date_format'] = array_shift(
          $dateFormats[$customField['params']['date_type']]
        );
      }

      return $customField;
    }, CustomField::findMany());

    $segmentIds = $this->settings->get('subscription.segments', []);
    if (!empty($segmentIds)) {
      $segments = Segment::getPublic()
        ->whereIn('id', $segmentIds)
        ->findMany();
    } else {
      $segments = Segment::getPublic()
        ->findMany();
    }
    $subscribedSegmentIds = [];
    if (!empty($this->subscriber->subscriptions)) {
      foreach ($this->subscriber->subscriptions as $subscription) {
        if ($subscription['status'] === Subscriber::STATUS_SUBSCRIBED) {
          $subscribedSegmentIds[] = $subscription['segment_id'];
        }
      }
    }

    $segments = array_map(function($segment) use($subscribedSegmentIds) {
      return [
        'id' => $segment->id,
        'name' => $segment->name,
        'is_checked' => in_array($segment->id, $subscribedSegmentIds),
      ];
    }, $segments);


    $fields = [
      [
        'id' => 'first_name',
        'type' => 'text',
        'params' => [
          'label' => $this->wp->__('First name', 'mailpoet'),
          'value' => $subscriber->firstName,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()),
        ],
      ],
      [
        'id' => 'last_name',
        'type' => 'text',
        'params' => [
          'label' => $this->wp->__('Last name', 'mailpoet'),
          'value' => $subscriber->lastName,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()),
        ],
      ],
      [
        'id' => 'status',
        'type' => 'select',
        'params' => [
          'required' => true,
          'label' => $this->wp->__('Status', 'mailpoet'),
          'values' => [
            [
              'value' => [
                Subscriber::STATUS_SUBSCRIBED => $this->wp->__('Subscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_SUBSCRIBED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_UNSUBSCRIBED => $this->wp->__('Unsubscribed', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_BOUNCED => $this->wp->__('Bounced', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_BOUNCED
              ),
              'is_disabled' => true,
              'is_hidden' => (
                $subscriber->status !== Subscriber::STATUS_BOUNCED
              ),
            ],
            [
              'value' => [
                Subscriber::STATUS_INACTIVE => $this->wp->__('Inactive', 'mailpoet'),
              ],
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_INACTIVE
              ),
              'is_hidden' => (
                $subscriber->status !== Subscriber::STATUS_INACTIVE
              ),
            ],
          ],
        ],
      ],
    ];

    $form = array_merge(
      $fields,
      $customFields,
      [
        [
          'id' => 'segments',
          'type' => 'segment',
          'params' => [
            'label' => $this->wp->__('Your lists', 'mailpoet'),
            'values' => $segments,
          ],
        ],
        [
          'id' => 'submit',
          'type' => 'submit',
          'params' => [
            'label' => $this->wp->__('Save', 'mailpoet'),
          ],
        ],
      ]
    );

    $formHtml = '<form method="POST" ' .
      'action="' . admin_url('admin-post.php') . '" ' .
      'novalidate>';
    $formHtml .= '<input type="hidden" name="action"' .
      ' value="mailpoet_subscription_update" />';
    $formHtml .= '<input type="hidden" name="data[segments]" value="" />';
    $formHtml .= '<input type="hidden" name="mailpoet_redirect" ' .
      'value="' . htmlspecialchars($this->urlHelper->getCurrentUrl(), ENT_QUOTES) . '" />';
    $formHtml .= '<input type="hidden" name="data[email]" value="' .
      $subscriber->email .
    '" />';
    $formHtml .= '<input type="hidden" name="token" value="' .
      $this->linkTokens->getToken($subscriber) .
    '" />';

    $formHtml .= '<p class="mailpoet_paragraph">';
    $formHtml .= '<label>' . __('Email', 'mailpoet') . ' *<br /><strong>' . htmlspecialchars($subscriber->email) . '</strong></label>';
    $formHtml .= '<br /><span style="font-size:85%;">';
    // special case for WP users as they cannot edit their subscriber's email
    if ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()) {
      // check if subscriber's associated WP user is the currently logged in WP user
      $wpCurrentUser = $this->wp->wpGetCurrentUser();
      if ($wpCurrentUser->user_email === $subscriber->email) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $formHtml .= Helpers::replaceLinkTags(
          $this->wp->__('[link]Edit your profile[/link] to update your email.', 'mailpoet'),
          $this->wp->getEditProfileUrl(),
          ['target' => '_blank']
        );
      } else {
        $formHtml .= Helpers::replaceLinkTags(
          $this->wp->__('[link]Log in to your account[/link] to update your email.', 'mailpoet'),
          $this->wp->wpLoginUrl(),
          ['target' => '_blank']
        );
      }
    } else {
      $formHtml .= $this->wp->__('Need to change your email address? Unsubscribe here, then simply sign up again.', 'mailpoet');
    }
    $formHtml .= '</span>';
    $formHtml .= '</p>';

    // subscription form
    $formHtml .= $this->formRenderer->renderBlocks($form, [], $honeypot = false);
    $formHtml .= '</form>';
    return $formHtml;
  }

  private function getUnsubscribeContent() {
    $content = '';
    if ($this->isPreview() || $this->subscriber !== false) {
      $content .= '<p>' . __('Accidentally unsubscribed?', 'mailpoet') . ' <strong>';
      $content .= '[mailpoet_manage]';
      $content .= '</strong></p>';
    }
    return $content;
  }

  public function getManageLink($params) {
    if (!$this->subscriber) return $this->wp->__('Link to subscription management page is only available to mailing lists subscribers.', 'mailpoet');

    // get label or display default label
    $text = (
      isset($params['text'])
      ? htmlspecialchars($params['text'])
      : $this->wp->__('Manage your subscription', 'mailpoet')
    );

    return '<a href="' . $this->subscriptionUrlFactory->getManageUrl(
      $this->subscriber ?: null
    ) . '">' . $text . '</a>';
  }
}
