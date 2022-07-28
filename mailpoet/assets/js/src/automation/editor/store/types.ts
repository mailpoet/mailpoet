import { ComponentType } from 'react';
import { Step, Workflow } from '../components/workflow/types';

export interface AutomationEditorWindow extends Window {
  mailpoet_automation_workflow: Workflow;
}

export type StepGroup = 'actions' | 'logical';

export type StepType = {
  key: string;
  group: StepGroup;
  title: string;
  description: string;
  subtitle: (step: Step) => string;
  icon: JSX.Element;
  edit: ComponentType;
};

export type State = {
  stepTypes: Record<string, StepType>;
  workflowData: Workflow;
  selectedStep: Step | undefined;
  inserterSidebar: {
    isOpened: boolean;
  };
  inserterPopover: {
    anchor?: HTMLElement;
  };
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
