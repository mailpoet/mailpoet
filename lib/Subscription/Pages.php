<?php
namespace MailPoet\Subscription;

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\SubscriberSegment;
use \MailPoet\Models\CustomField;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Segment;
use \MailPoet\Util\Helpers;
use \MailPoet\Util\Url;
use \MailPoet\Subscription;
use \MailPoet\Form\Renderer as FormRenderer;
use \MailPoet\Form\Block\Date as FormBlockDate;

class Pages {
  const DEMO_EMAIL = 'demo@mailpoet.com';

  private $action;
  private $data;
  private $subscriber;

  function __construct($action, $data) {
    $this->action = $action;
    $this->data = $data;
    $this->subscriber = $this->getSubscriber();

    // handle subscription pages title & content
    add_filter('wp_title', array($this,'setWindowTitle'), 10, 3);
    add_filter('document_title_parts', array($this,'setWindowTitleParts'), 10, 1);
    add_filter('the_title', array($this,'setPageTitle'), 10, 1);
    add_filter('the_content', array($this,'setPageContent'), 10, 1);

    // manage subscription link shortcode
    // [mailpoet_manage text="Manage your subscription"]
    add_shortcode('mailpoet_manage', array($this, 'getManageLink'));
    add_shortcode('mailpoet_manage_subscription', array($this, 'getManageContent'));
  }

  private function isPreview() {
    return (
      array_key_exists('preview', $_GET)
      || array_key_exists('preview', $this->data)
    );
  }

  function getSubscriber() {
    $token = (isset($this->data['token'])) ? $this->data['token'] : null;
    $email = (isset($this->data['email'])) ? $this->data['email'] : null;

    if(Subscriber::generateToken($email) === $token) {
      $subscriber = Subscriber::findOne($email);
      if($subscriber !== false) {
        return $subscriber;
      }
    }
    return false;
  }

  function confirm() {
    if($this->subscriber !== false) {
      if($this->subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
        $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
        $this->subscriber->save();
      }
    }
  }

  function unsubscribe() {
    if($this->subscriber !== false) {
      if($this->subscriber->status !== Subscriber::STATUS_UNSUBSCRIBED) {
        $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
        $this->subscriber->save();
        SubscriberSegment::unsubscribeFromSegments($this->subscriber);
      }
    }
  }
  function setPageTitle($page_title = '') {
    global $post;

    if($post->post_title !== __('MailPoet Page')) return $page_title;
    if(
      ($this->isMailPoetPage($post->ID) === false)
      ||
      ($page_title !== single_post_title('', false))
    ) {
      return $page_title;
    } else {
      switch($this->action) {
        case 'confirm':
          return $this->getConfirmTitle();

        case 'manage':
          return $this->getManageTitle();

        case 'unsubscribe':
          return $this->getUnsubscribeTitle();
      }
    }
    return $page_title;
  }

  function setPageContent($page_content = '[mailpoet_page]') {
    global $post;

    if(
      ($this->isPreview() === false)
      &&
      ($this->isMailPoetPage($post->ID) === false)
    ) {
      return $page_content;
    }

    $content = '';

    switch($this->action) {
      case 'confirm':
        $content = $this->getConfirmContent();
        break;
      case 'manage':
        $content = $this->getManageContent();
        break;
      case 'unsubscribe':
        $content = $this->getUnsubscribeContent();
        break;
    }

    if(strpos($page_content, '[mailpoet_page]') !== false) {
      return str_replace('[mailpoet_page]', $content, $page_content);
    } else {
      return $page_content;
    }
  }

