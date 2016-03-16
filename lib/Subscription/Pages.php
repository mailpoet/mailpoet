<?php
namespace MailPoet\Subscription;

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\CustomField;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Segment;
use \MailPoet\Util\Helpers;
use \MailPoet\Util\Url;
use \MailPoet\Subscription;

class Pages {
  const DEMO_EMAIL = 'demo@mailpoet.com';

  function __construct() {
  }

  function init() {
    $action = $this->getAction();
    if($action !== null) {
      add_filter('document_title_parts', array($this,'setWindowTitle'), 10, 1);
      add_filter('the_title', array($this,'setPageTitle'), 10, 1);
      add_filter('the_content', array($this,'setPageContent'), 10, 1);
    }
    add_action(
      'admin_post_mailpoet_subscriber_save',
      array($this, 'subscriberSave')
    );
    add_action(
      'admin_post_nopriv_mailpoet_subscriber_save',
      array($this, 'subscriberSave')
    );
  }

  function subscriberSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    if($action !== 'mailpoet_subscriber_save') {
      Url::redirectBack();
    }

    $reserved_keywords = array('action', 'mailpoet_redirect');
    $subscriber_data = array_diff_key(
      $_POST,
      array_flip($reserved_keywords)
    );
    if(isset($subscriber_data['email'])) {
      if($subscriber_data['email'] !== self::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate($subscriber_data);
        $errors = $subscriber->getErrors();
      }
    }
    // TBD: success/error messages (not present in MP2)

    Url::redirectBack();
  }

  function isPreview() {
    return (array_key_exists('mailpoet_preview', $_GET));
  }

  function setWindowTitle($meta = array()) {
    $meta['title'] = $this->setPageTitle($meta['title']);
    return $meta;
  }

  function setPageTitle($page_title = '[mailpoet_title]') {
   if(
      (strpos($page_title, '[mailpoet_title]') === false)
      &&
      (strlen(trim($page_title)) > 0)
    ) {
      return $page_title;
    } else {
      $subscriber = $this->getSubscriber();
      switch($this->getAction()) {
        case 'confirm':
          return $this->getConfirmTitle($subscriber);
        break;

        case 'manage':
          return $this->getManageTitle($subscriber);
        break;

        case 'unsubscribe':
          if($subscriber !== false) {
            if($subscriber->status !== Subscriber::STATUS_UNSUBSCRIBED) {
              $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
              $subscriber->save();
            }
          }
          return $this->getUnsubscribeTitle($subscriber);
        break;
      }
    }
    return str_replace('[mailpoet_title]', $title, $page_title);
  }

  function setPageContent($page_content = '[mailpoet_page]') {
    $content = '';
    $subscriber = $this->getSubscriber();

    switch($this->getAction()) {
      case 'confirm':
        $content = $this->getConfirmContent($subscriber);
      break;
      case 'manage':
        $content = $this->getManageContent($subscriber);
      break;
      case 'unsubscribe':
        $content = $this->getUnsubscribeContent($subscriber);
      break;
    }
    return str_replace('[mailpoet_page]', $content, $page_content);
  }

  private function getConfirmTitle($subscriber) {
    if($this->isPreview()) {
      $title = sprintf(
        __("You've subscribed to: %s"),
        'demo 1, demo 2'
      );
    } else if($subscriber === false) {
      $title = __('Your confirmation link expired, please subscribe again.');
    } else {
      if($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
        $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
        $subscriber->save();
      }

      $segment_names = array_map(function($segment) {
        return $segment->name;
      }, $subscriber->segments()->findMany());

      if(empty($segment_names)) {
        $title = __("You've subscribed!");
      } else {
        $title = sprintf(
          __("You've subscribed to: %s"),
          join(', ', $segment_names)
        );
      }
    }
    return $title;
  }

  private function getManageTitle($subscriber) {
    if($this->isPreview()) {
      return sprintf(
        __('Edit your subscriber profile: %s'),
        self::DEMO_EMAIL
      );
    } else if($subscriber !== false) {
      return sprintf(
        __('Edit your subscriber profile: %s'),
        $subscriber->email
      );
    }
  }

  private function getUnsubscribeTitle($subscriber) {
    if($this->isPreview() || $subscriber !== false) {
      return __("You've unsubscribed!");
    }
  }


  private function getConfirmContent($subscriber) {
    if($this->isPreview() || $subscriber !== false) {
      return __("Yup, we've added you to our list. You'll hear from us shortly.");
    }
  }

  private function getManageContent($subscriber) {
    if($this->isPreview()) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate(array(
        'email' => self::DEMO_EMAIL
      ));
    } else if($subscriber !== false) {
      $subscriber = $subscriber
      ->withCustomFields()
      ->withSubscriptions();
    } else {
      return;
    }

    $custom_fields = array_map(function($custom_field) use($subscriber) {
      $custom_field->id = 'cf_'.$custom_field->id;
      $custom_field = $custom_field->asArray();
      $custom_field['params']['value'] = $subscriber->{$custom_field['id']};
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
    if(!empty($subscriber->subscriptions)) {
      foreach ($subscriber->subscriptions as $subscription) {
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
        'id' => 'email',
        'type' => 'text',
        'params' => array(
          'label' => __('Email'),
          'required' => true,
          'value' => $subscriber->email,
          'readonly' => true
        )
      ),
      array(
        'id' => 'first_name',
        'type' => 'text',
        'params' => array(
          'label' => __('First name'),
          'value' => $subscriber->first_name
        )
      ),
      array(
        'id' => 'last_name',
        'type' => 'text',
        'params' => array(
          'label' => __('Last name'),
          'value' => $subscriber->last_name
        )
      ),
      array(
        'id' => 'status',
        'type' => 'select',
        'params' => array(
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
    $form_html .= '<input type="hidden" name="action" '.
      'value="mailpoet_subscriber_save" />';
    $form_html .= '<input type="hidden" name="segments" value="" />';
    $form_html .= '<input type="hidden" name="mailpoet_redirect" '.
      'value="'.Url::getCurrentUrl().'" />';
    $form_html .= \MailPoet\Form\Renderer::renderBlocks($form);
    $form_html .= '</form>';
    return $form_html;
  }

  private function getUnsubscribeContent($subscriber) {
    $content = '';
    if($this->isPreview() || $subscriber !== false) {
      $content = '<p>'.__("Great, you'll never hear from us again!").'</p>';
      if($subscriber !== false) {
        $content .= '<p><strong>'.
          str_replace(
            array('[link]', '[/link]'),
            array('<a href="'.Subscription\Url::getConfirmationUrl($subscriber).'">', '</a>'),
            __('You made a mistake? [link]Undo unsubscribe.[/link]')
          ).
        '</strong></p>';
      }
    }
    return $content;
  }

  private function getSubscriber() {
    $token = (isset($_GET['mailpoet_token']))
      ? $_GET['mailpoet_token']
      : null;
    $email = (isset($_GET['mailpoet_email']))
      ? $_GET['mailpoet_email']
      : null;

    if(Subscriber::generateToken($email) === $token) {
      $subscriber = Subscriber::findOne($email);
      if($subscriber !== false) {
        return $subscriber;
      }
    }
    return false;
  }

  private function getAction() {
    return (isset($_GET['mailpoet_action']))
      ? $_GET['mailpoet_action']
      : null;
  }
}