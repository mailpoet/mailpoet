<?php
namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;

class ShortcodesHelper {

  static function getShortcodes() {
    $shortcodes = array(
      __('Subscriber', 'mailpoet') => array(
        array(
          'text' => __('First Name', 'mailpoet'),
          'shortcode' => '[subscriber:firstname | default:reader]',
        ),
        array(
          'text' => __('Last Name', 'mailpoet'),
          'shortcode' => '[subscriber:lastname | default:reader]',
        ),
        array(
          'text' => __('Email Address', 'mailpoet'),
          'shortcode' => '[subscriber:email]',
        ),
        array(
          'text' => __('WordPress User Display Name', 'mailpoet'),
          'shortcode' => '[subscriber:displayname | default:member]',
        ),
        array(
          'text' => __('Total Number of Subscribers', 'mailpoet'),
          'shortcode' => '[subscriber:count]',
        )
      ),
      __('Newsletter', 'mailpoet') => array(
        array(
          'text' => __('Newsletter Subject', 'mailpoet'),
          'shortcode' => '[newsletter:subject]',
        )
      ),
      __('Post Notifications', 'mailpoet') => array(
        array(
          'text' => __('Total Number of Posts or Pages', 'mailpoet'),
          'shortcode' => '[newsletter:total]',
        ),
        array(
          'text' => __('Most Recent Post Title', 'mailpoet'),
          'shortcode' => '[newsletter:post_title]',
        ),
        array(
          'text' => __('Issue Number', 'mailpoet'),
          'shortcode' => '[newsletter:number]',
        )
      ),
      __('Date', 'mailpoet') => array(
        array(
          'text' => __('Current day of the month number', 'mailpoet'),
          'shortcode' => '[date:d]',
        ),
        array(
          'text' => __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', 'mailpoet'),
          'shortcode' => '[date:dordinal]',
        ),
        array(
          'text' => __('Full name of current day', 'mailpoet'),
          'shortcode' => '[date:dtext]',
        ),
        array(
          'text' => __('Current month number', 'mailpoet'),
          'shortcode' => '[date:m]',
        ),
        array(
          'text' => __('Full name of current month', 'mailpoet'),
          'shortcode' => '[date:mtext]',
        ),
        array(
          'text' => __('Year', 'mailpoet'),
          'shortcode' => '[date:y]',
        )
      ),
      __('Links', 'mailpoet') => array(
        array(
          'text' => __('Unsubscribe link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:subscription_unsubscribe_url]',
            __('Unsubscribe', 'mailpoet')
          )
        ),
        array(
          'text' => __('Edit subscription page link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:subscription_manage_url]',
            __('Manage subscription', 'mailpoet')
          )
        ),
        array(
          'text' => __('View in browser link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:newsletter_view_in_browser_url]',
            __('View in your browser', 'mailpoet')
          )
        )
      )
    );
    $custom_fields = self::getCustomFields();
    if($custom_fields) {
      $shortcodes[__('Subscriber', 'mailpoet')] = array_merge(
        $shortcodes[__('Subscriber', 'mailpoet')],
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