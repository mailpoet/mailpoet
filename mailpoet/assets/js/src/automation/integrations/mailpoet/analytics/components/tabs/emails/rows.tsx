import { Tooltip } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { EmailStats } from '../../../store';
import { Actions } from './actions';
import { locale } from '../../../../../../config';
import { Cell } from './cell';
import { formattedPrice } from '../../../formatter';
import { openTab } from '../../../navigation/open_tab';

const percentageFormatter = Intl.NumberFormat(locale.toString(), {
  style: 'percent',
  maximumFractionDigits: 2,
});

function calculatePercentage(
  value: number,
  base: number,
  canBeNegative = false,
): number {
  if (base === 0) {
    return 0;
  }
  const percentage = (value * 100) / base;
  return canBeNegative ? percentage - 100 : percentage;
}

function percentageBadgeCalculation(percentage: number): {
  badge: string;
  badgeType: string;
} {
  if (percentage > 3) {
    return {
      badge: __('Excellent', 'mailpoet'),
      badgeType: 'mailpoet-analytics-badge-success',
    };
  }

  if (percentage > 1) {
    return {
      badge: __('Good', 'mailpoet'),
      badgeType: 'mailpoet-analytics-badge-success',
    };
  }
  return {
    badge: __('Average', 'mailpoet'),
    badgeType: 'mailpoet-analytics-badge-warning',
  };
}

export function transformEmailsToRows(emails: EmailStats[]) {
  return emails.map((email) => {
    // Shows the percentage of clicked emails compared to the number of sent emails
    const clickedPercentage = calculatePercentage(
      email.clicked,
      email.sent.current,
    );
    const clickedBadge = percentageBadgeCalculation(clickedPercentage);

    return [
      {
        display: (
          <Cell
            className="mailpoet-automation-analytics-email-name"
            value={email.name}
            // translator: %d is the order number of the email, first email, second email, etc.
            subValue={sprintf(__('Email %d', 'mailpoet'), email.order)}
          />
        ),
        value: email.name,
      },
      {
        display: (
          <Cell
            value={
              <Tooltip text={__('View sending status', 'mailpoet')}>
                <a
                  href={`?page=mailpoet-newsletters#/sending-status/${email.id}`}
                >
                  {`${email.sent.current}`}
                </a>
              </Tooltip>
            }
            subValue={
              // Shows the percentage of sent emails compared to the previous email
              percentageFormatter.format(
                calculatePercentage(
                  email.sent.current,
                  email.sent.previous,
                  true,
                ) / 100,
              )
            }
          />
        ),
        value: email.sent.current,
      },
      {
        display: (
          <Cell
            value={email.opened}
            subValue={
              // Shows the percentage of opened emails compared to the number of sent emails
              percentageFormatter.format(
                calculatePercentage(email.opened, email.sent.current) / 100,
              )
            }
          />
        ),
        value: email.opened,
      },
      {
        display: (
          <Cell
            value={email.clicked}
            className={
              email.sent.current > 0
                ? 'mailpoet-automation-analytics-email-clicked'
                : ''
            }
            subValue={percentageFormatter.format(clickedPercentage / 100)}
            badge={email.sent.current > 0 ? clickedBadge.badge : undefined}
            badgeType={
              email.sent.current > 0 ? clickedBadge.badgeType : undefined
            }
          />
        ),
        value: email.clicked,
      },
      {
        display: (
          <Cell
            value={
              <Tooltip text={__('View orders', 'mailpoet')}>
                <a
                  href={addQueryArgs(window.location.href, {
                    tab: 'automation-orders',
                  })}
                  onClick={(e) => {
                    e.preventDefault();
                    openTab('orders');
                  }}
                >
                  {`${email.orders}`}
                </a>
              </Tooltip>
            }
          />
        ),
        value: email.orders,
      },
      {
        display: <Cell value={formattedPrice(email.revenue)} />,
        value: email.revenue,
      },
      {
        display: <Cell value={email.unsubscribed} />,
        value: email.unsubscribed,
      },
      {
        display: <Actions id={email.id} previewUrl={email.previewUrl} />,
        value: null,
      },
    ];
  });
}
