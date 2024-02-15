import { AutomationEditorWindow, State } from './types';

declare let window: AutomationEditorWindow;

export const getInitialState = (): State => ({
  savedState: 'saved',
  registry: { ...window.mailpoet_automation_registry },
  context: { ...window.mailpoet_automation_context },
  stepTypes: {},
  filterTypes: {},
  automationData: { ...window.mailpoet_automation },
  selectedStep: undefined,
  inserterSidebar: {
    isOpened: false,
  },
  activationPanel: {
    isOpened: false,
  },
  inserterPopover: undefined,
  errors: undefined,
});
