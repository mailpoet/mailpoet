import { __ } from '@wordpress/i18n';
import { SubscriberSection } from '../types';

const year = new Date().getFullYear();
const month = new Date().getMonth();
const datePrefix = `${year}-${(month + 1).toString().padStart(2, '0')}`;

const emptyAvatarUrl =
  'https://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50?s=40&d=mp&r=g&f=y';

const subjects = {
  // translators: a sample abandoned cart email subject
  abandonedCart: __('Did you forget something?', 'mailpoet'),

  // translators: a sample email subject
  holidaySale: __('Holiday Sale!', 'mailpoet'),
} as const;

export const subscribers: SubscriberSection['data'] = {
  isSample: true,
  results: 4,
  items: [
    {
      date: `${datePrefix}-26T14:22:02.000Z`,
      subscriber: {
        id: 1,
        email: 'kathlin.nelson@email.com',
        first_name: 'Kathlin',
        last_name: 'Nelson',
        avatar: emptyAvatarUrl,
      },
      run: {
        id: 1,
        status: 'complete',
        step: { id: 'send-email', name: __('Send email', 'mailpoet') },
      },
    },
    {
      date: `${datePrefix}-26T14:22:02.000Z`,
      subscriber: {
        id: 2,
        email: 'eric.borgol@email.com',
        first_name: 'Eric',
        last_name: 'Borgol',
        avatar: emptyAvatarUrl,
      },
      run: {
        id: 2,
        status: 'running',
        step: { id: 'delay', name: __('Delay', 'mailpoet') },
      },
    },
    {
      date: `${datePrefix}-26T14:22:02.000Z`,
      subscriber: {
        id: 3,
        email: 'elainelu@email.com',
        first_name: null,
        last_name: null,
        avatar: emptyAvatarUrl,
      },
      run: {
        id: 3,
        status: 'failed',
        step: { id: 'send-email', name: __('Send email', 'mailpoet') },
      },
    },
    {
      date: `${datePrefix}-26T14:22:02.000Z`,
      subscriber: {
        id: 4,
        email: 'brian.nelson@email.com',
        first_name: 'Brian',
        last_name: 'Norman',
        avatar: emptyAvatarUrl,
      },
      run: {
        id: 4,
        status: 'complete',
        step: {
          id: 'update-subscriber',
          name: __('Update subscriber', 'mailpoet'),
        },
      },
    },
  ],
  steps: {
    'send-email': {
      id: 'send-email',
      type: 'action',
      key: 'mailpoet:send-email',
      args: { subject: subjects.abandonedCart },
      next_steps: [],
    },
    delay: {
      id: 'delay',
      type: 'action',
      key: 'core:delay',
      args: { delay: 2, delay_type: 'WEEKS' },
      next_steps: [],
    },
    'update-subscriber': {
      id: 'update-subscriber',
      type: 'action',
      key: 'mailpoet:update-subscriber',
      args: {},
      next_steps: [],
    },
  },
};
