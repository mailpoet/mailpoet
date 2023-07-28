import { __ } from '@wordpress/i18n';
import { OrderSection } from '../types';

const year = new Date().getFullYear();
const month = new Date().getMonth();
const datePrefix = `${year}-${month.toString().padStart(2, '0')}`;

const emptyAvatarUrl =
  'https://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50?s=40&d=mp&r=g&f=y';

const products = {
  // translators: a sample product name
  mug: { id: 1, name: __('Mug', 'mailpoet'), quantity: 2 }, // 19.99

  // translators: a sample product name
  cup: { id: 2, name: __('Cup', 'mailpoet'), quantity: 1 }, // 14.5

  // translators: a sample product name
  socks: { id: 3, name: __('Funny socks', 'mailpoet'), quantity: 1 }, // 9.99

  // translators: a sample product name
  magnet: { id: 4, name: __('Branded magnet', 'mailpoet'), quantity: 1 }, // 3.99

  // translators: a sample product name
  pens: { id: 5, name: __('Pens 10x', 'mailpoet'), quantity: 1 }, // 7.50

  // translators: a sample product name
  bottle: { id: 6, name: __('Thermo bottle', 'mailpoet'), quantity: 1 }, // 25

  // translators: a sample product name
  subscription: { id: 7, name: __('Subscription', 'mailpoet'), quantity: 1 }, // 12.99
} as const;

const subjects = {
  // translators: a sample abandoned cart email subject
  abandonedCart: __('Did you forget something?', 'mailpoet'),

  // translators: a sample email subject
  holidaySale: __('Holiday Sale!', 'mailpoet'),
} as const;

export const orders: OrderSection['data'] = {
  isSample: true,
  results: 4,
  items: [
    {
      date: `${datePrefix}-26T14:22:02.000Z`,
      email: { id: 1, subject: subjects.abandonedCart },
      customer: {
        id: 1,
        email: 'sue.shei@email.com',
        first_name: 'Sue',
        last_name: 'Shei',
        avatar: emptyAvatarUrl,
      },
      details: {
        id: 543,
        status: { id: 'completed', name: __('Completed', 'mailpoet') },
        total: 61.46,
        products: [
          products.mug,
          products.socks,
          products.magnet,
          products.pens,
        ],
      },
    },
    {
      date: `${datePrefix}-22T07:13:11.000Z`,
      email: { id: 2, subject: subjects.holidaySale },
      customer: {
        id: 2,
        email: 'jim.sechen@email.com',
        first_name: null,
        last_name: null,
        avatar: emptyAvatarUrl,
      },
      details: {
        id: 498,
        status: {
          id: 'pending-payment',
          name: __('Pending payment', 'mailpoet'),
        },
        total: 12.99,
        products: [products.subscription],
      },
    },
    {
      date: `${datePrefix}-16T19:07:44.000Z`,
      email: { id: 1, subject: subjects.abandonedCart },
      customer: {
        id: 3,
        email: 'caspian.meringue@email.com',
        first_name: 'Caspian',
        last_name: 'Meringue',
        avatar: emptyAvatarUrl,
      },
      details: {
        id: 486,
        status: { id: 'on-hold', name: __('On hold', 'mailpoet') },
        total: 14.5,
        products: [products.cup],
      },
    },
    {
      date: `${datePrefix}-11T23:52:18.000Z`,
      email: { id: 1, subject: subjects.abandonedCart },
      customer: {
        id: 4,
        email: 'natalya.fant@email.com',
        first_name: 'Natalya',
        last_name: 'Fant',
        avatar: emptyAvatarUrl,
      },
      details: {
        id: 481,
        status: { id: 'processing', name: __('Processing', 'mailpoet') },
        total: 32.5,
        products: [products.socks, products.bottle],
      },
    },
  ],
  emails: [],
};
