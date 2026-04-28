import { __ } from '@wordpress/i18n';
import { NewsletterType } from './newsletter-type';

type Props = {
  newsletter: NewsletterType;
};

const reasonLabels: Record<string, string> = {
  too_many_emails: __('Too many emails', 'mailpoet'),
  not_relevant: __('Content is not relevant', 'mailpoet'),
  do_not_remember_signing_up: __('I do not remember signing up', 'mailpoet'),
  no_longer_interested: __('I am no longer interested', 'mailpoet'),
  too_promotional: __('Emails are too promotional', 'mailpoet'),
  other: __('Other', 'mailpoet'),
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
