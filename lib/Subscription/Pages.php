<?php

namespace MailPoet\Subscription;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Util\Helpers;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Block\Date as FormBlockDate;
use MailPoet\WP\Functions as WPFunctions;

class Pages {
  const DEMO_EMAIL = 'demo@mailpoet.com';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';

  private $action;
  private $data;
  private $subscriber;
  /** @var NewSubscriberNotificationMailer */
  private $new_subscriber_notification_sender;
  /** @var SettingsController */
  private $settings;

  function __construct($action = false, $data = array(), $init_shortcodes = false, $init_page_filters = false, $new_subscriber_notification_sender = null) {
    $this->action = $action;
    $this->data = $data;
    $this->subscriber = $this->getSubscriber();
    if ($init_page_filters) $this->initPageFilters();
    if ($init_shortcodes) $this->initShortcodes();
    if ($new_subscriber_notification_sender) {
      $this->new_subscriber_notification_sender = $new_subscriber_notification_sender;
    } else {
      $this->new_subscriber_notification_sender = new NewSubscriberNotificationMailer();
    }
    $this->settings = new SettingsController();
  }

  private function isPreview() {
    return (array_key_exists('preview', $_GET) || array_key_exists('preview', $this->data));
  }

  function initPageFilters() {
    WPFunctions::get()->addFilter('wp_title', array($this,'setWindowTitle'), 10, 3);
    WPFunctions::get()->addFilter('document_title_parts', array($this,'setWindowTitleParts'), 10, 1);
    WPFunctions::get()->addFilter('the_title', array($this,'setPageTitle'), 10, 1);
    WPFunctions::get()->addFilter('the_content', array($this,'setPageContent'), 10, 1);
  }

  function initShortcodes() {
    WPFunctions::get()->addShortcode('mailpoet_manage', array($this, 'getManageLink'));
    WPFunctions::get()->addShortcode('mailpoet_manage_subscription', array($this, 'getManageContent'));
  }

  function getSubscriber() {
    $token = (isset($this->data['token'])) ? $this->data['token'] : null;
    $email = (isset($this->data['email'])) ? $this->data['email'] : null;
    $wp_user = WPFunctions::get()->wpGetCurrentUser();

    if (!$email && $wp_user->exists()) {
      return Subscriber::where('wp_user_id', $wp_user->ID)->findOne();
    }

    return (Subscriber::generateToken($email) === $token) ?
      Subscriber::findOne($email) :
      false;
  }

  function confirm() {
    if ($this->subscriber === false || $this->subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
      return false;
    }

    $subscriber_data = $this->subscriber->getUnconfirmedData();

    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->confirmed_ip = Helpers::getIP();
    $this->subscriber->setExpr('confirmed_at', 'NOW()');
    $this->subscriber->unconfirmed_data = null;
    $this->subscriber->save();

    if ($this->subscriber->getErrors() === false) {
      // send welcome notification
      $subscriber_segments = $this->subscriber->segments()->findMany();
      if ($subscriber_segments) {
        Scheduler::scheduleSubscriberWelcomeNotification(
          $this->subscriber->id,
          array_map(function ($segment) {
            return $segment->get('id');
          }, $subscriber_segments)
        );
      }

      $this->new_subscriber_notification_sender->send($this->subscriber, $subscriber_segments);

      // update subscriber from stored data after confirmation
      if (!empty($subscriber_data)) {
        Subscriber::createOrUpdate($subscriber_data);
      }
    }
  }

  function unsubscribe() {
    if (!$this->isPreview()
      && ($this->subscriber !== false)
      && ($this->subscriber->status !== Subscriber::STATUS_UNSUBSCRIBED)
    ) {
      $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
      $this->subscriber->save();
      SubscriberSegment::unsubscribeFromSegments($this->subscriber);
    }
  }

  function setPageTitle($page_title = '') {
    global $post;

    if ($this->isPreview() === false && $this->subscriber === false) {
      return WPFunctions::get()->__("Hmmm... we don't have a record of you.", 'mailpoet');
    }

    if (
      ($post->post_title !== WPFunctions::get()->__('MailPoet Page', 'mailpoet'))
      ||
      ($page_title !== WPFunctions::get()->singlePostTitle('', false))
    ) {
      // when it's a custom page, just return the original page title
      return $page_title;
    } else {
      // when it's our own page, generate page title based on requested action
      switch ($this->action) {
        case self::ACTION_CONFIRM:
          return $this->getConfirmTitle();

        case self::ACTION_MANAGE:
          return $this->getManageTitle();

        case self::ACTION_UNSUBSCRIBE:
          return $this->getUnsubscribeTitle();
      }
    }
  }

