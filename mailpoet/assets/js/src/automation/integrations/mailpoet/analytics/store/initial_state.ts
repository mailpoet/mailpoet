import { __ } from '@wordpress/i18n';
import { AutomationAnalyticsWindow, State } from './types';

declare let window: AutomationAnalyticsWindow;

export const getInitialState = (): State => ({
  automation: window.mailpoet_automation,
  sections: {
    overview: {
      id: 'overview',
      name: __('Overview', 'mailpoet'),
      data: undefined,
      withPreviousData: true,
      endpoint: '/automation/analytics/overview',
    },
    orders: {
      id: 'orders',
      name: __('Orders', 'mailpoet'),
      data: undefined,
      withPreviousData: false,
      endpoint: '/automation/analytics/orders',
    },
  },
  query: {
    compare: 'previous_period',
    period: 'quarter',
    after: undefined,
    before: undefined,
  },
});
