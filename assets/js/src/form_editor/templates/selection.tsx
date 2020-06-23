import React from 'react';
import Heading from 'common/typography/heading/heading';
import MailPoet from 'mailpoet';

type Template = {
  id: string
  name: string
};

type Props = {
  templates: Array<Template>
  formEditorUrl: string
};

export default ({ formEditorUrl, templates }: Props) => (
  <div className="template-selection" data-automation-id="template_selection_list">
    <Heading level={1}>{MailPoet.I18n.t('heading')}</Heading>
    <ol>
      <li
        data-automation-id="blank_template"
      >
        <a href={formEditorUrl}>
          {MailPoet.I18n.t('blankTemplate')}
        </a>
      </li>
      {templates.map((template, index) => (
        <li
          key={template.id}
        >
          <a href={`${formEditorUrl}${template.id}`} data-automation-id={`template_index_${index}`}>
            {template.name}
          </a>
        </li>
      ))}
    </ol>
  </div>
);
