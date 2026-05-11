import { __ } from '@wordpress/i18n';
import { megaphone } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { StepType } from '../../../../editor/store';
import { Item } from '../../../../editor/components/inserter/item';
import { Group } from '../../../../editor/components/inserter/group';
import { Automation } from '../../../../editor/components/automation/types';
import { AutomationEditorWindow } from '../../../../editor/store/types';
import { Edit } from './edit';
import {
  disableSendLatestNewsletterWhenMissingTriggerList,
  sendLatestNewsletterStepKey,
} from './helper';

const disabledReason = __(
  'This action needs a trigger list, such as "Someone subscribes".',
  'mailpoet',
);

const keywords = [
  // translators: noun, used as a search keyword for "Send latest newsletter" automation action
  __('newsletter', 'mailpoet'),
  // translators: adjective, used as a search keyword for "Send latest newsletter" automation action
  __('latest', 'mailpoet'),
  // translators: noun, used as a search keyword for "Send latest newsletter" automation action
  __('subscriber', 'mailpoet'),
  // translators: noun, used as a search keyword for "Send latest newsletter" automation action
  __('list', 'mailpoet'),
  // translators: noun, used as a search keyword for "Send latest newsletter" automation action
  __('welcome', 'mailpoet'),
];

export function registerHooks(): void {
  Hooks.addFilter(
    'mailpoet.automation.inserter.items',
    'mailpoet',
    (items: Item[], _groupType: Group['type'], automation: Automation) =>
      disableSendLatestNewsletterWhenMissingTriggerList(
        items,
        automation,
        (window as unknown as AutomationEditorWindow)
          .mailpoet_automation_registry,
        disabledReason,
      ),
  );
}

export const step: StepType = {
  key: sendLatestNewsletterStepKey,
  group: 'actions',
  // translators: automation action title
  title: () => __('Send latest newsletter', 'mailpoet'),
  description: () =>
    __(
      'Send the latest regular newsletter from the trigger list when this automation runs.',
      'mailpoet',
    ),
  subtitle: () => __('Latest newsletter from trigger list', 'mailpoet'),
  keywords,
  foreground: '#996800',
  background: '#FCF9E8',
  icon: () => megaphone,
  edit: Edit,
  createStep: (stepData) => ({
    ...stepData,
    args: {},
  }),
} as const;
