import { getQueryArg, removeQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { Notice } from '../../notices/notice';

export const LISTING_NOTICE_PARAMETERS = {
  workflowHadBeenDeleted: 'mailpoet-had-been-deleted',
  workflowDeleted: 'mailpoet-workflow-deleted',
};

export function WorkflowListingNotices(): JSX.Element {
  const workflowHadBeenDeleted = parseInt(
    getQueryArg(
      window.location.href,
      LISTING_NOTICE_PARAMETERS.workflowHadBeenDeleted,
    ) as string,
    10,
  );
  const workflowDeleted = parseInt(
    getQueryArg(
      window.location.href,
      LISTING_NOTICE_PARAMETERS.workflowDeleted,
    ) as string,
    10,
  );

  if (Number.isNaN(workflowHadBeenDeleted) && Number.isNaN(workflowDeleted)) {
    return null;
  }

  const urlWithoutNotices = removeQueryArgs(
    window.location.href,
    ...Object.values(LISTING_NOTICE_PARAMETERS),
  );
  window.history.pushState('', '', urlWithoutNotices);
  if (workflowHadBeenDeleted) {
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
  if (workflowDeleted) {
    return (
      <Notice type="success" closable timeout={false}>
        <p>{__('1 workflow moved to the Trash.', 'mailpoet')}</p>
      </Notice>
    );
  }
  return null;
}
