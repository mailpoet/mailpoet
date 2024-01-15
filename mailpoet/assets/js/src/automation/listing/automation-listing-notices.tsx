import { useEffect } from 'react';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import { getQueryArg, removeQueryArgs } from '@wordpress/url';

export const LISTING_NOTICES = {
  automationDeleted: 'deleted',
  automationHadBeenDeleted: 'had-been-deleted',
} as const;

export function useAutomationListingNotices(): void {
  const { createNotice } = dispatch(noticesStore);

  useEffect(() => {
    const notice = getQueryArg(window.location.href, 'notice');

    if (notice === LISTING_NOTICES.automationDeleted) {
      createNotice(
        'success',
        __('1 automation moved to the Trash.', 'mailpoet'),
      );
    } else if (notice === LISTING_NOTICES.automationHadBeenDeleted) {
      createNotice(
        'error',
        __(
          'You cannot edit this automation because it is in the Trash.',
          'mailpoet',
        ),
      );
    }

    const urlWithoutNotices = removeQueryArgs(window.location.href, 'notice');
    window.history.replaceState('', '', urlWithoutNotices);
  }, [createNotice]);
}
