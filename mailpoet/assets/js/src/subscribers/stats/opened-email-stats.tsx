import { FunctionComponent, useMemo } from 'react';
import { Hooks } from 'hooks';
import { Location, Params } from 'react-router-dom';
import { NoAccessInfo } from './no-access-info';

type Props = {
  params: Params;
  location: Location;
};

export function OpenedEmailsStats({ params, location }: Props): JSX.Element {
  const Content = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet_subscribers_opened_emails_stats',
        () => <NoAccessInfo />,
        params,
        location,
      ) as FunctionComponent,
    [location, params],
  );

  return <Content />;
}
