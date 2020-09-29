import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import Button from 'common/button/button';
import { TemplateType } from 'form_editor/templates/store/types';

type Props = {
  template: TemplateType,
  onSelect: (id: string) => any,
}
const TemplateBox = ({ template, onSelect }: Props) => (
  <div className="mailpoet-template mailpoet-form-template" data-automation-id="select_template_box">
    <div className="mailpoet-template-thumbnail">
      <img src={template.thumbnail} alt={template.name} />
    </div>
    <div className="mailpoet-template-info">
      <Heading level={5} title={template.name}>{template.name}</Heading>
      <div>
        <Button dimension="small" automationId={`select_template_${template.id}`} onClick={() => onSelect(template.id)}>
          {MailPoet.I18n.t('select')}
        </Button>
      </div>
    </div>
  </div>
);

export default TemplateBox;
