import { Hooks } from 'wp-js-hooks';
import { AutomationSettingElements } from '../../../types/filters';
import { RunAutomationOnce, showRunOnlyOnce } from './run_automation_once';

export function registerAutomationSidebar() {
  Hooks.addFilter(
    'mailpoet.automation.settings.render',
    'mailpoet',
    (elements: AutomationSettingElements): AutomationSettingElements => {
      if (!showRunOnlyOnce()) {
        return elements;
      }
      return {
        ...elements,
        run_automation_once: <RunAutomationOnce />,
      };
    },
  );
}
