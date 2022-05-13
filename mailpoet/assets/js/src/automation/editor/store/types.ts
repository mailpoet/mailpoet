import { Item } from '../components/inserter/item';
import { Step, Workflow } from '../components/workflow/types';

export interface AutomationEditorWindow extends Window {
  mailpoet_automation_workflow: Workflow;
}

export type State = {
  workflowData: Workflow;
  selectedStep: Step | undefined;
  inserter: {
    actionSteps: Item[];
    logicalSteps: Item[];
  };
  inserterSidebar: {
    isOpened: boolean;
  };
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
