import classnames from 'classnames';
import { Tooltip } from 'common/tooltip/tooltip';
import { Button } from 'common/button/button';
import { useRef } from 'react';
import { copy } from '@wordpress/icons';

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
          <Button
            iconStart={copy}
            variant="secondary"
            onClick={performActionOnClick}
            dataTip
            dataFor={props.name}
          />
          <Tooltip id={props.name} place="top">
            <span> {tooltip} </span>
          </Tooltip>
        </>
      )}
    </div>
  );
}

export { DomainKeyComponent };
