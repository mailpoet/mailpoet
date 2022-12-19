/* eslint-disable @typescript-eslint/no-explicit-any */

declare module 'wp-js-hooks' {
  type Hooks = {
    addFilter: (
      name: string,
      namespace: string,
      callback: (...args: any[]) => any,
    ) => void;
    applyFilters: (name: string, ...args: any[]) => any;
  };
  export const Hooks: Hooks;
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
  parsley: () => any;
  mailpoetSerializeObject: () => {
    recaptchaWidgetId: number;
    token: string;
    api_version: string;
    data: {
      recaptchaResponseToken: string;
    };
  };
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
    defaultStatus: boolean;
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

interface Window {
  ajaxurl: string;
  mailpoet_token: string;
  mailpoet_feature_flags: string;
  mailpoet_referral_id: string;
  mailpoet_version: string;
  mailpoet_premium_version: string;
  mailpoet_premium_link: string;
  mailpoet_woocommerce_active: boolean;
  mailpoet_premium_active: boolean;
  mailpoet_subscribers_limit: number;
  mailpoet_subscribers_limit_reached: boolean;
  mailpoet_subscribers_count: number;
  mailpoet_has_premium_support: boolean;
  mailpoet_has_valid_api_key: boolean;
  mailpoet_has_valid_premium_key: string;
  mailpoet_mss_key_invalid: boolean;
  mailpoet_mta_method: string;
  mailpoet_date_offset: string;
  mailpoet_time_format: string;
  mailpoet_date_format: string;
  mailpoet_listing_per_page: string;
  mailpoet_3rd_party_libs_enabled: string;
  mailpoet_datetime_format: string;
  mailpoet_api_version: string;
  mailpoet_email_regex: RegExp;
  mailpoet_wp_segment_state: string;
  mailpoet_wp_week_starts_on: number;
  mailpoet_subscribers_counts_cache_created_at: string;
  mailpoet_shortcode_links: string[];
  mailpoet_tracking_config: {
    level: 'full' | 'partial' | 'basic';
    cookieTrackingEnabled: boolean;
    emailTrackingEnabled: boolean;
  };
  mailpoet_display_detailed_stats: boolean;
  mailpoet_premium_plugin_installed: boolean;
  mailpoet_premium_plugin_download_url: string;
  mailpoet_premium_plugin_activation_url: string;
  mailpoet_plugin_partial_key: string;
  mailpoet_email_volume_limit: string;
  mailpoet_email_volume_limit_reached: boolean;
  mailpoet_current_wp_user_email: string;
  mailpoet_current_time?: string;
  mailpoet_current_date?: string;
  mailpoet_tomorrow_date?: string;
  mailpoet_schedule_time_of_day?: string;
  mailpoet_date_display_format?: string;
  mailpoet_date_storage_format?: string;
  mailpoet_current_date_time?: string;
  mailpoet_urls: Record<string, string>;
  recaptcha?: unknown;
  grecaptcha?: any;
  MailPoetForm?: {
    ajax_url: string;
  };
  mailpoet_authorized_emails?: string[];
  mailpoet_verified_sender_domains?: string[];
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
  sender_data: { name: string; address: string };
  finish_wizard_url: string;
  admin_email: string;
  wizard_sender_illustration_url: string;
  wizard_tracking_illustration_url: string;
  wizard_MSS_pitch_illustration_url: string;
  wizard_woocommerce_illustration_url: string;
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
  mailpoet_homepage_data: {
    task_list_dismissed: boolean;
    task_list_status: {
      senderSet: boolean;
      mssConnected: boolean;
      subscribersAdded: boolean;
      wooSubscribersImported: boolean;
    };
    woo_customers_count: number;
    subscribers_count: number;
  };
}
