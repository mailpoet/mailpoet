import { Item } from '../components/inserter/item';
import { Step, Workflow } from '../components/workflow/types';

export interface AutomationEditorWindow extends Window {
  mailpoet_automation_workflow: Workflow;
}

export type StepType = {
  key: string;
  title: string;
  description: string;
};

export type State = {
  stepTypes: Record<string, StepType>;
  workflowData: Workflow;
  selectedStep: Step | undefined;
  inserter: {
    actionSteps: Item[];
    logicalSteps: Item[];
  };
  inserterSidebar: {
    isOpened: boolean;
  };
  inserterPopover: {
    anchor?: HTMLElement;
  };
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
