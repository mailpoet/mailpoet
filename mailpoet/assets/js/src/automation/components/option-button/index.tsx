import { ReactNode, useEffect, useRef, useState } from 'react';
import { Button } from '@wordpress/components';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { ReactNodeArray } from 'prop-types';

type OptionButtonPropType = {
  variant: Button.ButtonVariant;
  children: ReactNodeArray | ReactNode;
  title: string;
  onClick: () => void;
};
export function OptionButton({
  children,
  title,
  onClick,
  variant,
}: OptionButtonPropType): JSX.Element {
  const [isOpen, setIsOpen] = useState(false);
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const element = ref?.current;

    // Any mouse click outside the element will close the options.
    function handleCloseOnClick(event: Event) {
      if (element && !element.contains(event.target as Node | null)) {
        setIsOpen(false);
      }
    }

    // Pressing escape will close the options.
    function handleCloseOnEsc(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleCloseOnClick, {
      capture: true,
    });
    document.addEventListener('keyup', handleCloseOnEsc, { capture: true });
    return () => {
      document.removeEventListener('mousedown', handleCloseOnClick, {
        capture: true,
      });
      document.removeEventListener('keyup', handleCloseOnEsc, {
        capture: true,
      });
    };
  }, [ref]);

  return (
    <div className="mailpoet-option-button" ref={ref}>
      <header>
        <Button
          variant={variant}
          className="mailpoet-option-button-main"
          onClick={onClick}
        >
          {title}
        </Button>
        {(children as ReactNodeArray).length > 0 && (
          <Button
            className="mailpoet-option-button-opener"
            variant={variant}
            onClick={() => setIsOpen(!isOpen)}
            icon={isOpen ? chevronUp : chevronDown}
          />
        )}
      </header>
      <div
        className={
          isOpen
            ? 'mailpoet-option-button-options isOpen'
            : 'mailpoet-option-button-options'
        }
      >
        {children}
      </div>
    </div>
  );
}
