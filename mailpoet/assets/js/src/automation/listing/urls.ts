import { addQueryArgs } from '@wordpress/url';
import { MailPoet } from '../../mailpoet';
import type { AutomationItem } from './store/types';

export function getAutomationEditorUrl(automation: AutomationItem): string {
  return automation.isLegacy
    ? `?page=mailpoet-newsletter-editor&id=${automation.id}`
    : addQueryArgs(MailPoet.urls.automationEditor, { id: automation.id });
}

export function getAutomationAnalyticsUrl(automation: AutomationItem): string {
  return automation.isLegacy
    ? `?page=mailpoet-newsletters&context=automation#/stats/${automation.id}`
    : addQueryArgs(MailPoet.urls.automationAnalytics, { id: automation.id });
}
