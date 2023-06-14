import {__} from "@wordpress/i18n";
import {locale} from "../../../../../../config";
import {EmailStats} from "../../../store";
import {formattedPrice} from "../../../formatter";

export function calculateSummary(rows:EmailStats[]) {
  if (rows.length === 0) {
    return [];
  }
  const data = rows.reduce((acc, row) => {
    acc.sent += row.sent.current;
    acc.opened += row.opened.current;
    acc.clicked += row.clicked.current;
    acc.orders += row.orders.current;
    acc.unsubscribed += row.unsubscribed.current;
    acc.revenue += row.revenue.current;
    return acc;
  }, {
    sent: 0,
    opened: 0,
    clicked: 0,
    orders: 0,
    unsubscribed: 0,
    revenue: 0,
  });

  const summary =  [
    { label: __('sent', 'mailpoet'), value: Intl.NumberFormat(locale.toString(), { notation: 'compact' }).format(data.sent) },
    { label: __('opened', 'mailpoet'), value: Intl.NumberFormat(locale.toString(), { notation: 'compact' }).format(data.opened) },
    { label: __('clicked', 'mailpoet'), value: Intl.NumberFormat(locale.toString(), { notation: 'compact' }).format(data.clicked) },
    { label: __('orders', 'mailpoet'), value: Intl.NumberFormat(locale.toString(), { notation: 'compact' }).format(data.orders) },
    { label: __('revenue', 'mailpoet'), value: formattedPrice(data.revenue) },
    { label: __('unsubscribed', 'mailpoet'), value: Intl.NumberFormat(locale.toString(), { notation: 'compact' }).format(data.unsubscribed) },
  ];

  return summary;
}
