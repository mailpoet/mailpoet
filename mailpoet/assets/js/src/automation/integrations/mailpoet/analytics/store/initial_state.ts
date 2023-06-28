import { __ } from '@wordpress/i18n';
import { State } from './types';

export const getInitialState = (): State => ({
  sections: {
    overview: {
      id: 'overview',
      name: __('Overview', 'mailpoet'),
      data: undefined,
      customQuery: undefined,
      withPreviousData: true,
      endpoint: '/automation/analytics/overview',
    },
    orders: {
      id: 'orders',
      name: __('Orders', 'mailpoet'),
      data: undefined,
      customQuery: {
        order: 'asc',
        order_by: 'created_at',
        limit: 25,
        page: 1,
      },
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
