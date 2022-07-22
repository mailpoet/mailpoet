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
  mailpoet_settings: any;
  mailpoet_tracking_config: string;
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
  recaptcha?: unknown;
  MailPoetForm?: {
    ajax_url: string;
  };
  mailpoet_authorized_emails?: string[];
  mailpoet_verified_sender_domains?: string[];
  mailpoet_all_sender_domains?: string[];
  mailpoet_mss_active: boolean;
}
