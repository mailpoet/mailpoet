import { getQueryArg, removeQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { Notice } from '../../notices/notice';

export const LISTING_NOTICE_PARAMETERS = {
  automationHadBeenDeleted: 'mailpoet-had-been-deleted',
  automationDeleted: 'mailpoet-automation-deleted',
};

export function AutomationListingNotices(): JSX.Element {
  const automationHadBeenDeleted = parseInt(
    getQueryArg(
      window.location.href,
      LISTING_NOTICE_PARAMETERS.automationHadBeenDeleted,
    ) as string,
    10,
  );
  const automationDeleted = parseInt(
    getQueryArg(
      window.location.href,
      LISTING_NOTICE_PARAMETERS.automationDeleted,
    ) as string,
    10,
  );

  if (
    Number.isNaN(automationHadBeenDeleted) &&
    Number.isNaN(automationDeleted)
  ) {
    return null;
  }

  const urlWithoutNotices = removeQueryArgs(
    window.location.href,
    ...Object.values(LISTING_NOTICE_PARAMETERS),
  );
  window.history.pushState('', '', urlWithoutNotices);
  if (automationHadBeenDeleted) {
    return (
      <Notice type="error" closable timeout={false}>
        <p>
          {__(
            'You cannot edit this automation because it is in the Trash.',
            'mailpoet',
          )}
        </p>
      </Notice>
    );
  }
  if (automationDeleted) {
    return (
      <Notice type="success" closable timeout={false}>
        <p>{__('1 automation moved to the Trash.', 'mailpoet')}</p>
      </Notice>
    );
  }
  return null;
}
