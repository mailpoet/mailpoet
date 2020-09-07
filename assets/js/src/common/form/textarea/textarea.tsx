import React, { TextareaHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = TextareaHTMLAttributes<HTMLTextAreaElement> & {
  dimension?: 'small',
  isFullWidth?: boolean,
};

const Textarea = ({
  dimension,
  isFullWidth,
  ...attributes
}: Props) => (
  <div
    className={
      classnames(
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
  </div>
);

export default Textarea;
