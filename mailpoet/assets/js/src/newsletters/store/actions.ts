export * from './thunks';

import { 
  NewsletterActionTypes as Actions,
  Meta,
  StartStandardRequestAction,
  ReceiveStandardNewslettersAction,
  FinishStandardRequestAction,
  StartPostNotificationRequestAction,
  ReceivePostNotificationsAction,
  FinishPostNotificationsRequestAction,
  StartReEngagementRequestAction,
  ReceiveReEngagementAction,
  FinishReEngagementRequestAction,
  ReceiveErrorAction,
  ReceiveMetaAction,
  StartDuplicateRequestAction,
  ReceiveDuplicatedNewsletterAction,
  FinishDuplicateRequestAction,
} from './types';
import { NewsLetter } from '../../common/newsletter';


export function startStandardRequest(): StartStandardRequestAction {
  return {
    type: Actions.START_STANDARD_REQUEST,
  };
}

export function receiveStandardNewsletters(newsletters: NewsLetter[]): ReceiveStandardNewslettersAction {
  return {
    type: Actions.RECEIVE_STANDARD_NEWSLETTERS,
    response: newsletters,
  };
}

export function finishStandardRequest(): FinishStandardRequestAction {
  return {
    type: Actions.FINISH_STANDARD_REQUEST,
  };
}

export function startPostNotificationRequest(): StartPostNotificationRequestAction {
  return {
    type: Actions.START_POST_NOTIFICATION_REQUEST,
  };
}

export function receivePostNotifications(newsletters: NewsLetter[]): ReceivePostNotificationsAction {
  return {
    type: Actions.RECEIVE_POST_NOTIFICATIONS,
    response: newsletters,
  };
}

export function finishPostNotificationsRequest(): FinishPostNotificationsRequestAction {
  return {
    type: Actions.FINISH_POST_NOTIFICATION_REQUEST,
  };
}

export function startReEngagementRequest(): StartReEngagementRequestAction {
  return {
    type: Actions.START_RE_ENGAGEMENT_REQUEST,
  };
}

export function receiveReEngagement(newsletters: NewsLetter[]): ReceiveReEngagementAction {
  return {
    type: Actions.RECEIVE_RE_ENGAGEMENT,
    response: newsletters,
  };
}

export function finishReEngagementRequest(): FinishReEngagementRequestAction {
  return {
    type: Actions.FINISH_RE_ENGAGEMENT_REQUEST,
  };
}

export function receiveError(error: string): ReceiveErrorAction { 
  return {
    type: Actions.RECEIVE_ERROR,
    error,
  };
}

export function receiveMeta(meta: Meta): ReceiveMetaAction {
  return {
    type: Actions.RECEIVE_META,
    response: meta,
  };
}

export function startDuplicateRequest(): StartDuplicateRequestAction {
  return {
    type: Actions.DUPLICATE_NEWSLETTER_REQUEST_START,
  };
}

export function receiveDuplicatedNewsletter(newsletter: NewsLetter): ReceiveDuplicatedNewsletterAction {
  return {
    type: Actions.DUPLICATE_NEWSLETTER_REQUEST_SUCCESS,
    payload: newsletter,
  };
}

export function finishDuplicateRequest(): FinishDuplicateRequestAction {
  return {
    type: Actions.DUPLICATE_NEWSLETTER_REQUEST_FINISH,
  };
}
