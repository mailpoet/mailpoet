/**
 * The types in this file document the expected return types of specific
 * filters.
 */
import { State } from '../../../editor/store';
import { Step } from '../../../editor/components/workflow/types';

// mailpoet.automation.send_email.create_step
export type SendEmailCreateStepType = (step: Step, state: State) => Step;

// mailpoet.automation.send_email.google_analytics_panel
export type GoogleAnalyticsPanelBodyType = JSX.Element;
