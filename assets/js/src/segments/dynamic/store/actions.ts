import { ChangeEvent } from 'react';
import MailPoet from 'mailpoet';

import {
  Actions,
  AnyFormItem,
  SetSegmentActionType,
} from '../types';

export function setSegment(segment: AnyFormItem): SetSegmentActionType {
  return {
    type: Actions.SET_SEGMENT,
    segment,
  };
}

export function updateSegment(data: AnyFormItem): SetSegmentActionType {
  return {
    type: Actions.UPDATE_SEGMENT,
    segment: data,
  };
}

export function updateSegmentFromEvent(
  propertyName: string,
  event: ChangeEvent<HTMLSelectElement | HTMLInputElement>
): SetSegmentActionType {
  return {
    type: Actions.UPDATE_SEGMENT,
    segment: {
      [propertyName]: event.target.value,
    },
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
  const { res, success } = yield ({
    type: 'LOAD_SEGMENT',
    segmentId,
  });
  if (!success || res.is_plugin_missing) {
    window.location.href = '/segments';
  }
  yield setSegment(res);
  MailPoet.Modal.loading(false);
}
