import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Booking Created" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Booking Created" trigger
  __('bookings', 'mailpoet'),
  // translators: verb, used as a search keyword for "Woo Booking Created" trigger
  __('created', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Booking Created" trigger
  __('new', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-bookings:booking-created',
  group: 'triggers',
  title: () => __('Booking Created', 'mailpoet'),
  description: () =>
    __(
      'This trigger fires when a new booking is created. This includes bookings initiated by shoppers on store front end and manually created by admin users. This trigger doesn’t fire for “in-cart” bookings and a valid customer is needed to trigger this automation.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_booking_created',
      }}
    >
      {__(
        'Starting an automation when a booking is created is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
