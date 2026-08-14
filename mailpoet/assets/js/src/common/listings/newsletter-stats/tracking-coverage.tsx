import { __, sprintf } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Tooltip } from '../../tooltip/tooltip';

/**
 * count_processed and the per-recipient statistics rows have different writers
 * — a failed send writes a statistics row without bumping count_processed — so
 * the two can genuinely drift. Bound the untracked count to the audience it is
 * describing, so the percentage and the tooltip stay consistent instead of
 * reading "3 of 1 recipients are not tracked" next to "0% tracked".
 */
export function getBoundedNotTracked(
  totalSent: number,
  notTracked: number,
): number {
  return Math.min(Math.max(0, totalSent), Math.max(0, notTracked));
}

/** How much of a campaign's audience the open and click rates rest on. */
export function getTrackingCoveragePercentage(
  totalSent: number,
  notTracked: number,
): number {
  if (totalSent <= 0) {
    return 0;
  }
  const tracked = totalSent - getBoundedNotTracked(totalSent, notTracked);
  return (tracked * 100) / totalSent;
}

/**
 * "not tracked" rather than "opted out" on purpose. When a site asks everyone
 * for consent, most of the untracked group are people we simply never asked,
 * and calling that an opt-out would be wrong.
 */
export function getTrackingCoverageTooltipText(
  totalSent: number,
  notTracked: number,
): string {
  const boundedNotTracked = getBoundedNotTracked(totalSent, notTracked);
  const tracked = Math.max(0, totalSent - boundedNotTracked);
  return sprintf(
    /* translators: %1$s is the number of recipients not tracked, %2$s the total number of recipients, %3$s the number of recipients that were tracked. */
    __(
      '%1$s of %2$s recipients are not tracked, because of their email tracking consent. Open and click rates are based on the %3$s recipients we were able to measure.',
      'mailpoet',
    ),
    boundedNotTracked.toLocaleString(),
    totalSent.toLocaleString(),
    tracked.toLocaleString(),
  );
}

type Props = {
  totalSent: number;
  notTracked: number;
  /** Unique per screen so two coverage tooltips never share an id. */
  tooltipId: string;
  className?: string;
};

/**
 * Shown only when something is untracked. On a site with no opt-outs every
 * screen looks exactly as it does today, which is the issue's third acceptance
 * criterion.
 */
export function TrackingCoverage({
  totalSent,
  notTracked,
  tooltipId,
  className,
}: Props): JSX.Element | null {
  if (notTracked <= 0 || totalSent <= 0) {
    return null;
  }

  const coverage = getTrackingCoveragePercentage(totalSent, notTracked);

  return (
    <div className={className ?? 'mailpoet-listing-stats-coverage'}>
      <span data-tooltip-id={tooltipId}>
        {sprintf(
          /* translators: %s is a percentage, e.g. "95". */
          __('%s%% tracked', 'mailpoet'),
          MailPoet.Num.toLocaleFixed(coverage, 1),
        )}
      </span>
      <Tooltip place="top" id={tooltipId}>
        <div className="mailpoet-listing-stats-tooltip-content mailpoet-listing-stats-coverage-tooltip">
          {getTrackingCoverageTooltipText(totalSent, notTracked)}
        </div>
      </Tooltip>
    </div>
  );
}

TrackingCoverage.displayName = 'TrackingCoverage';
