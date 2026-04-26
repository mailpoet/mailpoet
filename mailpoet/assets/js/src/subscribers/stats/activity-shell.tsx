import { Card, CardBody, CardHeader } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { Location, Params } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { OpenedEmailsStats } from './opened-email-stats';

type Props = {
  lastEngagementAt?: string;
  location: Location;
  params: Params;
};

export function ActivityShell({
  lastEngagementAt,
  location,
  params,
}: Props): JSX.Element {
  const subtitle = lastEngagementAt
    ? sprintf(
        // translators: %s is a date and time when the subscriber was last seen.
        __('Last seen on %s', 'mailpoet'),
        MailPoet.Date.format(lastEngagementAt),
      )
    : __('Last seen: never', 'mailpoet');

  return (
    <Card
      className="mailpoet-subscriber-stats-card mailpoet-subscriber-stats-activity"
      size="medium"
    >
      <CardHeader className="mailpoet-subscriber-stats-card-header">
        <div>
          <h2 className="mailpoet-subscriber-stats-card-title">
            {__('Activity', 'mailpoet')}
          </h2>
          <div className="mailpoet-subscriber-stats-card-subtitle">
            {subtitle}
          </div>
        </div>
      </CardHeader>
      <CardBody className="mailpoet-subscriber-stats-activity-body">
        <OpenedEmailsStats params={params} location={location} />
      </CardBody>
    </Card>
  );
}

ActivityShell.displayName = 'ActivityShell';
