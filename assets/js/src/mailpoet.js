import FeaturesController from 'features_controller';
import MailPoetUrlFactory from 'mailpoet_url_factory';

// A placeholder for MailPoet object
var MailPoet = {
  FeaturesController: FeaturesController(window.mailpoet_feature_flags),
  MailPoetUrlFactory: MailPoetUrlFactory(window.mailpoet_referral_id),
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
