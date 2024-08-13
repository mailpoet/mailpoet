import { __ } from '@wordpress/i18n';
import { MailPoet } from '../../mailpoet';
import { StatsType } from '../types';

export type PropTypes = {
  stats: StatsType;
};

function dateOrNever(date?: string): string {
  if (typeof date === 'string') {
    return MailPoet.Date.format(date);
  }
  return __('never', 'mailpoet');
}

export function EngagementSummary({ stats }: PropTypes): JSX.Element {
  const engagementData = [
    {
      label: __('Last click', 'mailpoet'),
      date: stats.last_click || null,
    },
    {
      label: __('Last engagement', 'mailpoet'),
      date: stats.last_engagement || null,
    },
    {
      label: __('Last open', 'mailpoet'),
      date: stats.last_open || null,
    },
    {
      label: __('Last page view', 'mailpoet'),
      date: stats.last_page_view || null,
    },
    {
      label: __('Last sending', 'mailpoet'),
      date: stats.last_sending || null,
    },
  ];

  if (stats.is_woo_active) {
    engagementData.push({
      label: __('Last purchase', 'mailpoet'),
      date: stats.last_purchase || null,
    });
  }

  engagementData.sort((a, b) => {
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
            {engagementData.map(({ label, date }) => (
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
