import React, { TextareaHTMLAttributes } from 'react';
import classnames from 'classnames';
import Tooltip from 'common/tooltip/tooltip';

type Props = TextareaHTMLAttributes<HTMLTextAreaElement> & {
  customLabel?: string,
  dimension?: 'small',
  isFullWidth?: boolean,
  tooltip?: string,
};

const Textarea = ({
  className,
  customLabel,
  dimension,
  isFullWidth,
  tooltip,
  ...attributes
}: Props) => (
  <div
    className={
      classnames(
        className,
        'mailpoet-form-textarea',
        {
          [`mailpoet-form-textarea-${dimension}`]: dimension,
          'mailpoet-disabled': attributes.disabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    <textarea {...attributes} />
    {customLabel && <div className="mailpoet-form-input-label">{customLabel}</div>}
    {tooltip && (
      <>
        <span className="mailpoet-form-tooltip-holder">
          <span className="mailpoet-form-tooltip-icon" data-tip data-for={attributes.name} />
        </span>
        <Tooltip place="right" multiline id={attributes.name}>
          {tooltip}
        </Tooltip>
      </>
    )}
  </div>
);

export default Textarea;
