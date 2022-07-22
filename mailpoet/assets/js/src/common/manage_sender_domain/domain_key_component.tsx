import classnames from 'classnames';
import { Tooltip } from 'common/tooltip/tooltip';
import { useRef } from 'react';

const copyTextToClipboard = (value: string) => {
  if (!navigator.clipboard) {
    try {
      document.execCommand('copy');
    } catch (error) {
      // noop
    }
    return;
  }
  navigator.clipboard
    .writeText(value)
    .then()
    .catch(() => {
      // noop
    });
};

function DomainKeyComponent({ className = '', tooltip = '', ...props }) {
  const inputRef = useRef<HTMLInputElement>();

  const performActionOnClick = () => {
    inputRef.current?.focus();
    inputRef.current?.select();
    copyTextToClipboard(inputRef.current?.value);
  };

  return (
    <div className={classnames(className, 'mailpoet-form-input', {})}>
      <input ref={inputRef} onClick={performActionOnClick} {...props} />

      {tooltip && (
        <>
          <button
            className="mailpoet-form-tooltip-without-icon button-secondary"
            data-tip
            data-for={props.name}
            type="button"
            onClick={performActionOnClick}
          >
            <span className="dashicons dashicons-clipboard" />
          </button>
          <Tooltip id={props.name} place="top">
            <span> {tooltip} </span>
          </Tooltip>
        </>
      )}
    </div>
  );
}

export { DomainKeyComponent };
