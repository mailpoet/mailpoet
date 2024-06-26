import { __ } from '@wordpress/i18n';
import { RunData } from '../types';

const year = new Date().getFullYear();
const month = new Date().getMonth();
const datePrefix = `${year}-${(month + 1).toString().padStart(2, '0')}`;

const emptyAvatarUrl =
  'https://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50?s=40&d=mp&r=g&f=y';

const subjects = {
  // translators: a sample abandoned cart email subject
  abandonedCart1: __('Did you forget something?', 'mailpoet'),

  // translators: a sample abandoned cart email subject
  abandonedCart2: __('Last chance', 'mailpoet'),
} as const;

export const runLogs: RunData = {
  run: {
    id: 1,
    automation_id: 1,
    status: 'failed',
  },
  logs: [
    {
      id: 17,
      automation_run_id: 8,
      step_id: '1',
      step_type: 'trigger',
      step_key: 'woocommerce:abandoned-cart',
      status: 'complete',
      started_at: `${datePrefix}-26T14:22:02.000Z`,
      updated_at: `${datePrefix}-26T14:22:02.000Z`,
      run_number: 1,
      data: '{}',
      error: null,
    },
    {
      id: 20,
      automation_run_id: 8,
      step_id: '2',
      step_type: 'action',
      step_key: 'mailpoet:send-email',
      status: 'complete',
      started_at: `${datePrefix}-26T14:22:02.000Z`,
      updated_at: `${datePrefix}-26T14:22:02.000Z`,
      run_number: 2,
      data: '{}',
      error: null,
    },
    {
      id: 22,
      automation_run_id: 8,
      step_id: '3',
      step_type: 'action',
      step_key: 'core:delay',
      status: 'complete',
      started_at: `${datePrefix}-26T14:22:02.000Z`,
      updated_at: `${datePrefix}-26T14:22:02.000Z`,
      run_number: 2,
      data: '{}',
      error: null,
    },
    {
      id: 39,
      automation_run_id: 8,
      step_id: '4',
      step_type: 'action',
      step_key: 'core:if-else',
      status: 'complete',
      started_at: `${datePrefix}-26T14:22:02.000Z`,
      updated_at: `${datePrefix}-26T14:22:02.000Z`,
      run_number: 1,
      data: '{}',
      error: null,
    },
    {
      id: 40,
      automation_run_id: 8,
      step_id: '5',
      step_type: 'action',
      step_key: 'mailpoet:send-email',
      status: 'failed',
      started_at: `${datePrefix}-26T14:22:02.000Z`,
      updated_at: `${datePrefix}-26T14:22:02.000Z`,
      run_number: 1,
      data: '{}',
      error: null,
    },
  ],
  steps: {
    root: {
      id: 'root',
      type: 'root',
      key: 'core:root',
      args: {},
      next_steps: [],
      filters: null,
    },
    1: {
      id: '1',
      type: 'trigger',
      key: 'woocommerce:abandoned-cart',
      args: {
        wait: 30,
      },
      next_steps: [
        {
          id: '2',
        },
      ],
      filters: null,
    },
    2: {
      id: '2',
      type: 'action',
      key: 'mailpoet:send-email',
      args: { subject: subjects.abandonedCart1 },
      next_steps: [
        {
          id: '3',
        },
      ],
      filters: null,
    },
    3: {
      id: '3',
      type: 'action',
      key: 'core:delay',
      args: {
        delay_type: 'MINUTES',
        delay: 1,
      },
      next_steps: [
        {
          id: '4',
        },
      ],
      filters: null,
    },
    4: {
      id: '4',
      type: 'action',
      key: 'core:if-else',
      args: {},
      next_steps: [
        {
          id: '5',
        },
        {
          id: null,
        },
      ],
      filters: {
        operator: 'and',
        groups: [
          {
            id: '5usblnahw3ggo8s4',
            operator: 'and',
            filters: [
              {
                id: '1lz6yw8xuzk0sw88',
                field_type: 'integer',
                field_key: 'woocommerce:customer:order-count',
                condition: 'equals',
                args: {
                  value: 0,
                  params: {
                    in_the_last: {
                      number: 1,
                      unit: 'days',
                    },
                  },
                },
              },
            ],
          },
        ],
      },
    },
    5: {
      id: '5',
      type: 'action',
      key: 'mailpoet:send-email',
      args: { subject: subjects.abandonedCart2 },
      next_steps: [],
      filters: null,
    },
  },
  subscriber: {
    id: 3,
    email: 'elainelu@email.com',
    first_name: null,
    last_name: null,
    avatar: emptyAvatarUrl,
  },
};
