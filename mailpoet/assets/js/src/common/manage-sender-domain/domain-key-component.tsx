import classnames from 'classnames';
import { useRef } from 'react';
import { Button, Tooltip } from '@wordpress/components';
import { copy, Icon } from '@wordpress/icons';

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
        <Tooltip text={tooltip} delay={0} placement="top">
          <Button variant="tertiary" onClick={performActionOnClick}>
            <Icon icon={copy} size={20} />
          </Button>
        </Tooltip>
      )}
    </div>
  );
}

export { DomainKeyComponent };
