import { Link } from 'react-router-dom';
import { DynamicSegment, DynamicSegmentAction } from 'segments/types';
import * as ROUTES from 'segments/routes';
import { Button, DropdownMenu } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { MailPoet } from '../../../mailpoet';
import { storeName } from '../store';

export function getRow(
  dynamicSegment: DynamicSegment,
  tab: string,
  onSelect: (action: DynamicSegmentAction, segment: DynamicSegment) => void,
): object[] {
  const toggleSelect = (): void => {
    if (dynamicSegment?.selected) {
      dispatch(storeName).unselectDynamicSection(dynamicSegment);
      return;
    }
    dispatch(storeName).selectDynamicSection(dynamicSegment);
  };

  const menuItems =
    tab !== 'trash'
      ? [
          {
            key: 'trash',
            control: {
              title: __('Move to trash', 'mailpoet'),
              icon: null,
              onClick: () => onSelect('trash', dynamicSegment),
            },
          },
        ]
      : [
          {
            key: 'restore',
            control: {
              title: __('Restore', 'mailpoet'),
              icon: null,
              onClick: () => onSelect('restore', dynamicSegment),
            },
          },
          {
            key: 'delete',
            control: {
              title: __('Delete permanently', 'mailpoet'),
              icon: null,
              onClick: () => onSelect('delete', dynamicSegment),
            },
          },
        ];
  return [
    {
      value: null,
      display: (
        <input
          type="checkbox"
          checked={dynamicSegment?.selected}
          onChange={toggleSelect}
        />
      ),
    },
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
          <DropdownMenu
            className="mailpoet-automation-listing-more-button"
            label={__('More', 'mailpoet')}
            icon={moreVertical}
            controls={menuItems.map(({ control }) => control)}
            popoverProps={{ position: 'bottom left' }}
          />
        </div>
      ),
    },
  ];
}
