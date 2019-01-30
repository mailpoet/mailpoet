<?php
namespace MailPoet\Subscription;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;

class Registration {

  static function extendForm() {
    $settings = new SettingsController();
    $label = $settings->get(
      'subscribe.on_register.label',
      __('Yes, please add me to your mailing list.', 'mailpoet')
    );

    print '<p class="registration-form-mailpoet">
      <label for="mailpoet_subscribe_on_register">
        <input
          type="checkbox"
          id="mailpoet_subscribe_on_register"
          value="1"
          name="mailpoet[subscribe_on_register]"
        />&nbsp;'.esc_attr($label).'
      </label>
    </p>';
  }

  static function onMultiSiteRegister($result) {
    if(empty($result['errors']->errors)) {
      if(
        isset($_POST['mailpoet']['subscribe_on_register'])
        && (bool)$_POST['mailpoet']['subscribe_on_register'] === true
      ) {
        static::subscribeNewUser(
          $result['user_name'],
          $result['user_email']
        );
      }
    }
    return $result;
  }

  static function onRegister(
    $user_login,
    $user_email = null,
    $errors = null
  ) {
    if(
      empty($errors->errors)
      && isset($_POST['mailpoet']['subscribe_on_register'])
      && (bool)$_POST['mailpoet']['subscribe_on_register'] === true
    ) {
      static::subscribeNewUser(
        $user_login,
        $user_email
      );
    }
  }

  private static function subscribeNewUser($name, $email) {
    $settings = new SettingsController();
    $segment_ids = $settings->get(
      'subscribe.on_register.segments',
      []
    );

    if(!empty($segment_ids)) {
      Subscriber::subscribe(
        array(
          'email' => $email,
          'first_name' => $name
        ),
        $segment_ids
      );
    }
  }
}
