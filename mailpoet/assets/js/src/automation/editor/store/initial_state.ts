import { AutomationEditorWindow, State } from './types';

declare let window: AutomationEditorWindow;

export const initialState: State = {
  stepTypes: {},
  workflowData: { ...window.mailpoet_automation_workflow },
  workflowSaved: true,
  selectedStep: undefined,
  inserterSidebar: {
    isOpened: false,
  },
  inserterPopover: undefined,
};
