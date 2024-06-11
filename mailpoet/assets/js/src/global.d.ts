/* eslint-disable @typescript-eslint/no-explicit-any */

declare module 'wp-js-hooks' {
  import * as WPHooks from '@wordpress/hooks';

  export const Hooks: WPHooks;
}

type ErrorResponse = {
  errors: {
    message: string;
  }[];
};

type MtaLog = {
  status: string;
  error: {
    error_message: ?string;
    error_code: ?string;
    operation: ?string;
  };
};

interface JQuery {
  parsley: (options?: { successClass?: string }) => any;
  mailpoetSerializeObject: () => {
    recaptchaWidgetId: number;
    token: string;
    api_version: string;
    data: {
      recaptchaResponseToken: string;
    };
  };
  velocity: (selector: string, options?: Record<string, unknown>) => void;
  stick_in_parent: (options?: { offset_top: number }) => void;
}

/* Type definitions for components used from external libraries */
declare module '@woocommerce/blocks-checkout' {
  type CheckboxControlProps = {
    className?: string;
    label?: string;
    id?: string;
    instanceId?: string;
    onChange?: (value: boolean) => void;
    children?: React.ReactChildren | React.ReactElement;
    hasError?: boolean;
    checked?: boolean;
  };
  export const CheckboxControl: (props: CheckboxControlProps) => JSX.Element;
}

declare module '@woocommerce/settings' {
  interface MailPoetSettings {
    optinEnabled: boolean;
    defaultText: string;
  }

  function getSetting(name: 'mailpoet_data'): MailPoetSettings;
  function getSetting(name: 'adminUrl'): string;
}

declare module '@woocommerce/blocks-checkout' {
  import type { BlockConfiguration } from '@wordpress/blocks';

  interface CheckoutBlockOptionsMetadata extends Partial<BlockConfiguration> {
    name: string;
    parent: string[];
  }

  type CheckoutBlockOptions = {
    metadata: CheckoutBlockOptionsMetadata;
    component: (x) => JSX.Element;
  };

  function registerCheckoutBlock(options: CheckoutBlockOptions): void;
}

type WooCommerceStoreConfig =
  | {
      precision: number | string | null;
      decimalSeparator: string;
      thousandSeparator: string;
      code: string;
      symbol: string;
      symbolPosition: 'left' | 'right' | 'left_space' | 'right_space';
      priceFormat?: string;
    }
  | undefined;

type BaseCapability = {
  isRestricted: boolean;
  type: 'boolean' | 'number';
};

type BooleanCapability = BaseCapability & {
  type: 'boolean';
  value: never;
};
type NumberCapability = BaseCapability & {
  type: 'number';
  value: number;
};
type Capability = BooleanCapability | NumberCapability;
type Capabilities = Record<string, Capability>;

