import { FeaturesController } from './features_controller';
import { MailPoetComUrlFactory } from './mailpoet_com_url_factory';
import { MailPoetI18n } from './i18n';
import { MailPoetDate } from './date';
import { MailPoetAjax } from './ajax';
import { MailPoetModal } from './modal';
import { MailPoetNotice } from './notice';
// side effect - extends MailPoet object in initializeMixpanelWhenLoaded
import { MailPoetForceTrackEvent, MailPoetTrackEvent } from './analytics_event';
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
  premiumActive: window.mailpoet_premium_active,
  subscribersLimit: window.mailpoet_subscribers_limit,
  subscribersLimitReached: window.mailpoet_subscribers_limit_reached,
  subscribersCount: window.mailpoet_subscribers_count,
  hasPremiumSupport: window.mailpoet_has_premium_support,
  hasValidApiKey: window.mailpoet_has_valid_api_key,
  hasValidPremiumKey: window.mailpoet_has_valid_premium_key,
  hasInvalidMssApiKey: window.mailpoet_mss_key_invalid,
  mtaMethod: window.mailpoet_mta_method,
  listingPerPage: window.mailpoet_listing_per_page,
  libs3rdPartyEnabled: window.mailpoet_3rd_party_libs_enabled,
  apiVersion: window.mailpoet_api_version,
  emailRegex: window.mailpoet_email_regex,
  wpSegmentState: window.mailpoet_wp_segment_state,
  wpWeekStartsOn: window.mailpoet_wp_week_starts_on,
  urls: window.mailpoet_urls,
  subscribersCountsCacheCreatedAt:
    window.mailpoet_subscribers_counts_cache_created_at,
  getShortcodeLinks: (): string[] =>
    window.mailpoet_shortcode_links ? window.mailpoet_shortcode_links : [],
  trackingConfig: window.mailpoet_tracking_config || {},
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
  currentWpUserEmail: window.mailpoet_current_wp_user_email,
  freeMailDomains: window.mailpoet_free_domains || [],
  installedAt: window.mailpoet_installed_at,
  emailEditorTutorialSeen: window.mailpoet_email_editor_tutorial_seen,
  emailEditorTutorialUrl: window.mailpoet_email_editor_tutorial_url,
  deactivateSubscriberAfterInactiveDays:
    window.mailpoet_deactivate_subscriber_after_inactive_days,
  tags: window.mailpoet_tags,
} as const;

declare global {
  interface Window {
    MailPoet: Partial<typeof MailPoet>;
  }
}

// Expose MailPoet globally
window.MailPoet = MailPoet;
