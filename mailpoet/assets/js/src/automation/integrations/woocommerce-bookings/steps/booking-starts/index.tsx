import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Icon } from './icon';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';

const keywords = [
  // translators: noun, used as a search keyword for "Booking starts" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Booking starts" trigger
  __('bookings', 'mailpoet'),
  // translators: verb, used as a search keyword for "Booking starts" trigger
  __('start', 'mailpoet'),
  // translators: noun, used as a search keyword for "Booking starts" trigger
  __('schedule', 'mailpoet'),
];

export const step: StepType = {
  key: 'woocommerce-bookings:booking-starts',
  group: 'triggers',
  title: () => __('Booking starts', 'mailpoet'),
  description: () =>
    __('Starts before or after a booking is scheduled to begin.', 'mailpoet'),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_booking_starts',
      }}
    >
      {__(
        'Starting an automation before or after a booking begins is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
