import { ComponentProps, ComponentType, Ref } from 'react';
import {
  Dropdown as WpDropdown,
  Button,
  VisuallyHidden,
  __experimentalText as Text,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';
import { store } from '../../store';
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
      workflowName: select(store).getWorkflowData().name,
      workflowStatus: select(store).getWorkflowData().status,
      showIconLabels: select(store).isFeatureActive('showIconLabels'),
    }),
    [],
  );

  // The title ref is passed to the popover as the anchorRef so that the dropdown
  // is centered over the whole title area rather than just one part of it.
  const titleRef = useRef();

  return (
    <div className="edit-site-document-actions has-secondary-label">
      <div ref={titleRef} className="edit-site-document-actions__title-wrapper">
        <Text size="body" className="edit-site-document-actions__title" as="h1">
          <VisuallyHidden as="span">{__('Editing workflow: ')}</VisuallyHidden>
          {workflowName}
        </Text>

        <Text
          size="body"
          className="edit-site-document-actions__secondary-item"
        >
          {workflowStatus === WorkflowStatus.ACTIVE ? 'Active' : 'Not Active'}
        </Text>

        {children && (
          <Dropdown
            popoverProps={{
              anchorRef: titleRef.current,
            }}
            position="bottom center"
            renderToggle={({ isOpen, onToggle }) => (
              <Button
                className="edit-site-document-actions__get-info"
                icon={chevronDown}
                aria-expanded={isOpen}
                aria-haspopup="true"
                onClick={onToggle}
                label={__('Change workflow name')}
              >
                {showIconLabels && __('Rename')}
              </Button>
            )}
            contentClassName="edit-site-document-actions__info-dropdown"
            renderContent={children}
          />
        )}
      </div>
    </div>
  );
}
