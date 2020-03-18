import FeaturesController from 'features_controller';
import MailPoetComUrlFactory from 'mailpoet_com_url_factory';

// A placeholder for MailPoet object
var MailPoet = {
  FeaturesController: FeaturesController(window.mailpoet_feature_flags),
  MailPoetComUrlFactory: MailPoetComUrlFactory(window.mailpoet_referral_id),
  version: window.mailpoet_version,
  isWoocommerceActive: window.mailpoet_woocommerce_active,
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
