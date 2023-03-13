import { __, _x } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

export const step: StepType = {
  key: 'mailpoet:order-status-changed',
  group: 'triggers',
  title: __('Order status changed', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: __(
    'Start the automation when an order is created or changed to a specific status.',
    'mailpoet',
  ),
  subtitle: () => _x('Trigger', 'noun', 'mailpoet'),
  icon: () => <Icon />,
  edit: () => <Edit />,
} as const;
