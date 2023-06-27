import { __ } from '@wordpress/i18n';
import { locale } from '../../../../../../config';
import { EmailStats } from '../../../store';
import { formattedPrice } from '../../../formatter';

export function calculateSummary(rows: EmailStats[]) {
  if (rows.length === 0) {
    return [];
  }
  const data = rows.reduce(
    (acc, row) => {
      acc.sent += row.sent;
      acc.opened += row.opened;
      acc.clicked += row.clicked;
      acc.orders += row.orders;
      acc.unsubscribed += row.unsubscribed;
      acc.revenue += row.revenue;
      return acc;
    },
    {
      sent: 0,
      opened: 0,
      clicked: 0,
      orders: 0,
      unsubscribed: 0,
      revenue: 0,
    },
  );

  const compactFormatter = Intl.NumberFormat(locale.toString(), {
    notation: 'compact',
  });
  const summary = [
    {
      label: __('sent', 'mailpoet'),
      value: compactFormatter.format(data.sent),
    },
    {
      label: __('opened', 'mailpoet'),
      value: compactFormatter.format(data.opened),
    },
    {
      label: __('clicked', 'mailpoet'),
      value: compactFormatter.format(data.clicked),
    },
    {
      label: __('orders', 'mailpoet'),
      value: compactFormatter.format(data.orders),
    },
    { label: __('revenue', 'mailpoet'), value: formattedPrice(data.revenue) },
    {
      label: __('unsubscribed', 'mailpoet'),
      value: compactFormatter.format(data.unsubscribed),
    },
  ];

  return summary;
}
