import { getQueryArg } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { Notice } from '../../notices/notice';

export function WorkflowListingNotices(): JSX.Element {
  const workflowDeleted = parseInt(
    getQueryArg(window.location.href, 'mailpoet-workflow-deleted') as string,
    10,
  );
  if (!workflowDeleted) {
    return null;
  }
  return (
    <Notice type="success" closable timeout={false}>
      <p>{__('1 workflow moved to the Trash.', 'mailpoet')}</p>
    </Notice>
  );
}
