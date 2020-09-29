import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import Button from 'common/button/button';

type Props = {
  label: string,
  onSelect: () => void,
  children: React.ReactNode,
  onDelete?: () => void,
  automationId?: string,
  className?: string
}

const TemplateBox = ({
  label,
  onSelect,
  children,
  onDelete,
  automationId,
  className,
}: Props) => (
  <div className={`mailpoet-template ${className}`} data-automation-id="select_template_box">
    {children}
    <div className="mailpoet-template-info">
      <Heading level={5} title={label}>{label}</Heading>
      <div>
        {onDelete && (
          <Button dimension="small" variant="light" onClick={onDelete}>
            {MailPoet.I18n.t('delete')}
          </Button>
        )}
        <Button dimension="small" automationId={automationId} onClick={onSelect}>
          {MailPoet.I18n.t('select')}
        </Button>
      </div>
    </div>
  </div>
);

export default TemplateBox;
