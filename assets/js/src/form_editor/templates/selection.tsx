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
  <div className="template-selection">
    <Heading level={1}>{MailPoet.I18n.t('heading')}</Heading>
    <ol>
      <li>
        <a href={formEditorUrl}>
          {MailPoet.I18n.t('blankTemplate')}
        </a>
      </li>
      {templates.map((template) => (
        <li
          key={template.id}
        >
          <a href={`${formEditorUrl}${template.id}`}>
            {template.name}
          </a>
        </li>
      ))}
    </ol>
  </div>
);
