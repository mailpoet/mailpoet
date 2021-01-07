import FeaturesController from 'features_controller';
import MailPoetComUrlFactory from 'mailpoet_com_url_factory';

// A placeholder for MailPoet object
var MailPoet = {
  FeaturesController: FeaturesController(window.mailpoet_feature_flags),
  MailPoetComUrlFactory: MailPoetComUrlFactory(window.mailpoet_referral_id),
  version: window.mailpoet_version,
  premiumVersion: window.mailpoet_premium_version,
  premiumLink: window.mailpoet_premium_link,
  isWoocommerceActive: window.mailpoet_woocommerce_active,
  premiumActive: window.mailpoet_premium_active,
  subscribersLimit: window.mailpoet_subscribers_limit,
  subscribersLimitReached: window.mailpoet_subscribers_limit_reached,
  subscribersCountTowardsLimit: window.mailpoet_premium_subscribers_count,
  subscribersCount: window.mailpoet_subscribers_in_plan_count,
  hasPremiumSupport: window.mailpoet_has_premium_support,
  hasValidApiKey: window.mailpoet_has_valid_api_key,
  listingPerPage: window.mailpoet_listing_per_page,
  libs3rdPartyEnabled: window.mailpoet_3rd_party_libs_enabled,
  apiVersion: window.mailpoet_api_version,
  emailRegex: window.mailpoet_email_regex,
  getShortcodeLinks: () => (window.mailpoet_shortcode_links ? window.mailpoet_shortcode_links : []),
};

// Expose MailPoet globally
window.MailPoet = MailPoet;

export default MailPoet;

require('ajax'); // side effect - extends MailPoet object
require('date'); // side effect - extends MailPoet object
require('i18n'); // side effect - extends MailPoet object
require('modal'); // side effect - extends MailPoet object
require('notice'); // side effect - extends MailPoet object
require('num'); // side effect - extends MailPoet object
require('analytics_event'); // side effect - extends MailPoet object
require('help-tooltip'); // side effect - extends MailPoet object
