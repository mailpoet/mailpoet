import { Dropdown, TextControl } from '@wordpress/components';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { edit, Icon } from '@wordpress/icons';
import { PlainBodyTitle } from './plain-body-title';
import { TitleActionButton } from './title-action-button';

type Props = {
  currentName: string;
  defaultName: string;
  update: (value: string) => void;
};

type ContentProps = Props & {
  onClose: () => void;
  dropdownRef: React.RefObject<HTMLDivElement>;
};

/**
 * WordPress Popover intentionally skips closing when activeElement is
 * document.body (happens when clicking non-focusable areas like the canvas
 * background). This component adds a mousedown listener to cover that gap.
 */
function StepNameContent({
  currentName,
  defaultName,
  update,
  onClose,
  dropdownRef,
}: ContentProps): JSX.Element {
  const contentRef = useRef<HTMLDivElement>(null);

  const handleClickOutside = useCallback(
    (event: MouseEvent) => {
      const target = event.target as Node;
      const popoverEl = contentRef.current?.closest('.components-popover');
      const dropdownEl = dropdownRef.current;

      if (
        (!popoverEl || !popoverEl.contains(target)) &&
        (!dropdownEl || !dropdownEl.contains(target))
      ) {
        onClose();
      }
    },
    [onClose, dropdownRef],
  );

  useEffect(() => {
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [handleClickOutside]);

  return (
    <div ref={contentRef}>
      <TextControl
        label={__('Step name', 'mailpoet')}
        className="mailpoet-step-name-input"
        placeholder={defaultName}
        value={currentName}
        onChange={update}
        help={__(
          'Give the automation step a name that indicates its purpose. E.g "Abandoned cart recovery". This name will be displayed only to you and not to the clients.',
          'mailpoet',
        )}
      />
    </div>
  );
}

export function StepName({
  currentName,
  defaultName,
  update,
}: Props): JSX.Element {
  const dropdownRef = useRef<HTMLDivElement>(null);

  return (
    <div ref={dropdownRef}>
      <Dropdown
        className="mailpoet-step-name-dropdown"
        contentClassName="mailpoet-step-name-popover"
        popoverProps={{
          placement: 'bottom-end',
        }}
        renderToggle={({ isOpen, onToggle }) => (
          <PlainBodyTitle
            title={currentName.length > 0 ? currentName : defaultName}
          >
            <TitleActionButton
              onClick={onToggle}
              aria-expanded={isOpen}
              aria-label={__('Edit step name', 'mailpoet')}
            >
              <Icon icon={edit} size={16} />
            </TitleActionButton>
          </PlainBodyTitle>
        )}
        renderContent={({ onClose }) => (
          <StepNameContent
            currentName={currentName}
            defaultName={defaultName}
            update={update}
            onClose={onClose}
            dropdownRef={dropdownRef}
          />
        )}
      />
    </div>
  );
}
