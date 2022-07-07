import {
  atSymbol,
  backup,
  code,
  commentAuthorAvatar,
  commentEditLink,
  flipHorizontal,
  share,
  tag,
} from '@wordpress/icons';
import { AutomationEditorWindow, State } from './types';
import { Item } from '../components/inserter/item';

declare let window: AutomationEditorWindow;

// mocked data
const actionSteps: Item[] = [
  {
    id: 'mailpoet/automation-send-email',
    title: 'Send email',
    icon: atSymbol,
    description: 'Send an email.',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-update-contact',
    title: 'Update contact',
    icon: commentEditLink,
    description: 'Update contact information.',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-webhook',
    title: 'Webhook',
    icon: code,
    description: 'Trigger a webhook.',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-tag-untag',
    title: 'Tag/Untag',
    icon: tag,
    description: 'Add or remove tag',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-unsubscribe',
    title: 'Unsubscribe',
    icon: commentAuthorAvatar,
    description: 'Unsubscribe MailPoet subscriber.',
    isDisabled: false,
  },
];

// mocked data
const logicalSteps: Item[] = [
  {
    id: 'mailpoet/automation-delay',
    title: 'Delay',
    icon: backup,
    description: 'Add a delay.',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-if-else',
    title: 'If/Else',
    icon: share,
    description: 'Execute a conditional statement.',
    isDisabled: false,
  },
  {
    id: 'mailpoet/automation-a-b-test',
    title: 'A/B split test',
    icon: flipHorizontal,
    description: 'Run an A/B test.',
    isDisabled: false,
  },
];

export const initialState: State = {
  stepTypes: {},
  workflowData: { ...window.mailpoet_automation_workflow },
  selectedStep: undefined,
  inserter: {
    actionSteps,
    logicalSteps,
  },
  inserterSidebar: {
    isOpened: false,
  },
  inserterPopover: {
    anchor: undefined,
  },
};
