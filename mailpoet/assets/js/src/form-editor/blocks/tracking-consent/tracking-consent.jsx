import { MailPoet } from 'mailpoet';
import { Icon } from './icon.jsx';
import { TrackingConsentEdit } from './edit.jsx';

export const name = 'mailpoet-form/tracking-consent';

export const settings = {
  title: MailPoet.I18n.t('blockTrackingConsent'),
  description: MailPoet.I18n.t('blockTrackingConsentDescription'),
  icon: Icon,
  category: 'fields',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockTrackingConsent'),
    },
    consentText: {
      type: 'string',
      default: MailPoet.I18n.t('blockTrackingConsentDefaultText'),
    },
  },
  supports: {
    html: false,
    multiple: false,
  },
  edit: TrackingConsentEdit,
  save() {
    return null;
  },
};
