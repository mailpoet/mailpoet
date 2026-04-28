import { __ } from '@wordpress/i18n';
import { NewsletterType } from './newsletter-type';

type Props = {
  newsletter: NewsletterType;
};

const reasonLabels: Record<string, string> = {
  normal: __('I no longer want to receive these emails', 'mailpoet'),
  nosignup: __('I never signed up for this mailing list', 'mailpoet'),
  inappropriate: __('The emails are inappropriate', 'mailpoet'),
  spam: __('The emails are spam and should be reported', 'mailpoet'),
  other: __('Other (fill in reason below)', 'mailpoet'),
  unspecified: __('No reason provided', 'mailpoet'),
};

export function UnsubscribeReasonStats({ newsletter }: Props) {
  const reasons = newsletter.statistics.unsubscribeReasons || [];
  if (reasons.length === 0) {
    return null;
  }

  return (
    <div className="mailpoet-stats-general">
      <h2>{__('Unsubscribe reasons', 'mailpoet')}</h2>
      <table className="widefat striped">
        <thead>
          <tr>
            <th>{__('Reason', 'mailpoet')}</th>
            <th>{__('Responses', 'mailpoet')}</th>
          </tr>
        </thead>
        <tbody>
          {reasons.map(({ reason, count }) => (
            <tr key={reason}>
              <td>{reasonLabels[reason] || reason}</td>
              <td>{Number(count).toLocaleString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
