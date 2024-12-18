import { NewsLetter } from '../../common/newsletter';

export enum NewsletterActionTypes {
  START_STANDARD_REQUEST = 'START_STANDARD_REQUEST',
  RECEIVE_STANDARD_NEWSLETTERS = 'RECEIVE_STANDARD_NEWSLETTERS',
  FINISH_STANDARD_REQUEST = 'FINISH_STANDARD_REQUEST',
  START_POST_NOTIFICATION_REQUEST = 'START_POST_NOTIFICATION_REQUEST',
  RECEIVE_POST_NOTIFICATIONS = 'RECEIVE_POST_NOTIFICATIONS',
  FINISH_POST_NOTIFICATION_REQUEST = 'FINISH_POST_NOTIFICATION_REQUEST',
  START_RE_ENGAGEMENT_REQUEST = 'START_RE_ENGAGEMENT_REQUEST',
  RECEIVE_RE_ENGAGEMENT = 'RECEIVE_RE_ENGAGEMENT',
  FINISH_RE_ENGAGEMENT_REQUEST = 'FINISH_RE_ENGAGEMENT_REQUEST',
  RECEIVE_ERROR = 'RECEIVE_ERROR',
  RECEIVE_META = 'RECEIVE_META',
  DUPLICATE_NEWSLETTER_REQUEST_START = 'DUPLICATE_NEWSLETTER_REQUEST_START',
  DUPLICATE_NEWSLETTER_REQUEST_SUCCESS = 'DUPLICATE_NEWSLETTER_REQUEST_SUCCESS',
  DUPLICATE_NEWSLETTER_REQUEST_FINISH = 'DUPLICATE_NEWSLETTER_REQUEST_FINISH',
}

export interface Meta {
  count: number;
  filters: {
    segment: {
      label: string;
      value: string | number;
    }[];
  };
  groups: {
    name: string;
    label: string;
    count: number;
  }[];
  mta_log: {
    sent: any[];
    started: number;
    status: string | null;
    retry_attempt: number | null;
    retry_at: string | null;
    error: string | null;
    transactional_email_last_error_at: string | null;
    transactional_email_error_count: number | null;
  };
  mta_method: string;
  cron_accessible: boolean;
  current_time: string;
}

export interface NewsletterState {
  newsletters: {
    standard: NewsLetter[];
    postNotifications: NewsLetter[];
    reEngagements: NewsLetter[];
  };
  meta: Meta | {};
  isLoading: {
    standard: boolean;
    postNotification: boolean;
    reEngagement: boolean;
    duplication: boolean;
  };
  errors: string[];
  currentNewsletterType: string;
}

// Action Interfaces
export interface StartStandardRequestAction {
  type: NewsletterActionTypes.START_STANDARD_REQUEST;
}

export interface ReceiveStandardNewslettersAction {
  type: NewsletterActionTypes.RECEIVE_STANDARD_NEWSLETTERS;
  response: NewsLetter[];
}

export interface FinishStandardRequestAction {
  type: NewsletterActionTypes.FINISH_STANDARD_REQUEST;
}

export interface StartPostNotificationRequestAction {
  type: NewsletterActionTypes.START_POST_NOTIFICATION_REQUEST;
}

export interface ReceivePostNotificationsAction {
  type: NewsletterActionTypes.RECEIVE_POST_NOTIFICATIONS;
  response: NewsLetter[];
}

export interface FinishPostNotificationsRequestAction {
  type: NewsletterActionTypes.FINISH_POST_NOTIFICATION_REQUEST;
}

export interface StartReEngagementRequestAction {
  type: NewsletterActionTypes.START_RE_ENGAGEMENT_REQUEST;
}

export interface ReceiveReEngagementAction {
  type: NewsletterActionTypes.RECEIVE_RE_ENGAGEMENT;
  response: NewsLetter[];
}

export interface FinishReEngagementRequestAction {
  type: NewsletterActionTypes.FINISH_RE_ENGAGEMENT_REQUEST;
}

export interface ReceiveErrorAction {
  type: NewsletterActionTypes.RECEIVE_ERROR;
  error: string;
}

export interface ReceiveMetaAction {
  type: NewsletterActionTypes.RECEIVE_META;
  response: Meta;
}

export interface StartDuplicateRequestAction {
  type: NewsletterActionTypes.DUPLICATE_NEWSLETTER_REQUEST_START;
}

export interface ReceiveDuplicatedNewsletterAction {
  type: NewsletterActionTypes.DUPLICATE_NEWSLETTER_REQUEST_SUCCESS;
  payload: NewsLetter;
}

export interface FinishDuplicateRequestAction {
  type: NewsletterActionTypes.DUPLICATE_NEWSLETTER_REQUEST_FINISH;
}

// Union Type for All Actions
export type NewsletterActions =
  | StartStandardRequestAction
  | ReceiveStandardNewslettersAction
  | FinishStandardRequestAction
  | StartPostNotificationRequestAction
  | ReceivePostNotificationsAction
  | FinishPostNotificationsRequestAction
  | StartReEngagementRequestAction
  | ReceiveReEngagementAction
  | FinishReEngagementRequestAction
  | ReceiveErrorAction
  | ReceiveMetaAction
  | StartDuplicateRequestAction
  | ReceiveDuplicatedNewsletterAction
  | FinishDuplicateRequestAction;