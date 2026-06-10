import { __ } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

type UseScheduledDate = {
  scheduledDate: string | null;
  isScheduled: boolean;
  formattedDate: string;
  setScheduledDate: (date: string | null) => void;
};

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
  const settings = getSettings();

  const setScheduledDate = (date: string | null) => {
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
          scheduled_at: date,
        },
      },
    );
  };

  const formattedDate = scheduledDate
    ? dateI18n(
        settings.formats.datetime,
        scheduledDate,
        settings.timezone.string,
      )
    : __('Immediately', 'mailpoet');

  return {
    scheduledDate,
    isScheduled: Boolean(scheduledDate),
    formattedDate,
    setScheduledDate,
  };
}
