<?php
namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;

class ShortcodesHelper {

  static function getShortcodes() {
    $shortcodes = array(
      __('Subscriber', MAILPOET) => array(
        array(
          'text' => __('First Name', MAILPOET),
          'shortcode' => 'subscriber:firstname | default:reader',
        ),
        array(
          'text' => __('Last Name', MAILPOET),
          'shortcode' => 'subscriber:lastname | default:reader',
        ),
        array(
          'text' => __('Email Address', MAILPOET),
          'shortcode' => 'subscriber:email',
        ),
        array(
          'text' => __('WordPress User Display Name', MAILPOET),
          'shortcode' => 'subscriber:displayname | default:member',
        ),
        array(
          'text' => __('Total Number of Subscribers', MAILPOET),
          'shortcode' => 'subscriber:count',
        )
      ),
      __('Newsletter', MAILPOET) => array(
        array(
          'text' => __('Newsletter Subject', MAILPOET),
          'shortcode' => 'newsletter:subject',
        )
      ),
      __('Post Notifications', MAILPOET) => array(
        array(
          'text' => __('Total Number of Posts or Pages', MAILPOET),
          'shortcode' => 'newsletter:total',
        ),
        array(
          'text' => __('Most Recent Post Title', MAILPOET),
          'shortcode' => 'newsletter:post_title',
        ),
        array(
          'text' => __('Issue Number', MAILPOET),
          'shortcode' => 'newsletter:number',
        )
      ),
      __('Date', MAILPOET) => array(
        array(
          'text' => __('Current day of the month number', MAILPOET),
          'shortcode' => 'date:d',
        ),
        array(
          'text' => __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', MAILPOET),
          'shortcode' => 'date:dordinal',
        ),
        array(
          'text' => __('Full name of current day', MAILPOET),
          'shortcode' => 'date:dtext',
        ),
        array(
          'text' => __('Current month number', MAILPOET),
          'shortcode' => 'date:m',
        ),
        array(
          'text' => __('Full name of current month', MAILPOET),
          'shortcode' => 'date:mtext',
        ),
        array(
          'text' => __('Year', MAILPOET),
          'shortcode' => 'date:y',
        )
      ),
      __('Links', MAILPOET) => array(
        array(
          'text' => __('Unsubscribe link', MAILPOET),
          'shortcode' => 'link:subscription_unsubscribe',
        ),
        array(
          'text' => __('Edit subscription page link', MAILPOET),
          'shortcode' => 'link:subscription_manage',
        ),
        array(
          'text' => __('View in browser link', MAILPOET),
          'shortcode' => 'link:newsletter_view_in_browser',
        )
      )
    );
    $custom_fields = self::getCustomFields();
    if($custom_fields) {
      $shortcodes[__('Subscriber', MAILPOET)] = array_merge(
        $shortcodes[__('Subscriber', MAILPOET)],
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