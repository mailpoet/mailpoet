/**
 * The types in this file document the expected return types of specific
 * filters.
 */
import { Step } from '../../../editor/components/automation/types';

// mailpoet.automation.send_email.create_step
export type SendEmailCreateStepType = (
  step: Step,
  automationId: number,
) => Step;

// mailpoet.automation.send_email.google_analytics_panel
export type GoogleAnalyticsPanelBodyType = JSX.Element;
