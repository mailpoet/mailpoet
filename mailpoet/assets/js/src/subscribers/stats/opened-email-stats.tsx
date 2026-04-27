import { FunctionComponent, useMemo } from 'react';
import { Hooks } from 'hooks';
import { Location, Params } from 'react-router-dom';
import { NoAccessInfo } from './no-access-info';
import type { ActivityEventType } from './activity-shell';

type Props = {
  eventType: ActivityEventType;
  params: Params;
  location: Location;
};

export function OpenedEmailsStats({
  eventType,
  params,
  location,
}: Props): JSX.Element {
  const Content = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet_subscribers_opened_emails_stats',
        () => <NoAccessInfo eventType={eventType} />,
        params,
        location,
        eventType,
      ) as FunctionComponent,
    [eventType, location, params],
  );

  return <Content />;
}
