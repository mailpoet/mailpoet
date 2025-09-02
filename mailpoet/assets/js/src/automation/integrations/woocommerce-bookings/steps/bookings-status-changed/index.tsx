import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Woo Booking status changed" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Booking status changed" trigger
  __('bookings', 'mailpoet'),
  // translators: noun, used as a search keyword for "Woo Booking status changed" trigger
  __('status', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Woo Booking status changed" trigger
  __('changed', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-bookings:booking-status-changed',
  group: 'triggers',
  title: () => __('Woo Booking status changed', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when booking changed to a specific status.',
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
        utm_campaign: 'create_automation_editor_booking_status_changed',
      }}
    >
      {__(
        'Starting an automation by changing the status of a booking is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
