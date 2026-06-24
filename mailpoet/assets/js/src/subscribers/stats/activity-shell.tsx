import { useState } from 'react';
import {
  Card,
  CardBody,
  CardHeader,
  Flex,
  FlexBlock,
  SelectControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { Location, Params } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { OpenedEmailsStats } from './opened-email-stats';

export type ActivityEventType =
  | 'all'
  | 'open'
  | 'click'
  | 'purchase'
  | 'unsubscribe';

type Props = {
  lastEngagementAt?: string;
  location: Location;
  params: Params;
};

const EVENT_TYPE_OPTIONS: Array<{ label: string; value: ActivityEventType }> = [
  { label: __('All events', 'mailpoet'), value: 'all' },
  { label: __('Email opens', 'mailpoet'), value: 'open' },
  { label: __('Link clicks', 'mailpoet'), value: 'click' },
  { label: __('Purchases', 'mailpoet'), value: 'purchase' },
  { label: __('Unsubscribes', 'mailpoet'), value: 'unsubscribe' },
];

function isDetailedAnalyticsRestricted(): boolean {
  const capability = MailPoet.capabilities?.detailedAnalytics;
  return !capability || capability.isRestricted;
}

export function ActivityShell({
  lastEngagementAt,
  location,
  params,
}: Props): JSX.Element {
  const [eventType, setEventType] = useState<ActivityEventType>('all');
  // Premium replaces this dropdown with a native DataViews event-type filter, so
  // only restricted/free users (who see the upsell) need the control here.
  const showEventTypeFilter = isDetailedAnalyticsRestricted();
  const subtitle = lastEngagementAt
    ? sprintf(
        // translators: %s is a date and time when the subscriber was last seen.
        __('Last seen on %s', 'mailpoet'),
        MailPoet.Date.full(lastEngagementAt),
      )
    : __('Last seen: never', 'mailpoet');

  return (
    <Card
      className="mailpoet-subscriber-stats-card mailpoet-subscriber-stats-activity"
      size="medium"
    >
      <CardHeader className="mailpoet-subscriber-stats-card-header">
        <Flex align="center" gap={3}>
          <FlexBlock>
            <div>
              <h2 className="mailpoet-subscriber-stats-card-title">
                {__('Activity', 'mailpoet')}
              </h2>
              <div className="mailpoet-subscriber-stats-card-subtitle">
                {subtitle}
              </div>
            </div>
          </FlexBlock>
          {showEventTypeFilter && (
            <SelectControl
              className="mailpoet-subscriber-stats-activity-filter"
              hideLabelFromVision
              label={__('Activity event type', 'mailpoet')}
              onChange={(value) => setEventType(value as ActivityEventType)}
              options={EVENT_TYPE_OPTIONS}
              value={eventType}
            />
          )}
        </Flex>
      </CardHeader>
      <CardBody className="mailpoet-subscriber-stats-activity-body">
        <OpenedEmailsStats
          eventType={eventType}
          params={params}
          location={location}
        />
      </CardBody>
    </Card>
  );
}

ActivityShell.displayName = 'ActivityShell';
