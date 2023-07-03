import { __ } from '@wordpress/i18n';
import { calculatePercentage } from '../../formatter/calculate_percentage';
import { EmailStats } from '../../store';

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

type BadgeProps = {
  email: EmailStats;
  property: 'clicked' | 'opened';
  className?: string;
};
export function Badge({ email, property, className }: BadgeProps): JSX.Element {
  if (email.sent.current === 0) {
    return <>{`${email[property]}`}</>;
  }

  // Shows the percentage of clicked emails compared to the number of sent emails
  const clickedPercentage = calculatePercentage(
    email[property],
    email.sent.current,
  );
  const clickedBadge = percentageBadgeCalculation(clickedPercentage);

  return (
    <div
      className={`mailpoet-analytics-badge ${className ?? ''} ${
        clickedBadge.badgeType ?? ''
      }`}
    >
      <span className="mailpoet-analytics-badge-text">
        {clickedBadge.badge}
      </span>
      {`${email[property]}`}
    </div>
  );
}
