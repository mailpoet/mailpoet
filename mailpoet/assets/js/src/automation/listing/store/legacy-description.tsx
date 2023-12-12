import { ReactNode } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { ListingItem } from './legacy-api';

const mailpoetRoles = window.mailpoet_roles || {};
const mailpoetSegments = window.mailpoet_segments || [];

const getSendingDelay = (item: ListingItem): ReactNode => {
  const options = item.options;

  if (options.afterTimeType === 'immediate') {
    return undefined;
  }

  const number = options.afterTimeNumber;
  switch (options.afterTimeType) {
    case 'minutes':
      return sprintf(__('%d minute(s) later', 'mailpoet'), number);
    case 'hours':
      return sprintf(__('%d hour(s) later', 'mailpoet'), number);
    case 'days':
      return sprintf(__('%d day(s) later', 'mailpoet'), number);
    case 'weeks':
      return sprintf(__('%d week(s) later', 'mailpoet'), number);
    default:
      return __('Invalid sending delay.', 'mailpoet');
  }
};

const getWelcomeInfo = (item: ListingItem): ReactNode => {
  const options = item.options;

  if (options.event === 'user') {
    if (options.role === 'mailpoet_all') {
      return __(
        'Sent when a new WordPress user is added to your site.',
        'mailpoet',
      );
    }
    return sprintf(
      __(
        'Sent when a new WordPress user with the role %s is added to your site.',
        'mailpoet',
      ),
      mailpoetRoles[options.role],
    );
  }

  // get segment
  const segment = Object.values(mailpoetSegments).find(
    ({ id }) => Number(id) === Number(options.segment),
  );

  if (segment === undefined) {
    return (
      <a href={`/send/${item.id}`}>
        {__('You need to select a list to send to.', 'mailpoet')}
      </a>
    );
  }

  return sprintf(
    __('Sent when someone subscribes to the list: %s.', 'mailpoet'),
    segment.name,
  );
};

export const getDescription = (item: ListingItem): ReactNode => {
  const info = getWelcomeInfo(item);
  const delay = info ? getSendingDelay(item) : undefined;

  return info ? (
    <>
      {info}
      {delay && <> {delay}</>}
    </>
  ) : undefined;
};
