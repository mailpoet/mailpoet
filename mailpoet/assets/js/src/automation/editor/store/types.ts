import { ComponentType } from 'react';
import { Step, Automation } from '../components/automation/types';

export interface AutomationEditorWindow extends Window {
  mailpoet_automation_registry: Registry;
  mailpoet_automation_context: Context;
  mailpoet_automation: Automation;
}

export type Registry = {
  steps: Record<
    string,
    {
      key: string;
      subject_keys: string[];
      name: string;
      args_schema: {
        type: 'object';
        properties?: Record<string, { type: string; default?: unknown }>;
      };
    }
  >;
  subjects: Record<
    string,
    {
      key: string;
      name: string;
      args_schema: {
        type: 'object';
        properties?: Record<string, { type: string; default?: unknown }>;
      };
      field_keys: string[];
    }
  >;
  fields: Record<
    string,
    {
      key: string;
      type:
        | 'boolean'
        | 'number'
        | 'integer'
        | 'string'
        | 'datetime'
        | 'enum'
        | 'enum_array';
      name: string;
      args: Record<string, unknown>;
    }
  >;
  filters: Record<
    string,
    {
      field_type: string;
      conditions: { key: string; label: string }[];
    }
  >;
};

export type Context = Record<string, unknown>;

export type StepGroup = 'actions' | 'logical' | 'triggers';

export type StepRenderContext = 'inserter' | 'automation' | 'sidebar' | 'other';

export type StepType = {
  key: string;
  group: StepGroup;
  title: (
    step: Step | null,
    context: StepRenderContext,
  ) => JSX.Element | string;
  description: (step: Step, context: StepRenderContext) => JSX.Element | string;
  subtitle: (step: Step, context: StepRenderContext) => JSX.Element | string;
  keywords: string[];
  icon: ComponentType;
  edit: ComponentType;
  footer?: ComponentType<{ step: Step }>;
  branchBadge?: ComponentType<{ step: Step; index: number }>;
  foreground: string;
  background: string;
  createStep?: (step: Step, state: State) => Step;
};

export type StepErrors = {
  step_id: string;
  message: string;
  fields: Record<string, string>;
  filters: Record<string, string>;
};

export type Errors = {
  steps: Record<string, StepErrors>;
};

export type State = {
  savedState: 'unsaved' | 'saving' | 'saved';
  registry: Registry;
  context: Context;
  stepTypes: Record<string, StepType>;
  automationData: Automation;
  selectedStep: Step | undefined;
  inserterSidebar: {
    isOpened: boolean;
  };
  activationPanel: {
    isOpened: boolean;
  };
  inserterPopover?: {
    anchor: HTMLElement;
    type: 'steps' | 'triggers';
  };
  errors?: Errors;
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