interface Window {
  ajaxurl: string;
  mailpoet_wp_locale: string;
  mailpoet_token: string;
  mailpoet_feature_flags: string;
  mailpoet_referral_id: string;
  mailpoet_version: string;
  mailpoet_premium_version: string;
  mailpoet_premium_link: string;
  mailpoet_woocommerce_active: boolean;
  mailpoet_woocommerce_subscriptions_active: boolean;
  mailpoet_woocommerce_store_config: WooCommerceStoreConfig;
  mailpoet_woocommerce_version: string;
  mailpoet_track_wizard_loaded_via_woocommerce: boolean;
  mailpoet_track_wizard_loaded_via_woocommerce_marketing_dashboard: boolean;
  mailpoet_premium_active: boolean;
  mailpoet_subscribers_limit: number;
  mailpoet_subscribers_limit_reached: boolean;
  mailpoet_subscribers_count: number;
  mailpoet_has_premium_support: boolean;
  mailpoet_has_valid_api_key: boolean;
  mailpoet_has_valid_premium_key: boolean;
  mailpoet_mss_key_invalid: boolean;
  mailpoet_mss_key_valid: boolean;
  mailpoet_mta_method: string;
  mailpoet_date_offset: string;
  mailpoet_time_format: string;
  mailpoet_date_format: string;
  mailpoet_server_timezone_in_minutes: number;
  mailpoet_listing_per_page: string;
  mailpoet_3rd_party_libs_enabled: string;
  mailpoet_datetime_format: string;
  mailpoet_api_version: string;
  mailpoet_email_regex: RegExp;
  mailpoet_wp_segment_state: string;
  mailpoet_wp_week_starts_on: 0 | 1 | 2 | 3 | 4 | 5 | 6;
  mailpoet_subscribers_counts_cache_created_at: string;
  mailpoet_shortcode_links: Record<string, string>;
  mailpoet_tracking_config: Partial<{
    level: 'full' | 'partial' | 'basic';
    cookieTrackingEnabled: boolean;
    emailTrackingEnabled: boolean;
  }>;
  mailpoet_display_detailed_stats: boolean;
  mailpoet_premium_plugin_installed: boolean;
  mailpoet_premium_plugin_download_url: string;
  mailpoet_premium_plugin_activation_url: string;
  mailpoet_plugin_partial_key: string;
  mailpoet_email_volume_limit: string;
  mailpoet_email_volume_limit_reached: boolean;
  mailpoet_capabilities: Capabilities;
  mailpoet_tier: number | null;
  mailpoet_current_wp_user_email: string;
  mailpoet_current_time?: string;
  mailpoet_current_date?: string;
  mailpoet_tomorrow_date?: string;
  mailpoet_schedule_time_of_day?: string;
  mailpoet_date_storage_format?: string;
  mailpoet_current_date_time?: string;
  mailpoet_urls: Record<string, string>;
  recaptcha?: unknown;
  grecaptcha?: any;
  MailPoetForm?: {
    ajax_url: string;
    ajax_common_error_message: string;
  };
  mailpoet_authorized_emails?: string[];
  mailpoet_verified_sender_domains?: string[];
  mailpoet_partially_verified_sender_domains?: string[];
  mailpoet_sender_restrictions?: {
    lowerLimit: number;
    isAuthorizedDomainRequiredForNewCampaigns?: boolean;
    campaignTypes?: string[];
  };
  mailpoet_all_sender_domains?: string[];
  mailpoet_mss_active: boolean;
  mailpoet_free_domains?: string[];
  mailpoet_installed_at?: string;
  mailpoet_email_editor_tutorial_seen: number;
  mailpoet_email_editor_tutorial_url: string;
  mailpoet_deactivate_subscriber_after_inactive_days: string;
  mailpoet_current_site_title?: string;
  mailpoet_tags?: {
    id: number;
    name: string;
  }[];
  mailpoet_cdn_url: string;
  mailpoet_main_page_slug: string;
  finish_wizard_url: string;
  admin_email: string;
  wizard_sender_illustration_url: string;
  wizard_tracking_illustration_url: string;
  wizard_MSS_pitch_illustration_url: string;
  wizard_woocommerce_illustration_url: string;
  wizard_has_tracking_settings: boolean;
  mailpoet_account_url: string;
  mailpoet_show_customers_import: boolean;
  mailpoet_installed_days_ago: number;
  mailpoet_send_transactional_emails: boolean;
  mailpoet_transactional_emails_opt_in_notice_dismissed: boolean;

  mailpoet_mta_log?: MtaLog;
  mailpoet_listing: {
    forceUpdate: () => void;
  };
  mailpoet_welcome_wizard_url: string;
  mailpoet_welcome_wizard_current_step: string;
  mailpoet_homepage_data: {
    taskListDismissed: boolean;
    productDiscoveryDismissed: boolean;
    upsellDismissed: boolean;
    taskListStatus: {
      senderSet: boolean;
      mssConnected: boolean;
      subscribersAdded: boolean;
      wooSubscribersImported: boolean;
      senderDomainAuthenticated: boolean;
    } | null;
    productDiscoveryStatus: {
      setUpWelcomeCampaign: boolean;
      addSubscriptionForm: boolean;
      sendFirstNewsletter: boolean;
      setUpAbandonedCartEmail: boolean;
      brandWooEmails: boolean;
    } | null;
    upsellStatus: {
      canDisplay: boolean;
    } | null;
    subscribersStats: {
      global: {
        subscribed: number;
        unsubscribed: number;
        changePercent: number;
      };
      lists: {
        subscribed: number;
        unsubscribed: number;
        name: string;
        id: number;
        type: string;
        averageEngagementScore: number;
      }[];
    };
    wooCustomersCount: number;
    subscribersCount: number;
    formsCount: number;
    isNewUserForSenderDomainAuth: boolean;
    isFreeMailUser: boolean;
  };
  templates?: Record<string, string> & { sidebar: string };
  is_wc_active?: boolean;
  systemInfoData?: Record<string, string>;
  mailpoet_mail_function_enabled: boolean;
  mailpoet_mss_key_pending_approval: boolean;
  mailpoet_show_congratulate_after_first_newsletter?: boolean;
  mailpoet_sender_address_field_blur?: () => void;
  mailpoet_woocommerce_transactional_email_id?: string;
  mailpoet_is_new_user?: boolean;
  mailpoet_editor_javascript_url?: string;
  mailpoet_woocommerce_automatic_emails?: Record<
    string,
    {
      slug: string;
      title: string;
      description: string;
      events: Record<string, Record<string, unknown>>;
    }
  >;
  corrupt_newsletters: Array<{
    id: string;
    subject: string;
  }>;
  mailpoet_brand_styles?: {
    available: boolean;
  };
  mailpoet_max_confirmation_emails: number;
  mailpoet_segments: Array<{
    id: string;
    name: string;
    subscribers: string;
    type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
  }>;
  mailpoet_admin_plugins_url: string;
  mailpoet_is_dotcom: boolean;
  mailpoet_cron_trigger_method: string;
  mailpoet_dynamic_segment_count: number;
}
