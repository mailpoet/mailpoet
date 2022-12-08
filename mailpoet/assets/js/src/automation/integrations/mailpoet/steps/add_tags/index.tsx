import { __ } from '@wordpress/i18n';
import { tag } from '@wordpress/icons';
import { StepType } from '../../../../editor/store';

export const step: StepType = {
  key: 'mailpoet:add-tag',
  group: 'actions',
  title: __('Add tag', 'mailpoet'),
  description: __('Add a tag or multiple tags to a subscriber.', 'mailpoet'),
  subtitle: () =>
    __(
      'The premium plugin and your premium license need to be active for this action to work.',
      'mailpoet',
    ),
  foreground: '#00A32A',
  background: '#EDFAEF',
  icon: () => (
    <div style={{ width: '100%', height: '100%', scale: '1.4' }}>{tag}</div>
  ),
  edit: () => null,
} as const;
