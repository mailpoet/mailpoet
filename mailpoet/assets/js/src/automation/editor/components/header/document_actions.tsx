import { ComponentProps, ComponentType, Ref } from 'react';
import {
  __experimentalText as Text,
  Button,
  Dropdown as WpDropdown,
  VisuallyHidden,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';
import { storeName } from '../../store';
import { WorkflowStatus } from '../../../listing/workflow';

// See: https://github.com/WordPress/gutenberg/blob/eff0cab2b3181c004dbd15398e570ecec28a3726/packages/edit-site/src/components/header/document-actions/index.js

// property "popoverProps" is missing in WpDropdown type definition
const Dropdown: ComponentType<
  ComponentProps<typeof WpDropdown> & {
    popoverProps?: { anchorRef?: Ref<HTMLElement> };
  }
> = WpDropdown;

export function DocumentActions({ children }): JSX.Element {
  const { workflowName, workflowStatus, showIconLabels } = useSelect(
    (select) => ({
      workflowName: select(storeName).getWorkflowData().name,
      workflowStatus: select(storeName).getWorkflowData().status,
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
    }),
    [],
  );

  // The title ref is passed to the popover as the anchorRef so that the dropdown
  // is centered over the whole title area rather than just one part of it.
  const titleRef = useRef();

  let chipClass = 'mailpoet-automation-editor-chip-gray';
  if (workflowStatus === WorkflowStatus.ACTIVE) {
    chipClass = 'mailpoet-automation-editor-chip-success';
  } else if (workflowStatus === WorkflowStatus.DEACTIVATING) {
    chipClass = 'mailpoet-automation-editor-chip-danger';
  }

  return (
    <div className="edit-site-document-actions has-secondary-label">
      <div ref={titleRef} className="edit-site-document-actions__title-wrapper">
        {children && (
          <Dropdown
            popoverProps={{
              anchorRef: titleRef.current,
            }}
            position="bottom center"
            renderToggle={({ isOpen, onToggle }) => (
              <>
                <a
                  className="mailpoet-automation-editor-dropdown-toggle-link"
                  href="#"
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
                    {workflowName}
                  </Text>

                  <Text
                    size="body"
                    className={`edit-site-document-actions__secondary-item ${chipClass}`}
                  >
                    {workflowStatus === WorkflowStatus.ACTIVE &&
                      __('Active', 'mailpoet')}
                    {workflowStatus === WorkflowStatus.DEACTIVATING &&
                      __('Deactivating', 'mailpoet')}
                    {workflowStatus === WorkflowStatus.DRAFT &&
                      __('Draft', 'mailpoet')}
                  </Text>
                </a>
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
            contentClassName="edit-site-document-actions__info-dropdown"
            renderContent={children}
          />
        )}
      </div>
    </div>
  );
}
