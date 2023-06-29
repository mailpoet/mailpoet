/**
 * The types in this file document the expected return types of specific
 * filters.
 */

import { ComponentType, Dispatch, ReactNode, SetStateAction } from 'react';
import { DropdownMenu } from '@wordpress/components';
import { Item } from '../editor/components/inserter/item';
import { Filter, Step } from '../editor/components/automation/types';
import { EditorStoreConfig } from '../editor/store';

interface ControlWithSetIsBusy extends Omit<DropdownMenu.Control, 'onClick'> {
  onClick: (setIsBusy?: Dispatch<SetStateAction<boolean>>) => void;
}
export type MoreControlType = {
  key: string;
  control: ControlWithSetIsBusy;
  slot: () => JSX.Element | undefined;
};

/**
 * APPLICATION HOOKS
 */

// mailpoet.automation.step.more-controls
// mailpoet.automation.hero.actions
export type StepMoreControlsType = Record<string, MoreControlType>;

// mailpoet.automation.add_step_callback
export type AddStepCallbackType = (item?: Item) => void;

// mailpoet.automation.render_step
export type RenderStepType = (step: Step) => JSX.Element;

// mailpoet.automation.step.footer
export type RenderStepFooterType = JSX.Element | null;

// mailpoet.automation.render_step_separator
export type RenderStepSeparatorType = (step: Step) => JSX.Element;

// mailpoet.automation.editor.create_store
export type EditorStoreConfigType = EditorStoreConfig;

// mailpoet.automation.templates.from_scratch_button
export type FromScratchHookType = (errorHandler: Dispatch<string>) => void;

// mailpoet.automation.settings.render
export type AutomationSettingElements = Record<string, JSX.Element>;

// mailpoet.automation.trigger.order_status_changed.order_status_options
export type OrderStatusOptions = Map<
  string,
  {
    value: string;
    label: string;
    isDisabled: boolean;
  }
>;

// mailpoet.automation.filters.panel.content
export type FiltersPanelContentType = (step: Step) => JSX.Element;

// mailpoet.automation.filters.group_operator_change_callback
export type FilterGroupOperatorChangeType = (
  stepId: string,
  groupId: string,
  operator: 'and' | 'or',
) => void;

// mailpoet.automation.filters.filter_wrapper
export type FilterWrapperType = ComponentType<{
  step: Step;
  filter: Filter;
  editable: boolean;
  children: ReactNode;
}>;
