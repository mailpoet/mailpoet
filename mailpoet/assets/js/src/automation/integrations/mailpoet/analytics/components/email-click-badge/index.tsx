import { __ } from '@wordpress/i18n';
import { calculatePercentage } from '../../formatter/calculate-percentage';
import { EmailStats } from '../../store';
import { StatusBadge } from '../../../../../components/status';

function percentageBadgeCalculation(percentage: number): {
  badge: string;
  badgeType: string;
} {
  if (percentage > 3) {
    return {
      badge: __('Excellent', 'mailpoet'),
      badgeType: 'success',
    };
  }

  if (percentage > 1) {
    return {
      badge: __('Good', 'mailpoet'),
      badgeType: 'success',
    };
  }
  return {
    badge: __('Average', 'mailpoet'),
    badgeType: 'warning',
  };
}

type BadgeProps = {
  email: EmailStats | undefined;
  property: 'clicked' | 'opened';
};
export function Badge({ email, property }: BadgeProps): JSX.Element {
  if (!email) {
    return <>0</>;
  }
  // Based on the recipients we were allowed to measure, matching the rate next to it.
  const trackedSent = email.trackedSent ?? email.sent.current;
  // With nobody to measure, the rate next to the badge reads 0% and means nothing.
  if (email.sent.current === 0 || trackedSent === 0) {
    return <>{`${email[property]}`}</>;
  }

  const clickedPercentage = calculatePercentage(email[property], trackedSent);
  const clickedBadge = percentageBadgeCalculation(clickedPercentage);

  return (
    <>
      <StatusBadge
        name={clickedBadge.badge}
        className={clickedBadge.badgeType}
      />
      {`${email[property]}`}
    </>
  );
}