  function setWindowTitle($title, $separator, $separator_location = 'right') {
    $title_parts = explode(" $separator ", $title);
    if($separator_location === 'right') {
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

  function isMailPoetPage($page_id = null) {
    $mailpoet_page_ids = array_unique(array_values(
      Setting::getValue('subscription.pages', array())
    ));

    return (in_array($page_id, $mailpoet_page_ids));
  }

  private function getConfirmTitle() {
    if($this->isPreview()) {
      $title = sprintf(
        __("You've subscribed to: %s"),
        'demo 1, demo 2'
      );
    } else if($this->subscriber === false) {
      $title = __('Your confirmation link expired, please subscribe again.');
    } else {
      $segment_names = array_map(function($segment) {
        return $segment->name;
      }, $this->subscriber->segments()->findMany());

      if(empty($segment_names)) {
        $title = __("You're now subscribed!");
      } else {
        $title = sprintf(
          __("You've subscribed to: %s"),
          join(', ', $segment_names)
        );
      }
    }
    return $title;
  }

  private function getManageTitle() {
    if($this->isPreview() || $this->subscriber !== false) {
      return __("Manage your subscription");
    }
  }

  private function getUnsubscribeTitle() {
    if($this->isPreview() || $this->subscriber !== false) {
      return __("You are now unsubscribed");
    }
  }


  private function getConfirmContent() {
    if($this->isPreview() || $this->subscriber !== false) {
      return __("Yup, we've added you to our email list. You'll hear from us shortly.");
    }
  }

  public function getManageContent() {
    if($this->isPreview()) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate(array(
        'email' => self::DEMO_EMAIL,
        'first_name' => 'John',
        'last_name' => 'Doe'
      ));
    } else if($this->subscriber !== false) {
      $subscriber = $this->subscriber
      ->withCustomFields()
      ->withSubscriptions();
    } else {
      return;
    }

    $custom_fields = array_map(function($custom_field) use($subscriber) {
      $custom_field->id = 'cf_'.$custom_field->id;
      $custom_field = $custom_field->asArray();
      $custom_field['params']['value'] = $subscriber->{$custom_field['id']};

      if($custom_field['type'] === 'date') {
        $date_formats = FormBlockDate::getDateFormats();
        $custom_field['params']['date_format'] = array_shift(
          $date_formats[$custom_field['params']['date_type']]
        );
      }

      return $custom_field;
    }, CustomField::findMany());

    $segment_ids = Setting::getValue('subscription.segments', array());
    if(!empty($segment_ids)) {
      $segments = Segment::getPublic()
        ->whereIn('id', $segment_ids)
        ->findMany();
    } else {
      $segments = Segment::getPublic()
        ->findMany();
    }
    $subscribed_segment_ids = array();
    if(!empty($this->subscriber->subscriptions)) {
      foreach($this->subscriber->subscriptions as $subscription) {
        if($subscription['status'] === Subscriber::STATUS_SUBSCRIBED) {
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
          'label' => __('First name'),
          'value' => $subscriber->first_name,
          'disabled' => ($subscriber->isWPUser())
        )
      ),
      array(
        'id' => 'last_name',
        'type' => 'text',
        'params' => array(
          'label' => __('Last name'),
          'value' => $subscriber->last_name,
          'disabled' => ($subscriber->isWPUser())
        )
      ),
      array(
        'id' => 'status',
        'type' => 'select',
        'params' => array(
          'required' => true,
          'label' => __('Status'),
          'values' => array(
            array(
              'value' => array(
                Subscriber::STATUS_SUBSCRIBED => __('Subscribed')
              ),
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_SUBSCRIBED
              )
            ),
            array(
              'value' => array(
                Subscriber::STATUS_UNSUBSCRIBED => __('Unsubscribed')
              ),
              'is_checked' => (
                $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED
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
            'label' => __('Your lists'),
            'values' => $segments
          )
        ),
        array(
          'id' => 'submit',
          'type' => 'submit',
          'params' => array(
            'label' => __('Save')
          )
        )
      )
    );

    $form_html = '<form method="POST" '.
      'action="'.admin_url('admin-post.php').'" '.
      'novalidate>';
    $form_html .= '<input type="hidden" name="action"'.
      ' value="mailpoet_subscription_update" />';
    $form_html .= '<input type="hidden" name="segments" value="" />';
    $form_html .= '<input type="hidden" name="mailpoet_redirect" '.
      'value="'.Url::getCurrentUrl().'" />';
    $form_html .= '<input type="hidden" name="email" value="'.$subscriber->email.'" />';

    $form_html .= '<p class="mailpoet_paragraph">';
    $form_html .= '<label>Email *<br /><strong>'.$subscriber->email.'</strong></label>';
    $form_html .= '<br /><span style="font-size:85%;">';
    // special case for WP users as they cannot edit their subscriber's email
    if($subscriber->isWPUser()) {
      // check if subscriber's associated WP user is the currently logged in WP user
      $wp_current_user = wp_get_current_user();
      if($wp_current_user->user_email === $subscriber->email) {
        $form_html .= str_replace(
          array('[link]', '[/link]'),
          array('<a href="'.get_edit_profile_url().'" target="_blank">', '</a>'),
          __('[link]Edit your profile[/link] to update your email.')
        );
      } else {
        $form_html .= str_replace(
          array('[link]', '[/link]'),
          array('<a href="'.wp_login_url().'" target="_blank">', '</a>'),
          __('[link]Log in to your account[/link] to update your email.')
        );
      }
    } else {
      $form_html .= __('Need to change your email address? Unsubscribe here, then simply sign up again.');
    }
    $form_html .= '</span>';
    $form_html .= '</p>';

    // subscription form
    $form_html .= FormRenderer::renderBlocks($form);
    $form_html .= '</form>';
    return $form_html;
  }

  private function getUnsubscribeContent() {
    $content = '';
    if($this->isPreview() || $this->subscriber !== false) {
      $content .= '<p>'.__('Accidentally unsubscribed?').' <strong>';
      $content .= '[mailpoet_manage]';
      $content .= '</strong></p>';
    }
    return $content;
  }

  function getManageLink($params) {
    // get label or display default label
    $text = (
      isset($params['text'])
      ? $params['text']
      : __('Manage your subscription')
    );

    return '<a href="'.Subscription\Url::getManageUrl(
      $this->subscriber
    ).'">'.$text.'</a>';
  }
}
