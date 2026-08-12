import { Tooltip } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { EmailStats } from '../../../store';
import { Actions } from './actions';
import { locale } from '../../../../../../config';
import { Cell } from './cell';
import { formattedPrice } from '../../../formatter';
import { openTab } from '../../../navigation/open-tab';
import { calculatePercentage } from '../../../formatter/calculate-percentage';
import { Badge } from '../../email-click-badge';
import { MailPoet } from '../../../../../../../mailpoet';

const percentageFormatter = Intl.NumberFormat(locale.toString(), {
  style: 'percent',
  maximumFractionDigits: 2,
});

export function transformEmailsToRows(emails: EmailStats[]) {
  return emails.map((email) => {
    // Open and click rates are based on the recipients we were allowed to
    // measure, not on everyone we sent to. Older payloads carry no
    // trackedSent, so fall back to the full count.
    const trackedSent = email.trackedSent ?? email.sent.current;
    const clickedPercentage = calculatePercentage(email.clicked, trackedSent);

    const rows = [
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
              // Percentage of the recipients we could measure who opened.
              percentageFormatter.format(
                calculatePercentage(email.opened, trackedSent) / 100,
              )
            }
          />
        ),
        value: email.opened,
      },
      {
        display: (
          <Cell
            value={<Badge email={email} property="clicked" />}
            className={
              email.sent.current > 0
                ? 'mailpoet-automation-analytics-email-clicked'
                : ''
            }
            subValue={percentageFormatter.format(clickedPercentage / 100)}
          />
        ),
        value: email.clicked,
      },
    ];

    if (MailPoet.isWoocommerceActive) {
      rows.push(
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
                      openTab('orders', {
                        filters: { emails: [`${email.id}`] },
                      });
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
      );
    }

    return rows.concat([
      {
        display: <Cell value={email.unsubscribed} />,
        value: email.unsubscribed,
      },
      {
        display: (
          <Actions
            id={email.id}
            previewUrl={email.previewUrl}
            wpPostId={email?.wpPostId ?? null}
          />
        ),
        value: null,
      },
    ]);
  });
}
