import { Hooks } from 'wp-js-hooks';
import { AutomationSettingElements } from '../../../types/filters';
import { RunAutomationOnce } from './run_automation_once';

export function registerAutomationSidebar() {
  Hooks.addFilter(
    'mailpoet.automation.settings.render',
    'mailpoet',
    (elements: AutomationSettingElements): AutomationSettingElements => ({
      ...elements,
      run_automation_once: <RunAutomationOnce />,
    }),
  );
}
