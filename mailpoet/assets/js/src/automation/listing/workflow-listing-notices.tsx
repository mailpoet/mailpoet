import { getQueryArg } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { Notice } from '../../notices/notice';

export function WorkflowListingNotices(): JSX.Element {
  const workflowHadBeenDeleted = parseInt(
    getQueryArg(window.location.href, 'mailpoet-had-been-deleted') as string,
    10,
  );
  const workflowDeleted = parseInt(
    getQueryArg(window.location.href, 'mailpoet-workflow-deleted') as string,
    10,
  );
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
