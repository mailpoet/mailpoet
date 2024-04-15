import { FeaturesController } from './features-controller';
import { MailPoetComUrlFactory } from './mailpoet-com-url-factory';
import { MailPoetI18n } from './i18n';
import { MailPoetDate } from './date';
import { MailPoetAjax } from './ajax';
import { MailPoetModal } from './modal';
import { MailPoetNotice } from './notice';
import {
  initializeMixpanelWhenLoaded,
  MailPoetForceTrackEvent,
  MailPoetTrackEvent,
} from './analytics-event';
import { MailPoetNum } from './num';
import { MailPoetHelpTooltip } from './help-tooltip-helper';
import { MailPoetIframe } from './iframe';

// A placeholder for MailPoet object
export const MailPoet = {
  FeaturesController: FeaturesController(window.mailpoet_feature_flags),
  MailPoetComUrlFactory: MailPoetComUrlFactory(window.mailpoet_referral_id),
  version: window.mailpoet_version,
  premiumVersion: window.mailpoet_premium_version,
  premiumLink: window.mailpoet_premium_link,
  isWoocommerceActive: window.mailpoet_woocommerce_active,
  isWoocommerceSubscriptionsActive:
    window.mailpoet_woocommerce_subscriptions_active,
  WooCommerceStoreConfig: window.mailpoet_woocommerce_store_config,
  premiumActive: window.mailpoet_premium_active,
  subscribersLimit: window.mailpoet_subscribers_limit,
  subscribersLimitReached: window.mailpoet_subscribers_limit_reached,
  subscribersCount: window.mailpoet_subscribers_count,
  hasPremiumSupport: window.mailpoet_has_premium_support,
  // The key is valid and can be authenticated by the API (but still might not have access to premium or MSS
  hasValidApiKey: window.mailpoet_has_valid_api_key,
  // The key is valid and has access to premium features
  hasValidPremiumKey: window.mailpoet_has_valid_premium_key,
  // The key is valid and has access to MSS
  hasValidMssApiKey: window.mailpoet_mss_key_valid,
  // The key is invalid and has no access to MSS
  hasInvalidMssApiKey: window.mailpoet_mss_key_invalid,
  mtaMethod: window.mailpoet_mta_method,
  mtaLog: window.mailpoet_mta_log,
  listingPerPage: window.mailpoet_listing_per_page,
  libs3rdPartyEnabled: window.mailpoet_3rd_party_libs_enabled,
  apiVersion: window.mailpoet_api_version,
  emailRegex: window.mailpoet_email_regex,
  wpSegmentState: window.mailpoet_wp_segment_state,
  wpWeekStartsOn: window.mailpoet_wp_week_starts_on,
  urls: window.mailpoet_urls,
  subscribersCountsCacheCreatedAt:
    window.mailpoet_subscribers_counts_cache_created_at,
  getShortcodeLinks: (): Record<string, string> =>
    window.mailpoet_shortcode_links ? window.mailpoet_shortcode_links : {},
  trackingConfig: window.mailpoet_tracking_config,
  I18n: MailPoetI18n,
  Date: MailPoetDate,
  Ajax: MailPoetAjax,
  Modal: MailPoetModal,
  Notice: MailPoetNotice,
  trackEvent: MailPoetTrackEvent,
  forceTrackEvent: MailPoetForceTrackEvent,
  Num: MailPoetNum,
  helpTooltip: MailPoetHelpTooltip,
  Iframe: MailPoetIframe,
  isPremiumPluginInstalled: window.mailpoet_premium_plugin_installed,
  premiumPluginDownloadUrl: window.mailpoet_premium_plugin_download_url,
  premiumPluginActivationUrl: window.mailpoet_premium_plugin_activation_url,
  pluginPartialKey: window.mailpoet_plugin_partial_key,
  emailVolumeLimit: window.mailpoet_email_volume_limit,
  emailVolumeLimitReached: window.mailpoet_email_volume_limit_reached,
  capabilities: window.mailpoet_capabilities,
  tier: window.mailpoet_tier,
  currentWpUserEmail: window.mailpoet_current_wp_user_email,
  freeMailDomains: window.mailpoet_free_domains || [],
  installedAt: window.mailpoet_installed_at,
  installedDaysAgo: window.mailpoet_installed_days_ago,
  emailEditorTutorialSeen: window.mailpoet_email_editor_tutorial_seen,
  emailEditorTutorialUrl: window.mailpoet_email_editor_tutorial_url,
  deactivateSubscriberAfterInactiveDays:
    window.mailpoet_deactivate_subscriber_after_inactive_days,
  tags: window.mailpoet_tags,
  cdnUrl: window.mailpoet_cdn_url,
  mainPageSlug: window.mailpoet_main_page_slug,
  transactionalEmailsEnabled: window.mailpoet_send_transactional_emails,
  transactionalEmailsOptInNoticeDismissed:
    window.mailpoet_transactional_emails_opt_in_notice_dismissed,
  mailFunctionEnabled: window.mailpoet_mail_function_enabled,
  corrupt_newsletters: window.corrupt_newsletters ?? [],
  adminPluginsUrl: window.mailpoet_admin_plugins_url,
  isDotcom: window.mailpoet_is_dotcom,
} as const;

declare global {
  interface Window {
    MailPoet: Partial<typeof MailPoet>;
  }
}

// Expose MailPoet globally
window.MailPoet = MailPoet;

// initializeMixpanelWhenLoaded needs to be called after window.MailPoet is defined.
initializeMixpanelWhenLoaded();
