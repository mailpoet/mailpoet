import { InputHTMLAttributes } from 'react';
import classnames from 'classnames';
import Tooltip from 'common/tooltip/tooltip';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  customLabel?: string;
  dimension?: 'small';
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  tooltip?: string;
};

function Input({
  className,
  customLabel,
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  tooltip,
  ...attributes
}: Props) {
  return (
    <div
      className={classnames(className, 'mailpoet-form-input', {
        [`mailpoet-form-input-${dimension}`]: dimension,
        'mailpoet-disabled': attributes.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
    >
      {iconStart}
      <input {...attributes} />
      {customLabel && (
        <div className="mailpoet-form-input-label">{customLabel}</div>
      )}
      {tooltip && (
        <>
          <span className="mailpoet-form-tooltip-holder">
            <span
              className="mailpoet-form-tooltip-icon"
              data-tip
              data-for={attributes.name}
            />
          </span>
          <Tooltip place="right" multiline id={attributes.name}>
            {tooltip}
          </Tooltip>
        </>
      )}
      {iconEnd}
    </div>
  );
}

export default Input;