  function setPageContent($page_content = '[mailpoet_page]') {
    global $post;

    // if we're not in preview mode and the subscriber does not exist
    if ($this->isPreview() === false && $this->subscriber === false) {
      return WPFunctions::get()->__("Your email address doesn't appear in our lists anymore. Sign up again or contact us if this appears to be a mistake.", 'mailpoet');
    }

    if (strpos($page_content, '[mailpoet_page]') !== false) {
      $content = '';

      switch ($this->action) {
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
      return str_replace('[mailpoet_page]', trim($content), $page_content);
    } else {
      return $page_content;
    }
  }

  function setWindowTitle($title, $separator, $separator_location = 'right') {
    $title_parts = explode(" $separator ", $title);
    if ($separator_location === 'right') {
      // first part
      $title_parts[0] = $this->setPageTitle($title_parts[0]);
    } else {
      // last part
      $last_index = count($title_parts) - 1;
      $title_parts[$last_index] = $this->setPageTitle($title_parts[$last_index]);
    }
    return implode(" $separator ", $title_parts);
  }

  function setWindowTitleParts($meta = array()) {
    $meta['title'] = $this->setPageTitle($meta['title']);
    return $meta;
  }

  private function getConfirmTitle() {
    if ($this->isPreview()) {
      $title = sprintf(
        WPFunctions::get()->__("You have subscribed to: %s", 'mailpoet'),
        'demo 1, demo 2'
      );
    } else {
      $segment_names = array_map(function($segment) {
        return $segment->name;
      }, $this->subscriber->segments()->findMany());

      if (empty($segment_names)) {
        $title = WPFunctions::get()->__("You are now subscribed!", 'mailpoet');
      } else {
        $title = sprintf(
          WPFunctions::get()->__("You have subscribed to: %s", 'mailpoet'),
          join(', ', $segment_names)
        );
      }
    }
    return $title;
  }

  private function getManageTitle() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return WPFunctions::get()->__("Manage your subscription", 'mailpoet');
    }
  }

  private function getUnsubscribeTitle() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return WPFunctions::get()->__("You are now unsubscribed.", 'mailpoet');
    }
  }

  private function getConfirmContent() {
    if ($this->isPreview() || $this->subscriber !== false) {
      return WPFunctions::get()->__("Yup, we've added you to our email list. You'll hear from us shortly.", 'mailpoet');
    }
  }

  public function getManageContent() {
    if ($this->isPreview()) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate(array(
        'email' => self::DEMO_EMAIL,
        'first_name' => 'John',
        'last_name' => 'Doe'
      ));
    } else if ($this->subscriber !== false) {
      $subscriber = $this->subscriber
      ->withCustomFields()
      ->withSubscriptions();
    } else {
      return WPFunctions::get()->__('Subscription management form is only available to mailing lists subscribers.', 'mailpoet');
    }

    $custom_fields = array_map(function($custom_field) use($subscriber) {
      $custom_field->id = 'cf_'.$custom_field->id;
      $custom_field = $custom_field->asArray();
      $custom_field['params']['value'] = $subscriber->{$custom_field['id']};

      if ($custom_field['type'] === 'date') {
        $date_formats = FormBlockDate::getDateFormats();
        $custom_field['params']['date_format'] = array_shift(
          $date_formats[$custom_field['params']['date_type']]
        );
      }

      return $custom_field;
    }, CustomField::findMany());

    $segment_ids = $this->settings->get('subscription.segments', []);
    if (!empty($segment_ids)) {
      $segments = Segment::getPublic()
        ->whereIn('id', $segment_ids)
        ->findMany();
    } else {
      $segments = Segment::getPublic()
        ->findMany();
    }
    $subscribed_segment_ids = array();
    if (!empty($this->subscriber->subscriptions)) {
      foreach ($this->subscriber->subscriptions as $subscription) {
        if ($subscription['status'] === Subscriber::STATUS_SUBSCRIBED) {
          $subscribed_segment_ids[] = $subscription['segment_id'];
        }
      }
    }

    $segments = array_map(function($segment) use($subscribed_segment_ids) {
      return array(
        'id' => $segment->id,
        'name' => $segment->name,
        'is_checked' => in_array($segment->id, $subscribed_segment_ids)
      );
    }, $segments);


    $fields = array(
      array(
        'id' => 'first_name',
        'type' => 'text',
        'params' => array(
          'label' => WPFunctions::get()->__('First name', 'mailpoet'),
          'value' => $subscriber->first_name,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser())
        )
      ),
      array(
        'id' => 'last_name',
        'type' => 'text',
        'params' => array(
          'label' => WPFunctions::get()->__('Last name', 'mailpoet'),
          'value' => $subscriber->last_name,
          'disabled' => ($subscriber->isWPUser() || $subscriber->isWooCommerceUser())
        )
      ),
      array(
        'id' => 'status',
        'type' => 'select',
        'params' => array(
          'required' => true,
          'label' => WPFunctions::get()->__('Status', 'mailpoet'),
          'values' => array(
            array(
              'value' => array(
                Subscriber::STATUS_SUBSCRIBED => WPFunctions::get()->__('Subscribed', 'mailpoet')
              ),
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_SUBSCRIBED
              )
            ),
            array(
              'value' => array(
                Subscriber::STATUS_UNSUBSCRIBED => WPFunctions::get()->__('Unsubscribed', 'mailpoet')
              ),
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED
              )
            ),
            array(
              'value' => array(
                Subscriber::STATUS_BOUNCED => WPFunctions::get()->__('Bounced', 'mailpoet')
              ),
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_BOUNCED
              ),
              'is_disabled' => true,
              'is_hidden' => (
                $subscriber->status !== Subscriber::STATUS_BOUNCED
              )
            )
          )
        )
      )
    );

    $form = array_merge(
      $fields,
      $custom_fields,
      array(
        array(
          'id' => 'segments',
          'type' => 'segment',
          'params' => array(
            'label' => WPFunctions::get()->__('Your lists', 'mailpoet'),
            'values' => $segments
          )
        ),
        array(
          'id' => 'submit',
          'type' => 'submit',
          'params' => array(
            'label' => WPFunctions::get()->__('Save', 'mailpoet')
          )
        )
      )
    );

    $form_html = '<form method="POST" '.
      'action="'.admin_url('admin-post.php').'" '.
      'novalidate>';
    $form_html .= '<input type="hidden" name="action"'.
      ' value="mailpoet_subscription_update" />';
    $form_html .= '<input type="hidden" name="data[segments]" value="" />';
    $form_html .= '<input type="hidden" name="mailpoet_redirect" '.
      'value="' . htmlspecialchars(UrlHelper::getCurrentUrl(), ENT_QUOTES) . '" />';
    $form_html .= '<input type="hidden" name="data[email]" value="'.
      $subscriber->email.
    '" />';
    $form_html .= '<input type="hidden" name="token" value="'.
      Subscriber::generateToken($subscriber->email).
    '" />';

    $form_html .= '<p class="mailpoet_paragraph">';
    $form_html .= '<label>'.__('Email', 'mailpoet').' *<br /><strong>' . htmlspecialchars($subscriber->email) . '</strong></label>';
    $form_html .= '<br /><span style="font-size:85%;">';
    // special case for WP users as they cannot edit their subscriber's email
    if ($subscriber->isWPUser() || $subscriber->isWooCommerceUser()) {
      // check if subscriber's associated WP user is the currently logged in WP user
      $wp_current_user = WPFunctions::get()->wpGetCurrentUser();
      if ($wp_current_user->user_email === $subscriber->email) {
        $form_html .= Helpers::replaceLinkTags(
          WPFunctions::get()->__('[link]Edit your profile[/link] to update your email.', 'mailpoet'),
          WPFunctions::get()->getEditProfileUrl(),
          array('target' => '_blank')
        );
      } else {
        $form_html .= Helpers::replaceLinkTags(
          WPFunctions::get()->__('[link]Log in to your account[/link] to update your email.', 'mailpoet'),
          WPFunctions::get()->wpLoginUrl(),
          array('target' => '_blank')
        );
      }
    } else {
      $form_html .= WPFunctions::get()->__('Need to change your email address? Unsubscribe here, then simply sign up again.', 'mailpoet');
    }
    $form_html .= '</span>';
    $form_html .= '</p>';

    // subscription form
    $form_html .= FormRenderer::renderBlocks($form, $honeypot = false);
    $form_html .= '</form>';
    return $form_html;
  }

  private function getUnsubscribeContent() {
    $content = '';
    if ($this->isPreview() || $this->subscriber !== false) {
      $content .= '<p>'.__('Accidentally unsubscribed?', 'mailpoet').' <strong>';
      $content .= '[mailpoet_manage]';
      $content .= '</strong></p>';
    }
    return $content;
  }

  function getManageLink($params) {
    if (!$this->subscriber) return WPFunctions::get()->__('Link to subscription management page is only available to mailing lists subscribers.', 'mailpoet');

    // get label or display default label
    $text = (
      isset($params['text'])
      ? htmlspecialchars($params['text'])
      : WPFunctions::get()->__('Manage your subscription', 'mailpoet')
    );

    return '<a href="'.Url::getManageUrl(
      $this->subscriber ?: null
    ).'">'.$text.'</a>';
  }
}
