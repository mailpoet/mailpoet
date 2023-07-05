import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { State } from './types';
import { storeName as editorStoreName } from '../../../../editor/store';

export const getInitialState = (): State => ({
  sections: {
    automation_flow: {
      id: 'automation_flow',
      name: __('Automation flow', 'mailpoet'),
      data: undefined,
      withPreviousData: false,
      endpoint: '/automation/analytics/automation_flow',
      updateCallback: (data): void => {
        if (!data || !data?.automation) {
          return;
        }
        const { automation } = data;
        dispatch(editorStoreName).updateAutomation(automation);
      },
    },
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
      customQuery: {
        order: 'asc',
        order_by: 'created_at',
        limit: 25,
        page: 1,
      },
      withPreviousData: false,
      endpoint: '/automation/analytics/orders',
    },
    subscribers: {
      id: 'subscribers',
      name: __('Subscribers', 'mailpoet'),
      data: undefined,
      customQuery: {
        order: 'asc',
        order_by: 'updated_at',
        limit: 25,
        page: 1,
      },
      withPreviousData: false,
      endpoint: '/automation/analytics/subscribers',
    },
  },
  query: {
    compare: 'previous_period',
    period: 'quarter',
    after: undefined,
    before: undefined,
  },
});
