import {
  PanelRow,
  Flex,
  FlexItem,
  Button,
  Dropdown,
  __experimentalVStack as VStack,
  __experimentalHStack as HStack,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { useRecipients } from '../../shared/use-recipients';
import { RecipientsSelector } from '../../shared/recipients-selector';

export function RecipientsRow() {
  const recipients = useRecipients();
  const { isLoadingSegments, recipientLabel } = recipients;
  const recipientsPopoverAnchor = useRef(null);

  return (
    <PanelRow>
      <Flex justify="start" ref={recipientsPopoverAnchor}>
        <FlexItem className="editor-post-panel__row-label">
          {__('Recipients', 'mailpoet')}
        </FlexItem>
        <FlexItem className="editor-post-panel__row-control">
          <Dropdown
            popoverProps={{
              anchor: recipientsPopoverAnchor.current,
              placement: 'left-start',
              offset: 36,
              shift: true,
            }}
            renderToggle={({ isOpen, onToggle }) => (
              <Button
                variant="tertiary"
                onClick={onToggle}
                aria-expanded={isOpen}
                disabled={isLoadingSegments}
              >
                {recipientLabel}
              </Button>
            )}
            renderContent={({ onClose }) => (
              <div className="mailpoet-status-panel__recipients-selector">
                <VStack
                  className="block-editor-inspector-popover-header"
                  spacing={4}
                >
                  <HStack alignment="center">
                    <Heading
                      className="block-editor-inspector-popover-header__heading"
                      level={2}
                      size={13}
                    >
                      {__('Select recipients', 'mailpoet')}
                    </Heading>
                    <Spacer />
                    <Button
                      size="small"
                      className="block-editor-inspector-popover-header__action"
                      label={__('Close', 'mailpoet')}
                      icon={closeSmall}
                      onClick={onClose}
                    />
                  </HStack>
                </VStack>
                <RecipientsSelector recipients={recipients} />
              </div>
            )}
          />
        </FlexItem>
      </Flex>
    </PanelRow>
  );
}
