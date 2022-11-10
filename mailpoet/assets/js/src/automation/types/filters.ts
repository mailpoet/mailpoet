/**
 * The types in this file document the expected return types of specific
 * filters.
 */

import { Dispatch } from 'react';
import { DropdownMenu } from '@wordpress/components';
import { StoreConfig } from '@wordpress/data';
import { Item } from '../editor/components/inserter/item';
import { Step } from '../editor/components/automation/types';
import { State } from '../editor/store/types';

export type MoreControlType = {
  key: string;
  control: DropdownMenu.Control;
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

// mailpoet.automation.render_step_separator
export type RenderStepSeparatorType = (step: Step) => JSX.Element;

// mailpoet.automation.editor.create_store
export type EditorStoreConfigType = StoreConfig<State>;

// mailpoet.automation.templates.from_scratch_button
export type FromScratchHookType = (errorHandler: Dispatch<string>) => void;
