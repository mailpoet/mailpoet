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
} from '../types';
import { storeName } from './constants';

export function setSegment(segment: AnyFormItem): SetSegmentActionType {
  return {
    type: Actions.SET_SEGMENT,
    segment,
  };
}

function unsetSegment(): ActionType {
  return {
    type: Actions.UNSET_SEGMENT,
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
  yield unsetSegment();
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

export function* handleSave(segmentId?: number): Generator<{
  type: string;
  segment?: AnyFormItem;
}> {
  const segment = select(storeName).getSegment();
  yield setErrors([]);
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { error, success } = yield {
    type: 'SAVE_SEGMENT',
    segment,
  };

  if (success) {
    window.location.href = 'admin.php?page=mailpoet-segments#/segments';

    if (segmentId !== undefined) {
      messages.onUpdate();
    } else {
      messages.onCreate(segment);
    }
  } else {
    yield setErrors(error as string[]);
  }
}

export function* createFromTemplate(): Generator<{
  type: string;
  segment?: Segment;
}> {
  MailPoet.Modal.loading(true);
  const segment = select(storeName).getSegment();
  segment.force_creation = true; // create segment with a random name if one with the same name already exists
  yield setErrors([]);
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { error, success } = yield {
    type: 'SAVE_SEGMENT',
    segment,
  };

  if (success) {
    window.location.href = `admin.php?page=mailpoet-segments#${ROUTES.EDIT_DYNAMIC_SEGMENT}/${segment.id}`;
  } else {
    yield setErrors(error as string[]);
  }

  MailPoet.Modal.loading(false);
}
