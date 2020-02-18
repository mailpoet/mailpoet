<?php

namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Models\CustomField;
use MailPoet\Models\NewsletterLink;
use MailPoet\WP\Functions as WPFunctions;

class ShortcodesHelper {
  public static function getShortcodes() {
    $shortcodes = [
      WPFunctions::get()->__('Subscriber', 'mailpoet') => [
        [
          'text' => WPFunctions::get()->__('First Name', 'mailpoet'),
          'shortcode' => '[subscriber:firstname | default:reader]',
        ],
        [
          'text' => WPFunctions::get()->__('Last Name', 'mailpoet'),
          'shortcode' => '[subscriber:lastname | default:reader]',
        ],
        [
          'text' => WPFunctions::get()->__('Email Address', 'mailpoet'),
          'shortcode' => '[subscriber:email]',
        ],
        [
          'text' => WPFunctions::get()->__('WordPress User Display Name', 'mailpoet'),
          'shortcode' => '[subscriber:displayname | default:member]',
        ],
        [
          'text' => WPFunctions::get()->__('Total Number of Subscribers', 'mailpoet'),
          'shortcode' => '[subscriber:count]',
        ],
      ],
      WPFunctions::get()->__('Newsletter', 'mailpoet') => [
        [
          'text' => WPFunctions::get()->__('Newsletter Subject', 'mailpoet'),
          'shortcode' => '[newsletter:subject]',
        ],
      ],
      WPFunctions::get()->__('Post Notifications', 'mailpoet') => [
        [
          'text' => WPFunctions::get()->__('Total Number of Posts or Pages', 'mailpoet'),
          'shortcode' => '[newsletter:total]',
        ],
        [
          'text' => WPFunctions::get()->__('Most Recent Post Title', 'mailpoet'),
          'shortcode' => '[newsletter:post_title]',
        ],
        [
          'text' => WPFunctions::get()->__('Issue Number', 'mailpoet'),
          'shortcode' => '[newsletter:number]',
        ],
      ],
      WPFunctions::get()->__('Date', 'mailpoet') => [
        [
          'text' => WPFunctions::get()->__('Current day of the month number', 'mailpoet'),
          'shortcode' => '[date:d]',
        ],
        [
          'text' => WPFunctions::get()->__('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', 'mailpoet'),
          'shortcode' => '[date:dordinal]',
        ],
        [
          'text' => WPFunctions::get()->__('Full name of current day', 'mailpoet'),
          'shortcode' => '[date:dtext]',
        ],
        [
          'text' => WPFunctions::get()->__('Current month number', 'mailpoet'),
          'shortcode' => '[date:m]',
        ],
        [
          'text' => WPFunctions::get()->__('Full name of current month', 'mailpoet'),
          'shortcode' => '[date:mtext]',
        ],
        [
          'text' => WPFunctions::get()->__('Year', 'mailpoet'),
          'shortcode' => '[date:y]',
        ],
      ],
      WPFunctions::get()->__('Links', 'mailpoet') => [
        [
          'text' => WPFunctions::get()->__('Unsubscribe link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            NewsletterLink::UNSUBSCRIBE_LINK_SHORT_CODE,
            WPFunctions::get()->__('Unsubscribe', 'mailpoet')
          ),
        ],
        [
          'text' => WPFunctions::get()->__('Edit subscription page link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:subscription_manage_url]',
            WPFunctions::get()->__('Manage subscription', 'mailpoet')
          ),
        ],
        [
          'text' => WPFunctions::get()->__('View in browser link', 'mailpoet'),
          'shortcode' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            '[link:newsletter_view_in_browser_url]',
            WPFunctions::get()->__('View in your browser', 'mailpoet')
          ),
        ],
      ],
    ];
    $customFields = self::getCustomFields();
    if ($customFields) {
      $shortcodes[__('Subscriber', 'mailpoet')] = array_merge(
        $shortcodes[__('Subscriber', 'mailpoet')],
        $customFields
      );
    }
    return $shortcodes;
  }

  public static function getCustomFields() {
    $customFields = CustomField::findMany();
    if (!$customFields) return false;
    return array_map(function($customField) {
      return [
        'text' => $customField->name,
        'shortcode' => '[subscriber:cf_' . $customField->id . ']',
      ];
    }, $customFields);
  }
}
