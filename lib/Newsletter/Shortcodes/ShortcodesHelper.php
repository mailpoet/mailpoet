<?php
namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;

class ShortcodesHelper {

  static function getShortcodes() {
    $shortcodes = array(
      __('Subscriber') => array(
        array(
          'text' => __('First Name'),
          'shortcode' => 'subscriber:firstname | default:reader',
        ),
        array(
          'text' => __('Last Name'),
          'shortcode' => 'subscriber:lastname | default:reader',
        ),
        array(
          'text' => __('Email Address'),
          'shortcode' => 'subscriber:email',
        ),
        array(
          'text' => __('Wordpress User Display Name'),
          'shortcode' => 'subscriber:displayname | default:member',
        ),
        array(
          'text' => __('Total Number of Subscribers'),
          'shortcode' => 'subscriber:count',
        )
      ),
      __('Newsletter') => array(
        array(
          'text' => __('Newsletter Subject'),
          'shortcode' => 'newsletter:subject',
        )
      ),
      __('Post Notifications') => array(
        array(
          'text' => __('Total Number of Posts or Pages'),
          'shortcode' => 'newsletter:total',
        ),
        array(
          'text' => __('Most Recent Post Title'),
          'shortcode' => 'newsletter:post_title',
        ),
        array(
          'text' => __('Issue Number'),
          'shortcode' => 'newsletter:number',
        )
      ),
      __('Date') => array(
        array(
          'text' => __('Current day of the month number'),
          'shortcode' => 'date:d',
        ),
        array(
          'text' => __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.'),
          'shortcode' => 'date:dordinal',
        ),
        array(
          'text' => __('Full name of current day'),
          'shortcode' => 'date:dtext',
        ),
        array(
          'text' => __('Current month number'),
          'shortcode' => 'date:m',
        ),
        array(
          'text' => __('Full name of current month'),
          'shortcode' => 'date:mtext',
        ),
        array(
          'text' => __('Year'),
          'shortcode' => 'date:y',
        )
      ),
      __('Links') => array(
        array(
          'text' => __('Unsubscribe link'),
          'shortcode' => 'link:subscription_unsubscribe',
        ),
        array(
          'text' => __('Edit subscription page link'),
          'shortcode' => 'link:subscription_manage',
        ),
        array(
          'text' => __('View in browser link'),
          'shortcode' => 'link:newsletter_view_in_browser',
        )
      )
    );
    $custom_fields = self::getCustomFields();
    if($custom_fields) {
      $shortcodes[__('Subscriber')] = array_merge(
        $shortcodes[__('Subscriber')],
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