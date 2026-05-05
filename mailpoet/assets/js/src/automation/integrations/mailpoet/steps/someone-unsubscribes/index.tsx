import { __, _x } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { StepType } from '../../../../editor/store';
import { Item } from '../../../../editor/components/inserter/item';
import { Group } from '../../../../editor/components/inserter/group';
import { Automation } from '../../../../editor/components/automation/types';
import { Icon } from './icon';

const keywords = [
  // translators: verb, used as a search keyword for "Someone unsubscribes" trigger
  __('unsubscribe', 'mailpoet'),
  // translators: used as a search keyword for "Someone unsubscribes" trigger
  __('opt out', 'mailpoet'),
];

export const stepKey = 'mailpoet:someone-unsubscribes';
const sendEmailKey = 'mailpoet:send-email';

export function registerHooks(): void {
  Hooks.addFilter(
    'mailpoet.automation.inserter.items',
    'mailpoet',
    (items: Item[], _groupType: Group['type'], automation: Automation) => {
      const hasUnsubscribeTrigger = Object.values(automation.steps).some(
        (step) => step.key === stepKey,
      );
      if (!hasUnsubscribeTrigger) {
        return items;
      }
      const disabledReason = __(
        'The "Send email" action cannot be used together with the "Email subscriber unsubscribed" trigger.',
        'mailpoet',
      );
      return items.map((item) =>
        item.key === sendEmailKey
          ? { ...item, isDisabled: true, disabledReason }
          : item,
      );
    },
  );
}

export const step: StepType = {
  key: stepKey,
  group: 'triggers',
  // translators: automation trigger title
  title: () => __('Email subscriber unsubscribed', 'mailpoet'),
  description: () =>
    __(
      'Starts when a subscriber unsubscribes from your email list.',
      'mailpoet',
    ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => null,
} as const;
