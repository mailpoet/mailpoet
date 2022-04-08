import { ChangeEvent } from 'react';
import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';

import {
  Actions,
  AnyFormItem,
  SetSegmentActionType,
  SetErrorsActionType,
  SetSegmentFilerActionType,
  SubscriberCount,
  SetSubscriberCountActionType,
} from '../types';

export function setSegment(segment: AnyFormItem): SetSegmentActionType {
  return {
    type: Actions.SET_SEGMENT,
    segment,
  };
}

export function setErrors(errors: string[]): SetErrorsActionType {
  return {
    type: Actions.SET_ERRORS,
    errors,
  };
}

export function updateSegment(data: AnyFormItem): SetSegmentActionType {
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

export function* pageLoaded(segmentId?: number): Generator<{
  type: string;
  segmentId?: number;
}> {
  if (segmentId === undefined) return; // new segment no need to load anything
  MailPoet.Modal.loading(true);

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore -- I don't know how to configure typescript to understand this
  const { res, success } = yield {
    type: 'LOAD_SEGMENT',
    segmentId,
  };
  if (!success || res.is_plugin_missing) {
    window.location.href = 'admin.php?page=mailpoet-segments#/segments';
  }
  yield setSegment(res as AnyFormItem);
  MailPoet.Modal.loading(false);
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
  const segment = select('mailpoet-dynamic-segments-form').getSegment();
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
