import { __ } from '@wordpress/i18n';
import { OrderData } from '../../../store';
import { formattedPrice } from '../../../formatter';

function calculateDaysPassed(data: OrderData[]): number {
  const dates = data
    .map((order) => {
      const date = new Date(order.date);
      return date.getTime();
    })
    .filter((value, index, self) => self.indexOf(value) === index);

  const latestDate = Math.max.apply(null, dates);
  const earliestDate = Math.min.apply(null, dates);

  return Math.max.apply(null, [
    1,
    Math.round((latestDate - earliestDate) / (1000 * 3600 * 24)),
  ]) as number;
}

export function calculateSummary(data: OrderData[]) {
  const subscribers = data
    .map((order) => order.customer.id)
    .filter((value, index, self) => self.indexOf(value) === index).length;

  const products = data
    .map((order) => order.details.products.map((item) => item.id))
    .flat()
    .filter((value, index, self) => self.indexOf(value) === index).length;

  const revenue = data
    .map((order) => order.details.total)
    .reduce((a, b) => a + b, 0);
  return [
    {
      label: __('days', 'mailpoet'),
      value: calculateDaysPassed(data),
    },
    {
      label: __('subscribers', 'mailpoet'),
      value: subscribers,
    },
    {
      label: __('products', 'mailpoet'),
      value: products,
    },
    {
      label: __('revenue', 'mailpoet'),
      value: formattedPrice(revenue),
    },
  ];
}
