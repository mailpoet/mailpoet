<?php

namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;
use MailPoet\WP\Functions as WPFunctions;

class ShortcodesHelper {

  static function getShortcodes() {
    $shortcodes = array(
      WPFunctions::get()->__('Subscriber', 'mailpoet') => array(
        array(
          'text' => WPFunctions::get()->__('First Name', 'mailpoet'),
          'shortcode' => '[subscriber:firstname | default:reader]',
        ),
        array(
          'text' => WPFunctions::get()->__('Last Name', 'mailpoet'),
          'shortcode' => '[subscriber:lastname | default:reader]',
        ),
        array(
          'text' => WPFunctions::get()->__('Email Address', 'mailpoet'),
          'shortcode' => '[subscriber:email]',
        ),
        array(
          'text' => WPFunctions::get()->__('WordPress User Display Name', 'mailpoet'),
          'shortcode' => '[subscriber:displayname | default:member]',
        ),
        array(
          'text' => WPFunctions::get()->__('Total Number of Subscribers', 'mailpoet'),
          'shortcode' => '[subscriber:count]',
        )
      ),
      WPFunctions::get()->__('Newsletter', 'mailpoet') => array(
        array(
          'text' => WPFunctions::get()->__('Newsletter Subject', 'mailpoet'),
          'shortcode' => '[newsletter:subject]',
        )
      ),
      WPFunctions::get()->__('Post Notifications', 'mailpoet') => array(
        array(
          'text' => WPFunctions::get()->__('Total Number of Posts or Pages', 'mailpoet'),
          'shortcode' => '[newsletter:total]',
        ),
        array(
          'text' => WPFunctions::get()->__('Most Recent Post Title', 'mailpoet'),
          'shortcode' => '[newsletter:post_title]',
        ),
        array(
          'text' => WPFunctions::get()->__('Issue Number', 'mailpoet'),
          'shortcode' => '[newsletter:number]',
        )
      ),
      WPFunctions::get()->__('Date', 'mailpoet') => array(
        array(
          'text' => WPFunctions::get()->__('Current day of the month number', 'mailpoet'),
          'shortcode' => '[date:d]',
        ),
        array(
          'text' => WPFunctions::get()->__('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', 'mailpoet'),
          'shortcode' => '[date:dordinal]',
        ),
        array(
          'text' => WPFunctions::get()->__('Full name of current day', 'mailpoet'),
          'shortcode' => '[date:dtext]',
        ),
        array(
          'text' => WPFunctions::get()->__('Current month number', 'mailpoet'),
          'shortcode' => '[date:m]',
        ),
        array(
          'text' => WPFunctions::get()->__('Full name of current month', 'mailpoet'),
          'shortcode' => '[date:mtext]',
        ),
        array(
          'text' => WPFunctions::get()->__('Year', 'mailpoet'),
          'shortcode' => '[date:y]',
        )
      ),
      WPFunctions::get()->__('Links', 'mailpoet') => array(
        array(
          'text' => WPFunctions::get()->__('Unsubscribe link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:subscription_unsubscribe_url]',
            WPFunctions::get()->__('Unsubscribe', 'mailpoet')
          )
        ),
        array(
          'text' => WPFunctions::get()->__('Edit subscription page link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:subscription_manage_url]',
            WPFunctions::get()->__('Manage subscription', 'mailpoet')
          )
        ),
        array(
          'text' => WPFunctions::get()->__('View in browser link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:newsletter_view_in_browser_url]',
            WPFunctions::get()->__('View in your browser', 'mailpoet')
          )
        )
      )
    );
    $custom_fields = self::getCustomFields();
    if ($custom_fields) {
      $shortcodes[__('Subscriber', 'mailpoet')] = array_merge(
        $shortcodes[__('Subscriber', 'mailpoet')],
        $custom_fields
      );
    }
    return $shortcodes;
  }

  static function getCustomFields() {
    $custom_fields = CustomField::findMany();
    if (!$custom_fields) return false;
    return array_map(function($custom_field) {
      return array(
        'text' => $custom_field->name,
        'shortcode' => '[subscriber:cf_' . $custom_field->id . ']'
      );
    }, $custom_fields);
  }
}
