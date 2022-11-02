import { __ } from '@wordpress/i18n';

export function EmptyWorkflow(): JSX.Element {
  return (
    <div className="mailpoet-automation-editor-empty-workflow">
      {__('No automation data.', 'mailpoet')}
    </div>
  );
}
