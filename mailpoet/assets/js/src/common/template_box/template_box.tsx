import { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { Heading } from 'common/typography/heading/heading';
import { Button } from 'common/button/button';

type Props = {
  label: string;
  onSelect: () => void;
  children: ReactNode;
  onDelete?: () => void;
  automationId?: string;
  className?: string;
};

export function TemplateBox({
  label,
  onSelect,
  children,
  onDelete,
  automationId,
  className,
}: Props) {
  return (
    <div
      className={`mailpoet-template ${className}`}
      data-automation-id="select_template_box"
    >
      {children}
      <div className="mailpoet-template-info">
        <Heading level={5} title={label}>
          {label}
        </Heading>
        <div>
          {onDelete && (
            <Button variant="destructive" onClick={onDelete}>
              {__('Delete', 'mailpoet')}
            </Button>
          )}
          <Button automationId={automationId} onClick={onSelect}>
            {__('Select', 'mailpoet')}
          </Button>
        </div>
      </div>
    </div>
  );
}
