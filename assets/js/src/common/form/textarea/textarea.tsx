import React, { TextareaHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = TextareaHTMLAttributes<HTMLTextAreaElement> & {
  customLabel?: string,
  dimension?: 'small',
  isFullWidth?: boolean,
};

const Textarea = ({
  className,
  customLabel,
  dimension,
  isFullWidth,
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
  </div>
);

export default Textarea;
