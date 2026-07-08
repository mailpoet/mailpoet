import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { MailPoet } from 'mailpoet';
import {
  SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
  SCHEDULE_MODE_WEBSITE_TIME,
  ScheduleMode,
  getScheduleMode,
  getScheduleModeOptionChanges,
  getTomorrowLocalDate,
} from 'common/newsletter-schedule-mode';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

type UseScheduledDate = {
  scheduledDate: string | null;
  isScheduled: boolean;
  formattedDate: string;
  setScheduledDate: (date: string | null) => void;
  scheduleMode: ScheduleMode;
  scheduledLocalDate: string | null;
  scheduledLocalTime: string | null;
  isTimezoneSchedulingAvailable: boolean;
  setScheduleMode: (mode: ScheduleMode) => void;
  setScheduledLocalDate: (date: string) => void;
  setScheduledLocalTime: (time: string) => void;
};

function editMailpoetData(changes: Record<string, string | null>): void {
  const postId = select(editorStore).getCurrentPostId();
  const currentPostType = MAILPOET_EMAIL_POST_TYPE;

  const editedPost = select(coreDataStore).getEditedEntityRecord(
    'postType',
    currentPostType,
    postId,
  );

  // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
  const mailpoetData = editedPost?.mailpoet_data || {};
  void dispatch(coreDataStore).editEntityRecord(
    'postType',
    currentPostType,
    postId,
    {
      mailpoet_data: {
        ...mailpoetData,
        ...changes,
      },
    },
  );
}

/**
 * Reads and writes the email's scheduled send time from `mailpoet_data`.
 * A `null` date means the email is sent immediately.
 */
export function useScheduledDate(): UseScheduledDate {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    'mailpoet_data',
  );

  const scheduledDate = (mailpoetEmailData?.scheduled_at as string) || null;
  const isTimezoneSchedulingAvailable = MailPoet.FeaturesController.isSupported(
    MailPoet.FeaturesController.FEATURE_SEND_BY_TIMEZONE,
  );
  const scheduleMode = isTimezoneSchedulingAvailable
    ? getScheduleMode(mailpoetEmailData?.schedule_mode as string)
    : SCHEDULE_MODE_WEBSITE_TIME;
  const isSubscriberTimezoneMode =
    scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;
  const scheduledLocalDate =
    (mailpoetEmailData?.scheduled_local_date as string) || null;
  const scheduledLocalTime =
    (mailpoetEmailData?.scheduled_local_time as string) || null;
  const settings = getSettings();

  const setScheduledDate = (date: string | null) => {
    editMailpoetData({ scheduled_at: date });
  };

  const setScheduleMode = (mode: ScheduleMode) => {
    const changes = getScheduleModeOptionChanges(
      mode,
      {
        scheduledLocalDate: scheduledLocalDate || undefined,
        scheduledLocalTime: scheduledLocalTime || undefined,
      },
      getTomorrowLocalDate(),
    );
    editMailpoetData({
      schedule_mode: changes.scheduleMode,
      scheduled_local_date: changes.scheduledLocalDate,
      scheduled_local_time: changes.scheduledLocalTime,
    });
  };

  const setScheduledLocalDate = (date: string) => {
    editMailpoetData({ scheduled_local_date: date });
  };

  const setScheduledLocalTime = (time: string) => {
    editMailpoetData({ scheduled_local_time: time });
  };

  let formattedDate;
  if (isSubscriberTimezoneMode) {
    formattedDate = sprintf(
      // translators: %1$s is a date, %2$s is a time. Example: "2026-07-10 at 08:00 in subscriber’s time zone".
      __('%1$s at %2$s in subscriber’s time zone', 'mailpoet'),
      scheduledLocalDate || '',
      (scheduledLocalTime || '').slice(0, 5),
    );
  } else {
    formattedDate = scheduledDate
      ? dateI18n(
          settings.formats.datetime,
          scheduledDate,
          settings.timezone.string,
        )
      : __('Immediately', 'mailpoet');
  }

  return {
    scheduledDate,
    isScheduled: isSubscriberTimezoneMode
      ? Boolean(scheduledLocalDate)
      : Boolean(scheduledDate),
    formattedDate,
    setScheduledDate,
    scheduleMode,
    scheduledLocalDate,
    scheduledLocalTime,
    isTimezoneSchedulingAvailable,
    setScheduleMode,
    setScheduledLocalDate,
    setScheduledLocalTime,
  };
}
