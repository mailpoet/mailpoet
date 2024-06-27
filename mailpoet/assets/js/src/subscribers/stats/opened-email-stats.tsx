import { FunctionComponent, useMemo } from 'react';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'hooks';
import { Location, Params } from 'react-router-dom';
import { Heading } from 'common/typography/heading/heading';
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

  return (
    <>
      <Heading level={4}>{__('Opened emails', 'mailpoet')}</Heading>
      <Content />
    </>
  );
}
