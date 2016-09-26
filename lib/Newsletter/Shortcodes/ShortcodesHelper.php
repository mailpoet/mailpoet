<?php
namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;

class ShortcodesHelper {

  static function getShortcodes() {
    $shortcodes = array(
      __('Subscriber', Env::$plugin_name) => array(
        array(
          'text' => __('First Name', Env::$plugin_name),
          'shortcode' => 'subscriber:firstname | default:reader',
        ),
        array(
          'text' => __('Last Name', Env::$plugin_name),
          'shortcode' => 'subscriber:lastname | default:reader',
        ),
        array(
          'text' => __('Email Address', Env::$plugin_name),
          'shortcode' => 'subscriber:email',
        ),
        array(
          'text' => __('WordPress User Display Name', Env::$plugin_name),
          'shortcode' => 'subscriber:displayname | default:member',
        ),
        array(
          'text' => __('Total Number of Subscribers', Env::$plugin_name),
          'shortcode' => 'subscriber:count',
        )
      ),
      __('Newsletter', Env::$plugin_name) => array(
        array(
          'text' => __('Newsletter Subject', Env::$plugin_name),
          'shortcode' => 'newsletter:subject',
        )
      ),
      __('Post Notifications', Env::$plugin_name) => array(
        array(
          'text' => __('Total Number of Posts or Pages', Env::$plugin_name),
          'shortcode' => 'newsletter:total',
        ),
        array(
          'text' => __('Most Recent Post Title', Env::$plugin_name),
          'shortcode' => 'newsletter:post_title',
        ),
        array(
          'text' => __('Issue Number', Env::$plugin_name),
          'shortcode' => 'newsletter:number',
        )
      ),
      __('Date', Env::$plugin_name) => array(
        array(
          'text' => __('Current day of the month number', Env::$plugin_name),
          'shortcode' => 'date:d',
        ),
        array(
          'text' => __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', Env::$plugin_name),
          'shortcode' => 'date:dordinal',
        ),
        array(
          'text' => __('Full name of current day', Env::$plugin_name),
          'shortcode' => 'date:dtext',
        ),
        array(
          'text' => __('Current month number', Env::$plugin_name),
          'shortcode' => 'date:m',
        ),
        array(
          'text' => __('Full name of current month', Env::$plugin_name),
          'shortcode' => 'date:mtext',
        ),
        array(
          'text' => __('Year', Env::$plugin_name),
          'shortcode' => 'date:y',
        )
      ),
      __('Links', Env::$plugin_name) => array(
        array(
          'text' => __('Unsubscribe link', Env::$plugin_name),
          'shortcode' => 'link:subscription_unsubscribe',
        ),
        array(
          'text' => __('Edit subscription page link', Env::$plugin_name),
          'shortcode' => 'link:subscription_manage',
        ),
        array(
          'text' => __('View in browser link', Env::$plugin_name),
          'shortcode' => 'link:newsletter_view_in_browser',
        )
      )
    );
    $custom_fields = self::getCustomFields();
    if($custom_fields) {
      $shortcodes[__('Subscriber', Env::$plugin_name)] = array_merge(
        $shortcodes[__('Subscriber', Env::$plugin_name)],
        $custom_fields
      );
    }
    return $shortcodes;
  }

  static function getCustomFields() {
    $custom_fields = CustomField::findMany();
    if(!$custom_fields) return false;
    return array_map(function ($custom_field) {
      return array(
        'text' => $custom_field->name,
        'shortcode' => 'subscriber:cf_' . $custom_field->id
      );
    }, $custom_fields);
  }
}