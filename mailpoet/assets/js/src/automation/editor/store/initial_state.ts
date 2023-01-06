import { AutomationEditorWindow, State } from './types';

declare let window: AutomationEditorWindow;

export const getInitialState = (): State => ({
  registry: { ...window.mailpoet_automation_registry },
  context: { ...window.mailpoet_automation_context },
  stepTypes: {},
  automationData: { ...window.mailpoet_automation },
  automationSaved: true,
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
