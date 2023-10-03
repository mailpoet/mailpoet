import { Link } from 'react-router-dom';
import { DynamicSegment } from 'segments/types';
import * as ROUTES from 'segments/routes';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, moreVertical } from '@wordpress/icons';
import { MailPoet } from '../../../mailpoet';

export function getRow(dynamicSegment: DynamicSegment): object[] {
  return [
    { value: null, display: <input type="checkbox" /> },
    {
      value: dynamicSegment.name,
      display: (
        <>
          <Link to={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/${dynamicSegment.id}`}>
            {dynamicSegment.name}
          </Link>
          {dynamicSegment.description && <p>{dynamicSegment.description}</p>}
        </>
      ),
    },
    {
      value: dynamicSegment.count_all,
      display: <p>{dynamicSegment.count_all}</p>,
    },
    {
      value: dynamicSegment.count_subscribed,
      display:
        dynamicSegment.count_subscribed === '0' ? (
          dynamicSegment.count_subscribed
        ) : (
          <Button
            className="mailpoet-listing-text-right-align"
            variant="link"
            href={dynamicSegment.subscribers_url}
          >
            {dynamicSegment.count_subscribed}
          </Button>
        ),
    },
    {
      value: dynamicSegment.created_at,
      display: (
        <p>
          {MailPoet.Date.short(dynamicSegment.created_at)} /{' '}
          {MailPoet.Date.time(dynamicSegment.created_at)}
        </p>
      ),
    },
    {
      value: null,
      display: (
        <div className="mailpoet-listing-actions-cell">
          <Button variant="tertiary" href={dynamicSegment.subscribers_url}>
            {__('View subscribers', 'mailpoet')}
          </Button>
          <Button
            variant="tertiary"
            href={`#${ROUTES.EDIT_DYNAMIC_SEGMENT}/${dynamicSegment.id}`}
          >
            {__('Edit', 'mailpoet')}
          </Button>
          <Icon icon={moreVertical} />
        </div>
      ),
    },
  ];
}
