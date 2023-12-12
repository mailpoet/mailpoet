import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Section, State } from './types';
import { storeName as editorStoreName } from '../../../../editor/store';
import { MailPoet } from '../../../../../mailpoet';

const sections: Record<string, Section> = {
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
      void dispatch(editorStoreName).updateAutomation(automation);
    },
  },
  overview: {
    id: 'overview',
    name: __('Overview', 'mailpoet'),
    data: undefined,
    withPreviousData: true,
    endpoint: '/automation/analytics/overview',
  },
  subscribers: {
    id: 'subscribers',
    name: __('Subscribers', 'mailpoet'),
    data: undefined,
    currentView: {
      search: '',
      filters: {
        step: [],
        status: [],
      },
    },
    customQuery: {
      order: 'asc',
      order_by: 'updated_at',
      limit: 25,
      page: 1,
      filter: undefined,
      search: undefined,
    },
    withPreviousData: false,
    endpoint: '/automation/analytics/subscribers',
  },
};

if (MailPoet.isWoocommerceActive) {
  sections.orders = {
    id: 'orders',
    name: __('Orders', 'mailpoet'),
    data: undefined,
    currentView: {
      filters: {
        emails: [],
      },
    },
    customQuery: {
      order: 'asc',
      order_by: 'created_at',
      limit: 25,
      page: 1,
      filter: undefined,
      search: undefined,
    },
    withPreviousData: false,
    endpoint: '/automation/analytics/orders',
  };
}

export const getInitialState = (): State => ({
  sections,
  query: {
    compare: 'previous_period',
    period: 'quarter',
    after: undefined,
    before: undefined,
  },
});
