import React from 'react';
import Heading from 'common/typography/heading/heading';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';

import { TemplateType } from './store/types';

export default () => {
  const templates: Array<TemplateType> = useSelect(
    (select) => select('mailpoet-form-editor-templates').getTemplates(),
    []
  );
  const selectTemplateFailed: boolean = useSelect(
    (select) => select('mailpoet-form-editor-templates').getSelectTemplateFailed(),
    []
  );
  const { selectTemplate } = useDispatch('mailpoet-form-editor-templates');
  return (
    <div className="template-selection" data-automation-id="template_selection_list">
      <Heading level={1}>{MailPoet.I18n.t('heading')}</Heading>
      {selectTemplateFailed && (<div className="mailpoet_error">Failed</div>)}
      <ol>
        <li>
          <Button
            isLink
            data-automation-id="blank_template"
            onClick={() => selectTemplate()}
          >
            {MailPoet.I18n.t('blankTemplate')}
          </Button>
        </li>
        {templates.map((template, index) => (
          <li key={template.id}>
            <Button
              isLink
              onClick={() => selectTemplate(template.id)}
              data-automation-id={`template_index_${index}`}
            >
              {template.name}
            </Button>
          </li>
        ))}
      </ol>
    </div>
  );
};
