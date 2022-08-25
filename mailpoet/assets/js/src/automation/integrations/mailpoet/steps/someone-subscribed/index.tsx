import { __ } from '@wordpress/i18n';
import { commentAuthorAvatar } from '@wordpress/icons';
import { StepType } from '../../../../editor/store/types';

export const step: StepType = {
  key: 'mailpoet:segment:subscribed',
  group: 'triggers',
  title: __('Someone subscribed', 'mailpoet'),
  foreground: '#2271b1',
  background: '#f0f6fc',
  description: __(
    'Starts the automation when a new subscriber is added to MailPoet. Use trigger filters to filter by a specific list.',
    'mailpoet',
  ),
  subtitle: () => __('Trigger', 'mailpoet'),
  icon: () => commentAuthorAvatar,
  edit: () => <div />,
} as const;
