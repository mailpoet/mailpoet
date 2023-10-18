import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';
import { Icon } from './icon';

const keywords = [__('comment', 'mailpoet')];
export const step: StepType = {
  key: 'wordpress:made-a-comment',
  group: 'triggers',
  title: () => __('User makes a comment', 'mailpoet'),
  description: () =>
    __('Start the automation when a user makes a comment.', 'mailpoet'),

  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_made_a_comment',
      }}
    >
      {__(
        'Starting an automation by creating a comment is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
