import {
  PanelRow,
  Flex,
  FlexItem,
  Button,
  Dropdown,
  DateTimePicker,
  __experimentalVStack as VStack,
  __experimentalHStack as HStack,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { dateI18n, getSettings } from '@wordpress/date';
import { closeSmall } from '@wordpress/icons';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';

export function ScheduledRow() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const scheduledDate = (mailpoetEmailData?.scheduled_at as string) || null;
  const sendPopoverAnchor = useRef(null);
  const settings = getSettings();

  const setScheduledDate = (date: string | null) => {
    const postId = select(editorStore).getCurrentPostId();
    const currentPostType = 'mailpoet_email';

    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      currentPostType,
      postId,
    );

    // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
    const mailpoetData = editedPost?.mailpoet_data || {};
    void dispatch(coreDataStore).editEntityRecord(
      'postType',
      currentPostType,
      postId,
      {
        mailpoet_data: {
          ...mailpoetData,
          scheduled_at: date,
        },
      },
    );
  };

  const getFormattedDate = () => {
    if (!scheduledDate) {
      return __('Immediately', 'mailpoet');
    }
    return dateI18n(
      settings.formats.datetime,
      scheduledDate,
      settings.timezone.string,
    );
  };

  const is12HourTime = /a(?!\\)/i.test(
    settings.formats.time
      .toLowerCase() // Test only the lower case a.
      .replace(/\\\\/g, '') // Replace "//" with empty strings.
      .split('')
      .reverse()
      .join(''), // Reverse the string and test for "a" not followed by a slash.
  );
  // Used for comparing today with DateTimePicker dates to determine validity.
  // We set the hours to 0:00:00 to match the time format of DateTimePicker dates.
  const today = new Date().setHours(0, 0, 0, 0);

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
                {getFormattedDate()}
              </Button>
            )}
            renderContent={({ onClose }) => (
              <div className="mailpoet-status-panel__date-time-picker">
                <VStack
                  className="block-editor-inspector-popover-header"
                  spacing={4}
                >
                  <HStack alignment="center">
                    {/* @ts-expect-error size prop is available in the external @wordpress/components package */}
                    <Heading
                      className="block-editor-inspector-popover-header__heading"
                      level={2}
                      size={13}
                    >
                      {__('Send', 'mailpoet')}
                    </Heading>
                    <Spacer />
                    <Button
                      size="small"
                      className="block-editor-inspector-popover-header__action"
                      label={__('Now', 'mailpoet')}
                      variant="tertiary"
                      onClick={() => setScheduledDate(null)}
                    >
                      {__('Now', 'mailpoet')}
                    </Button>
                    <Button
                      size="small"
                      className="block-editor-inspector-popover-header__action"
                      label={__('Close', 'mailpoet')}
                      icon={closeSmall}
                      onClick={onClose}
                    />
                  </HStack>
                </VStack>
                <DateTimePicker
                  currentDate={scheduledDate}
                  onChange={(newDate) => setScheduledDate(newDate)}
                  /* @ts-expect-error dateOrder prop is available in the external @wordpress/components package */
                  dateOrder={
                    /* translators: Order of day, month, and year. Available formats are 'dmy', 'mdy', and 'ymd'. */
                    _x('dmy', 'date order', 'mailpoet')
                  }
                  is12Hour={is12HourTime}
                  isInvalidDate={(date) => date.getTime() < today}
                />
              </div>
            )}
          />
        </FlexItem>
      </Flex>
    </PanelRow>
  );
}
