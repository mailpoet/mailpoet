import { __ } from '@wordpress/i18n';
import { Badge } from '../common/listings/newsletter_stats/badge';

interface Props {
  id: number;
  engagementScore?: number;
}

export function ListingsEngagementScore({
  id,
  engagementScore,
}: Props): JSX.Element {
  const badges = {
    unknown: {
      name: __('Unknown', 'mailpoet'),
      type: 'unknown' as const,
      tooltipTitle: __('Not enough data.', 'mailpoet'),
      tooltipText: __('Fewer than 3 emails sent', 'mailpoet'),
    },
    excellent: {
      name: __('Excellent', 'mailpoet'),
      type: 'excellent' as const,
      tooltipTitle: __('Congrats!', 'mailpoet'),
      tooltipText: __('50% or more', 'mailpoet'),
    },
    good: {
      name: __('Good', 'mailpoet'),
      type: 'good' as const,
      tooltipTitle: __('Good stuff.', 'mailpoet'),
      tooltipText: __('between 20 and 50%', 'mailpoet'),
    },
    average: {
      name: __('Low', 'mailpoet'),
      type: 'average' as const,
      tooltipTitle: __('Something to improve.', 'mailpoet'),
      tooltipText: __('20% or fewer', 'mailpoet'),
    },
  };
  const tooltipId = `badge-${id}`;
  let badge;
  if (engagementScore == null) {
    badge = badges.unknown;
  } else if (engagementScore < 20) {
    badge = badges.average;
  } else if (engagementScore < 50) {
    badge = badges.good;
  } else {
    badge = badges.excellent;
  }
  const tooltipText = (
    <div key={`tooltip-${tooltipId}`}>
      <div className="mailpoet-listing-stats-tooltip-title">
        {badge.tooltipTitle.toUpperCase()}
      </div>
      <div className="mailpoet-listing-stats-tooltip-description">
        {__(
          'Average percent of emails subscribers read in the last year',
          'mailpoet',
        )}
      </div>
      <div className="mailpoet-listing-stats-tooltip-content">
        <Badge type="unknown" name={__('Unknown', 'mailpoet')} />
        {' : '}
        {badges.unknown.tooltipText}
        <br />
        <Badge type="excellent" name={__('Excellent', 'mailpoet')} />
        {' : '}
        {badges.excellent.tooltipText}
        <br />
        <Badge type="good" name={__('Good', 'mailpoet')} />
        {' : '}
        {badges.good.tooltipText}
        <br />
        <Badge type="average" name={__('Low', 'mailpoet')} />
        {' : '}
        {badges.average.tooltipText}
      </div>
    </div>
  );
  return (
    <div className="mailpoet-listing-stats-opened-clicked">
      {engagementScore != null && (
        <div className="mailpoet-listing-stats-percentages">
          {engagementScore.toLocaleString(undefined, {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
          })}
          %
        </div>
      )}
      <div>
        <Badge
          isInverted
          type={badge.type}
          name={badge.name}
          tooltip={tooltipText}
          tooltipId={tooltipId}
          tooltipPlace="top"
        />
      </div>
    </div>
  );
}
