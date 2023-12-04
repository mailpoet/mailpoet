import ReactStringReplace from 'react-string-replace';
import { __, _x } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import { Grid } from 'common/grid';
import {
  getBadgeType,
  StatsBadge,
} from 'common/listings/newsletter-stats/stats';
import { Tooltip } from 'help-tooltip';

import { NewsletterType } from './newsletter-type';

type Props = {
  newsletter: NewsletterType;
  isWoocommerceActive: boolean;
};

const minNewslettersSent = 20;
const minNewslettersOpened = 5;
const minUnsubscribedStat = 5;
const minBouncedStat = 5;
const minNewslettersSentForBouncedAndUnsubscribed = 100;

// When percentage value is lower then 0.1 we want to display value with two decimal places
const formatWithOptimalPrecision = (value: number) => {
  const precision = value < 0.1 ? 2 : 1;
  return MailPoet.Num.toLocaleFixed(value, precision);
};

/*
 * FormatForStats
 * always round-up to one decimal place
 * in stats.tsx, we are comparing against 0, 0.3, 0.5, 0.7, 1.5, etc
 */
const formatForStats = (value: number): number => {
  const numValue = +value;
  return +numValue.toFixed(1);
};

function NewsletterGeneralStats({ newsletter, isWoocommerceActive }: Props) {
  const totalSent = newsletter.total_sent || 0;

  let percentageClicked = 0;
  let percentageOpened = 0;
  let percentageMachineOpened = 0;
  let percentageUnsubscribed = 0;
  let percentageBounced = 0;
  if (totalSent > 0) {
    percentageClicked = (newsletter.statistics.clicked * 100) / totalSent;
    percentageOpened = (newsletter.statistics.opened * 100) / totalSent;
    percentageMachineOpened =
      (newsletter.statistics.machineOpened * 100) / totalSent;
    percentageUnsubscribed =
      (newsletter.statistics.unsubscribed * 100) / totalSent;
    percentageBounced = (newsletter.statistics.bounced * 100) / totalSent;
  }
  // format by decimal places count
  const percentageClickedDisplay =
    formatWithOptimalPrecision(percentageClicked);
  const percentageOpenedDisplay = formatWithOptimalPrecision(percentageOpened);
  const percentageMachineOpenedDisplay = formatWithOptimalPrecision(
    percentageMachineOpened,
  );
  const percentageUnsubscribedDisplay = formatWithOptimalPrecision(
    percentageUnsubscribed,
  );
  const percentageBouncedDisplay =
    formatWithOptimalPrecision(percentageBounced);

  const displayBadges =
    totalSent >= minNewslettersSent &&
    newsletter.statistics.opened >= minNewslettersOpened;

  const displayUnsubscribedBadge =
    newsletter.statistics.unsubscribed >= minUnsubscribedStat &&
    totalSent >= minNewslettersSentForBouncedAndUnsubscribed;
  const displayBouncedBadge =
    newsletter.statistics.bounced >= minBouncedStat &&
    totalSent >= minNewslettersSentForBouncedAndUnsubscribed;

  const badgeTypeOpened = getBadgeType('opened', percentageOpened) || '';
  const opened = (
    <>
      <div className="mailpoet-statistics-value-small">
        <span
          className={`mailpoet-statistics-value-number mailpoet-statistics-value-number-${badgeTypeOpened}`}
        >
          {percentageOpenedDisplay}
          {'% '}
        </span>
        {_x(
          'opened',
          'Percentage of subscribers that opened a newsletter link',
          'mailpoet',
        )}
      </div>
      {displayBadges && (
        <StatsBadge
          isInverted={false}
          stat="opened"
          rate={percentageOpened}
          tooltipId={`opened-${newsletter.id || '0'}`}
          tooltipPlace="right"
        />
      )}
    </>
  );

  const machineOpened = (
    <div className="mailpoet-statistics-value-small">
      <span className="mailpoet-statistics-value-number">
        {percentageMachineOpenedDisplay}
        {'% '}
      </span>
      {_x(
        'machine-opened',
        'Percentage of newsletters that were opened by a machine',
        'mailpoet',
      )}
      <Tooltip
        tooltip={ReactStringReplace(
          __(
            'A machine-opened email is an email opened by a computer in the background without the user’s explicit request or knowledge. [link]Read more[/link]',
            'mailpoet',
          ),
          /\[link](.*?)\[\/link]/,
          (match) => (
            <span style={{ pointerEvents: 'all' }} key="machine-opened-tooltip">
              <a
                href="https://kb.mailpoet.com/article/368-what-are-machine-opens"
                key="kb-link"
                target="_blank"
                rel="noopener noreferrer"
              >
                {match}
              </a>
            </span>
          ),
        )}
      />
    </div>
  );

  const formattedPercentageUnsubscribed = formatForStats(
    percentageUnsubscribed,
  );
  const badgeTypeUnsubscribed = displayUnsubscribedBadge
    ? getBadgeType('unsubscribed', formattedPercentageUnsubscribed)
    : '';
  const unsubscribed = (
    <>
      <div className="mailpoet-statistics-value-small">
        <span
          className={`mailpoet-statistics-value-number mailpoet-statistics-value-number-${badgeTypeUnsubscribed}`}
        >
          {percentageUnsubscribedDisplay}
          {'% '}
        </span>
        {_x(
          'unsubscribed',
          'Percentage of subscribers that unsubscribed from a newsletter',
          'mailpoet',
        )}
      </div>
      {displayUnsubscribedBadge && (
        <StatsBadge
          isInverted={false}
          stat="unsubscribed"
          rate={formattedPercentageUnsubscribed}
          tooltipId={`unsubscribed-${newsletter.id || '0'}`}
          tooltipPlace="right"
        />
      )}
    </>
  );

  const formattedPercentageBounced = formatForStats(percentageBounced);
  const badgeTypeBounced = displayBouncedBadge
    ? getBadgeType('bounced', formattedPercentageBounced)
    : '';
  const bounced = (
    <>
      <div className="mailpoet-statistics-value-small">
        <span
          className={`mailpoet-statistics-value-number mailpoet-statistics-value-number-${badgeTypeBounced}`}
        >
          {percentageBouncedDisplay}
          {'% '}
        </span>
        {_x(
          'bounced',
          'Percentage of subscribers that bounced from a newsletter',
          'mailpoet',
        )}
      </div>
      {displayBouncedBadge && (
        <StatsBadge
          isInverted={false}
          stat="bounced"
          rate={formattedPercentageBounced}
          tooltipId={`bounced-${newsletter.id || '0'}`}
          tooltipPlace="right"
        />
      )}
    </>
  );

  const badgeTypeClicked = getBadgeType('clicked', percentageClicked);
  const clicked = (
    <>
      <div className="mailpoet-statistics-value">
        <span
          className={`mailpoet-statistics-value-number mailpoet-statistics-value-number-${badgeTypeClicked}`}
        >
          {percentageClickedDisplay}
          {'% '}
        </span>
        {_x(
          'clicked',
          'Percentage of subscribers that clicked a newsletter link',
          'mailpoet',
        )}
      </div>
      {displayBadges && (
        <StatsBadge
          isInverted={false}
          stat="clicked"
          rate={percentageClicked}
          tooltipId={`clicked-${newsletter.id || '0'}`}
          tooltipPlace="right"
        />
      )}
    </>
  );

  return (
    <div className="mailpoet-stats-general">
      <Grid.ThreeColumns className="mailpoet-stats-general-top-row">
        <div>
          <div className="mailpoet-statistics-value-small">
            {__('Sent to', 'mailpoet')}
            {': '}
            <span className="mailpoet-statistics-value-number">
              {totalSent.toLocaleString()}
            </span>
          </div>
        </div>
        <div className="mailpoet-statistics-with-left-separator">
          {unsubscribed}
        </div>
        <div className="mailpoet-statistics-with-left-separator">{bounced}</div>
      </Grid.ThreeColumns>
      <Grid.ThreeColumns>
        <div>{clicked}</div>
        <div className="mailpoet-statistics-with-left-separator">
          {opened}
          {machineOpened}
        </div>
        {isWoocommerceActive && (
          <div className="mailpoet-statistics-with-left-separator">
            {Hooks.applyFilters(
              'mailpoet_newsletters_revenues_stats',
              null,
              newsletter.statistics.revenue,
            )}
          </div>
        )}
        {!isWoocommerceActive && <div />}
      </Grid.ThreeColumns>
      <div className="mailpoet-stats-general-read-more">
        <p className="mailpoet-stats-has-margin-left">
          <a
            href="https://kb.mailpoet.com/article/190-whats-a-good-email-open-rate"
            target="_blank"
            rel="noopener noreferrer"
          >
            {__('Read more on stats.', 'mailpoet')}
          </a>
        </p>
        <p>
          <a
            href={`admin.php?page=mailpoet-newsletters#/sending-status/${newsletter.id}`}
          >
            {__('Sending stats', 'mailpoet')}
          </a>
        </p>
      </div>
    </div>
  );
}

NewsletterGeneralStats.displayName = 'NewsletterGeneralStats';
export { NewsletterGeneralStats };
