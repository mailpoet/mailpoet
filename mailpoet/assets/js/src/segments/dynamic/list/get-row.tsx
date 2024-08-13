import { Link } from 'react-router-dom';
import { DynamicSegment, DynamicSegmentAction } from 'segments/types';
import * as ROUTES from 'segments/routes';
import { Button, DropdownMenu } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { MailPoet } from '../../../mailpoet';
import { storeName } from '../store';
import { isErrorResponse } from '../../../ajax';

async function duplicateDynamicSegment(segment: DynamicSegment) {
  try {
    const response = await MailPoet.Ajax.post({
      api_version: 'v1',
      endpoint: 'dynamic_segments',
      action: 'duplicate',
      data: {
        id: segment.id,
      },
    });
    const newSegment = response.data as DynamicSegment;
    const successMessage = sprintf(
      __('Segment "%s" has been duplicated.', 'mailpoet'),
      newSegment.name,
    );
    void dispatch(noticesStore).createSuccessNotice(successMessage);
    void dispatch(storeName).loadDynamicSegments();
  } catch (errorResponse: unknown) {
    if (isErrorResponse(errorResponse)) {
      MailPoet.Notice.showApiErrorNotice(errorResponse);
    }
  }
}

export function getRow(
  dynamicSegment: DynamicSegment,
  tab: string,
  onSelect: (action: DynamicSegmentAction, segment: DynamicSegment) => void,
): object[] {
  const toggleSelect = (): void => {
    if (dynamicSegment?.selected) {
      void dispatch(storeName).unselectDynamicSection(dynamicSegment);
      return;
    }
    void dispatch(storeName).selectDynamicSection(dynamicSegment);
  };

  const menuItems =
    tab !== 'trash'
      ? [
          {
            key: 'duplicate',
            control: {
              title: __('Duplicate', 'mailpoet'),
              icon: null,
              onClick: () => {
                void duplicateDynamicSegment(dynamicSegment);
              },
            },
          },
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
          checked={dynamicSegment?.selected ?? false}
          onChange={toggleSelect}
        />
      ),
    },
    {
      value: dynamicSegment.name,
      display: (
        <div
          data-automation-id={`mailpoet_dynamic_segment_name_${dynamicSegment.id}`}
        >
          <Link to={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/${dynamicSegment.id}`}>
            {dynamicSegment.name}
          </Link>
          {dynamicSegment.description && (
            <div>{dynamicSegment.description}</div>
          )}
        </div>
      ),
    },
    dynamicSegment.is_plugin_missing
      ? {
          value: dynamicSegment.missing_plugin_message,
          display: (
            <div
              data-automation-id={`mailpoet_dynamic_segment_plugin_missing_message_${dynamicSegment.id}`}
            >
              {dynamicSegment.missing_plugin_message.message}
            </div>
          ),
        }
      : {
          value: dynamicSegment.count_all,
          display: (
            <div
              data-automation-id={`mailpoet_dynamic_segment_count_all_${dynamicSegment.id}`}
            >
              {dynamicSegment.count_all}
            </div>
          ),
        },
    dynamicSegment.is_plugin_missing
      ? {
          value: null,
          display: null,
        }
      : {
          value: dynamicSegment.count_subscribed,
          display:
            dynamicSegment.count_subscribed === '0' ? (
              dynamicSegment.count_subscribed
            ) : (
              <Button
                data-automation-id={`mailpoet_dynamic_segment_count_subscribed_${dynamicSegment.id}`}
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
        <div
          data-automation-id={`mailpoet_dynamic_segment_created_at_${dynamicSegment.id}`}
        >
          {MailPoet.Date.short(dynamicSegment.created_at)}
          <br />
          {MailPoet.Date.time(dynamicSegment.created_at)}
        </div>
      ),
    },
    {
      value: null,
      display: (
        <div
          className="mailpoet-listing-actions-cell"
          data-automation-id={`mailpoet_dynamic_segment_actions_${dynamicSegment.id}`}
        >
          <Button variant="tertiary" href={dynamicSegment.subscribers_url}>
            {__('View subscribers', 'mailpoet')}
          </Button>
          {dynamicSegment.is_plugin_missing ? (
            <Button
              data-automation-id={`mailpoet_dynamic_segment_edit_button_${dynamicSegment.id}`}
              variant="tertiary"
              disabled
            >
              {__('Edit', 'mailpoet')}
            </Button>
          ) : (
            <Button
              data-automation-id={`mailpoet_dynamic_segment_edit_button_${dynamicSegment.id}`}
              variant="tertiary"
              href={`#${ROUTES.EDIT_DYNAMIC_SEGMENT}/${dynamicSegment.id}`}
            >
              {__('Edit', 'mailpoet')}
            </Button>
          )}
          <DropdownMenu
            className="mailpoet-listing-more-button"
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
