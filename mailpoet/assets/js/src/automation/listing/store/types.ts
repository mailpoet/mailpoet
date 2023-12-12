import { Automation } from '../automation';

export type AutomationItem = Automation & {
  isLegacy?: boolean;
};

export type State = {
  automations?: AutomationItem[];
  legacyAutomations?: AutomationItem[];
};
