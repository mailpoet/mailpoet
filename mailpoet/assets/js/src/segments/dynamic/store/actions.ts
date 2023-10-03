import { ChangeEvent } from 'react';
import { select } from '@wordpress/data';
import { MailPoet } from 'mailpoet';

import * as ROUTES from '../../routes';
import {
  Actions,
  ActionType,
  AnyFormItem,
  SetSegmentActionType,
  SetErrorsActionType,
  SetSegmentFilerActionType,
  SubscriberCount,
  SetSubscriberCountActionType,
  UpdateSegmentActionData,
  Segment,
  SegmentTemplate,
  SetPreviousPageActionType,
  DynamicSegment,
} from '../types';
import { storeName } from './constants';

export function setSegment(segment: AnyFormItem): SetSegmentActionType {
  return {
    type: Actions.SET_SEGMENT,
    segment,
  };
}

function resetSegmentAndErrors(): ActionType {
  return {
    type: Actions.RESET_SEGMENT_AND_ERRORS,
  };
}

export function setErrors(errors: string[]): SetErrorsActionType {
  return {
    type: Actions.SET_ERRORS,
    errors,
  };
}

export function updateSegment(
  data: UpdateSegmentActionData,
): SetSegmentActionType {
  return {
    type: Actions.UPDATE_SEGMENT,
    segment: data,
  };
}

export function updateSegmentFilter(
  filter: AnyFormItem,
  filterIndex: number,
): SetSegmentFilerActionType {
  return {
    type: Actions.UPDATE_SEGMENT_FILTER,
    filter,
    filterIndex,
  };
}

export function updateSegmentFromEvent(
  propertyName: string,
  event: ChangeEvent<HTMLSelectElement | HTMLInputElement>,
): SetSegmentActionType {
  return {
    type: Actions.UPDATE_SEGMENT,
    segment: {
      [propertyName]: event.target.value,
    },
  };
}

export function updateSegmentFilterFromEvent(
  propertyName: string,
  filterIndex: number,
  event: ChangeEvent<HTMLSelectElement | HTMLInputElement>,
): SetSegmentFilerActionType {
  return {
    type: Actions.UPDATE_SEGMENT_FILTER,
    filter: {
      [propertyName]: event.target.value,
    },
    filterIndex,
  };
}

export function updateSubscriberCount(
  data: SubscriberCount,
): SetSubscriberCountActionType {
  return {
    type: Actions.UPDATE_SUBSCRIBER_COUNT,
    subscriberCount: data,
  };
}

export function* pageLoaded(segmentId?: number | string): Generator<{
  type: string;
  segmentId?: number;
}> {
  if (segmentId === undefined) return; // new segment no need to load anything
  MailPoet.Modal.loading(true);

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { res, success } = yield {
    type: 'LOAD_SEGMENT',
    segmentId: Number(segmentId),
  };
  if (!success || res.is_plugin_missing) {
    window.location.href = 'admin.php?page=mailpoet-segments#/segments';
  }
  yield setSegment(res as AnyFormItem);
  MailPoet.Modal.loading(false);
}

export function* pageUnloaded() {
  yield resetSegmentAndErrors();
}

const messages = {
  onUpdate: (): void => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentUpdated'));
  },
  onCreate: (data): void => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentAdded'));
    MailPoet.trackEvent('Segments > Add new', {
      type: data.segmentType || 'unknown type',
      subtype: data.action || data.wordpressRole || 'unknown subtype',
    });
  },
};

export function* handleSave(
  isNewSegment: boolean,
  newsletterId?: string,
): Generator<{
  type: string;
  segment?: AnyFormItem;
}> {
  const segment = select(storeName).getSegment();
  yield setErrors([]);
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { error, success, data } = yield {
    type: 'SAVE_SEGMENT',
    segment,
  };

  if (success) {
    const savedSegmentId = data?.id as string;
    if (newsletterId && savedSegmentId) {
      window.location.href = `admin.php?page=mailpoet-newsletters#/send/${newsletterId}?filterSegmentId=${savedSegmentId}`;
    } else {
      window.location.href = 'admin.php?page=mailpoet-segments#/segments';

      if (isNewSegment) {
        messages.onCreate(segment);
      } else {
        messages.onUpdate();
      }
    }
  } else {
    yield setErrors(error as string[]);
  }
}

export function* createFromTemplate(
  segmentTemplate: SegmentTemplate,
): Generator<{
  type: string;
  segment?: Segment;
}> {
  MailPoet.Modal.loading(true);

  const segment = select(storeName).getSegment();

  segment.name = segmentTemplate.name;
  segment.description = segmentTemplate.description;
  segment.filters = segmentTemplate.filters;
  segment.force_creation = true; // create segment with a random name if one with the same name already exists

  if (segmentTemplate.filtersConnect) {
    segment.filters_connect = segmentTemplate.filtersConnect;
  }

  updateSegment({
    ...segment,
  });

  yield setErrors([]);
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { error, success } = yield {
    type: 'SAVE_SEGMENT',
    segment,
  };

  if (success) {
    MailPoet.trackEvent(
      'Segments > Template selected',
      {
        'Segment name': segmentTemplate.name,
        'Segment slug': segmentTemplate.slug,
        'Segment category': segmentTemplate.category,
      },
      { send_immediately: true },
      () => {
        window.location.href = `admin.php?page=mailpoet-segments#${ROUTES.EDIT_DYNAMIC_SEGMENT}/${segment.id}`;
      },
    );
  } else {
    yield setErrors(error as string[]);
  }

  MailPoet.Modal.loading(false);
}

export function setPreviousPage(data: string): SetPreviousPageActionType {
  return {
    type: Actions.SET_PREVIOUS_PAGE,
    previousPage: data,
  };
}

export async function loadDynamicSegments() {
  let data: DynamicSegment[] = [];

  await MailPoet.Ajax.post({
    api_version: 'v1',
    endpoint: 'dynamic_segments',
    action: 'listing',
    data: {
      offset: 0,
      limit: 20,
      filter: {},
      search: '',
      sort_by: 'name',
      sort_order: 'desc',
    },
  })
    .done((response) => {
      data = response.data || [];
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        // TODO: handle errors
      }
    });

  return {
    type: 'SET_DYNAMIC_SEGMENTS',
    dynamicSegments: data,
  } as const;
}
