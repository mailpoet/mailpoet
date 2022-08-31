import { __ } from '@wordpress/i18n';
import { wordpress } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';

export const step: StepType = {
  key: 'mailpoet:wp-user-registered',
  group: 'triggers',
  title: __('WordPress user registered', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: __(
    'Starts the automation when a new user registered in WordPress. Use trigger filters to filter by a specific user role.',
    'mailpoet',
  ),
  subtitle: () => __('Trigger', 'mailpoet'),
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.12' }}>
      {wordpress}
    </div>
  ),
  edit: () => <div />,
} as const;
