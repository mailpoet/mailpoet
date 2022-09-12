import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { WorkflowTemplate } from '../config';
import { useMutation } from '../../api';
import { MailPoet } from '../../../mailpoet';
import { Notice } from '../../../notices/notice';

type TemplateListItemProps = {
  template: WorkflowTemplate;
};
export function TemplateListItem({
  template,
}: TemplateListItemProps): JSX.Element {
  const [createWorkflowFromTemplate, { loading, error, data }] = useMutation(
    'workflows/create-from-template',
    {
      method: 'POST',
      body: JSON.stringify({
        slug: template.slug,
      }),
    },
  );

  if (!error && data) {
    window.location.href = addQueryArgs(MailPoet.urls.automationEditor, {
      id: data.data.id,
    });
  }

  let notice = null;
  if (error) {
    notice = (
      <Notice type="error" closable timeout={false}>
        <p>
          {error.data
            ? error.data.message
            : __('Could not create workflow.', 'mailpoet')}
        </p>
      </Notice>
    );
  }

  return (
    <li className="mailpoet-template-list-item">
      {notice}
      <Button
        isBusy={loading}
        onClick={() => {
          void createWorkflowFromTemplate();
        }}
      >
        <h2>{template.name} →</h2>
        <p>{template.description}</p>
      </Button>
    </li>
  );
}
