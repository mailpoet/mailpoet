import MailPoet from 'mailpoet';
import { select } from '@wordpress/data';
import { STORE_NAME } from '.';

export { default as CALL_API } from 'common/controls/call_api';

export function TRACK_SETTINGS_SAVED() {
  const settings = select(STORE_NAME).getSettings();
  const data = {
    'MailPoet Free version': MailPoet.version,
    'Sending method type': settings.mta_group || null,
    'Sending frequency (emails)': (
      settings.mta_group !== 'mailpoet'
      && settings.mta
      && settings.mta.frequency
      && settings.mta.frequency.emails
    ),
    'Sending frequency (interval)': (
      settings.mta_group !== 'mailpoet'
      && settings.mta
      && settings.mta.frequency
      && settings.mta.frequency.interval
    ),
    'Sending provider': settings.mta_group === 'smtp' && settings.smtp_provider,
    'Sign-up confirmation enabled': settings.signup_confirmation && settings.signup_confirmation.enabled,
    'Bounce email is present': settings.bounce && settings.bounce.address !== '',
    'Newsletter task scheduler method': (settings.cron_trigger && settings.cron_trigger.method),
  };
  if (MailPoet.isWoocommerceActive) {
    data['WooCommerce email customizer enabled'] = settings.woocommerce && settings.woocommerce.use_mailpoet_editor;
  }
  MailPoet.trackEvent('User has saved Settings', data);
}

export function TRACK_REINSTALLED() {
  MailPoet.trackEvent(
    'User has reinstalled MailPoet via Settings',
    { 'MailPoet Free version': MailPoet.version }
  );
}
