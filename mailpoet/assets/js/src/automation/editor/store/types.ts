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
  subtitle: (step: Step) => JSX.Element | string;
  icon: JSX.Element;
  edit: ComponentType;
  foreground: string;
  background: string;
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
