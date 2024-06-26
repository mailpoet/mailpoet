import {
  __experimentalText as Text,
  Button,
  Dropdown,
  VisuallyHidden,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';
import { storeName } from '../../store';
import { AutomationStatus } from '../../../components/status';

// See: https://github.com/WordPress/gutenberg/blob/eff0cab2b3181c004dbd15398e570ecec28a3726/packages/edit-site/src/components/header/document-actions/index.js

function DocumentActions({ children }): JSX.Element {
  const { automationName, automationStatus, showIconLabels } = useSelect(
    (select) => ({
      automationName: select(storeName).getAutomationData().name,
      automationStatus: select(storeName).getAutomationData().status,
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
    }),
    [],
  );

  // The title ref is passed to the popover as the anchorRef so that the dropdown
  // is centered over the whole title area rather than just one part of it.
  const titleRef = useRef();

  return (
    <div className="edit-site-document-actions has-secondary-label mailpoet-automation-editor-edit-site-document-actions">
      <div ref={titleRef} className="edit-site-document-actions__title-wrapper">
        {children && (
          <Dropdown
            popoverProps={{
              placement: 'bottom',
              anchor: titleRef.current,
            }}
            renderToggle={({ isOpen, onToggle }) => (
              <>
                <Button
                  className="mailpoet-automation-editor-dropdown-toggle-link"
                  onClick={onToggle}
                >
                  <Text
                    size="body"
                    className="edit-site-document-actions__title"
                    as="h1"
                  >
                    <VisuallyHidden as="span">
                      {__('Editing automation:', 'mailpoet')}
                    </VisuallyHidden>
                    {automationName}
                  </Text>

                  <AutomationStatus status={automationStatus} />
                </Button>
                <Button
                  className="edit-site-document-actions__get-info"
                  icon={chevronDown}
                  aria-expanded={isOpen}
                  aria-haspopup="true"
                  onClick={onToggle}
                  label={__('Change automation name', 'mailpoet')}
                >
                  {showIconLabels && __('Rename', 'mailpoet')}
                </Button>
              </>
            )}
            contentClassName="edit-site-document-actions__info-dropdown mailpoet-automation-editor-edit-site-document-actions__info-dropdown"
            renderContent={children}
          />
        )}
      </div>
    </div>
  );
}

DocumentActions.displayName = 'DocumentActions';
export { DocumentActions };
