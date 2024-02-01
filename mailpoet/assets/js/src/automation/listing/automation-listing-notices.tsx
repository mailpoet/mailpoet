import { useEffect } from 'react';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { __, sprintf } from '@wordpress/i18n';
import { getQueryArg, removeQueryArgs } from '@wordpress/url';

export const LISTING_NOTICES = {
  automationDeleted: 'deleted',
  automationHadBeenDeleted: 'had-been-deleted',
} as const;

export function useAutomationListingNotices(): void {
  const { createNotice } = dispatch(noticesStore);

  useEffect(() => {
    const notice = getQueryArg(window.location.href, 'notice');
    const args = getQueryArg(window.location.href, 'notice-args') ?? [];
    const automationName = args[0] ?? 'Unknown';
    if (notice === LISTING_NOTICES.automationDeleted) {
      createNotice(
        'success',
        sprintf(
          __('Automation "%s" was moved to the trash.', 'mailpoet'),
          automationName,
        ),
      );
    } else if (notice === LISTING_NOTICES.automationHadBeenDeleted) {
      createNotice(
        'error',
        sprintf(
          __(
            'You cannot edit automation "%s" because it is in the trash.',
            'mailpoet',
          ),
          automationName,
        ),
      );
    }

    const urlWithoutNotices = removeQueryArgs(
      window.location.href,
      'notice',
      'notice-args',
    );
    window.history.replaceState('', '', urlWithoutNotices);
  }, [createNotice]);
}
