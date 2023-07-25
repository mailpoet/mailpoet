import { __ } from '@wordpress/i18n';
import { MailPoet } from '../../mailpoet';

export type PropTypes = {
  lastClick?: string;
  lastEngagement?: string;
  lastOpen?: string;
  lastPageView?: string;
  lastPurchase?: string;
  lastSending?: string;
  wooCommerceActive: boolean;
};

function dateOrNever(date?: string): string {
  if (typeof date === 'string') {
    return MailPoet.Date.format(date);
  }
  return __('never', 'mailpoet');
}

export function EngagementSummary({
  lastClick,
  lastEngagement,
  lastOpen,
  lastPageView,
  lastPurchase,
  lastSending,
  wooCommerceActive,
}: PropTypes): JSX.Element {
  const stats = [
    { label: __('Last click', 'mailpoet'), date: lastClick },
    { label: __('Last engagement', 'mailpoet'), date: lastEngagement },
    { label: __('Last open', 'mailpoet'), date: lastOpen },
    { label: __('Last page view', 'mailpoet'), date: lastPageView },
    { label: __('Last sending', 'mailpoet'), date: lastSending },
  ];

  if (wooCommerceActive) {
    stats.push({ label: __('Last purchase', 'mailpoet'), date: lastPurchase });
  }

  stats.sort((a, b) => {
    if (a.date === b.date) {
      return 0;
    }
    if (!a.date) {
      return 1;
    }
    if (!b.date) {
      return -1;
    }
    return b.date.localeCompare(a.date);
  });

  return (
    <div className="mailpoet-tab-content mailpoet-subscriber-stats-summary">
      <div className="mailpoet-listing">
        <table className="mailpoet-listing-table">
          <tbody>
            {stats.map(({ label, date }) => (
              <tr key={label}>
                <td>{label}</td>
                <td>
                  <b>{dateOrNever(date)}</b>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
