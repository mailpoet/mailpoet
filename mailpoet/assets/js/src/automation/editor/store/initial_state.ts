import { AutomationEditorWindow, State } from './types';

declare let window: AutomationEditorWindow;

export const getInitialState = (): State => ({
  context: { ...window.mailpoet_automation_context },
  stepTypes: {},
  workflowData: { ...window.mailpoet_automation_workflow },
  workflowSaved: true,
  selectedStep: undefined,
  inserterSidebar: {
    isOpened: false,
  },
  inserterPopover: undefined,
  errors: undefined,
});
