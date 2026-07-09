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
import { SCHEDULE_MODE_SUBSCRIBER_TIMEZONE } from 'common/newsletter-schedule-mode';
import { useScheduledDate } from '../../shared/use-scheduled-date';
import { ScheduledDatePicker } from '../../shared/scheduled-date-picker';
import { ScheduleModeControls } from '../../shared/schedule-mode-controls';

export function ScheduledRow() {
  const { formattedDate, setScheduledDate, scheduleMode } = useScheduledDate();
  const isSubscriberTimezoneMode =
    scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;
  const sendPopoverAnchor = useRef(null);

  return (
    <PanelRow>
      <Flex justify="start" ref={sendPopoverAnchor}>
        <FlexItem className="editor-post-panel__row-label">
          {__('Send', 'mailpoet')}
        </FlexItem>
        <FlexItem className="editor-post-panel__row-control">
          <Dropdown
            popoverProps={{
              anchor: sendPopoverAnchor.current,
              placement: 'left-start',
              offset: 36,
              shift: true,
            }}
            renderToggle={({ isOpen, onToggle }) => (
              <Button
                variant="tertiary"
                onClick={onToggle}
                aria-expanded={isOpen}
              >
                {formattedDate}
              </Button>
            )}
            renderContent={({ onClose }) => (
              <div className="mailpoet-status-panel__date-time-picker">
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
                      {__('Send', 'mailpoet')}
                    </Heading>
                    <Spacer />
                    {!isSubscriberTimezoneMode && (
                      <Button
                        size="small"
                        className="block-editor-inspector-popover-header__action"
                        label={__('Now', 'mailpoet')}
                        variant="tertiary"
                        onClick={() => setScheduledDate(null)}
                      >
                        {__('Now', 'mailpoet')}
                      </Button>
                    )}
                    <Button
                      size="small"
                      className="block-editor-inspector-popover-header__action"
                      label={__('Close', 'mailpoet')}
                      icon={closeSmall}
                      onClick={onClose}
                    />
                  </HStack>
                </VStack>
                <ScheduleModeControls />
                <ScheduledDatePicker />
              </div>
            )}
          />
        </FlexItem>
      </Flex>
    </PanelRow>
  );
}
