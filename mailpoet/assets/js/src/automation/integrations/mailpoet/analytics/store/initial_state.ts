import { AutomationAnalyticsWindow, State } from './types';

declare let window: AutomationAnalyticsWindow;

export const getInitialState = (): State => ({
  automation: window.mailpoet_automation,
  sections: {
    overview: {
      id: 'overview',
      name: 'Overview',
      data: undefined,
      endpoint: '/automation/analytics/overview',
    },
  },
  query: {
    compare: 'previous_period',
    period: 'quarter',
    after: undefined,
    before: undefined,
  },
});
