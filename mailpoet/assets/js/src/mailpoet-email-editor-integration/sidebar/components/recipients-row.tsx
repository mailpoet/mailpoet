import {
  PanelRow,
  Flex,
  FlexItem,
  Button,
  Dropdown,
  FormTokenField,
  RadioControl,
  Spinner,
  __experimentalVStack as VStack,
  __experimentalHStack as HStack,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { useRef, useState, useEffect } from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { MailPoet } from 'mailpoet';

type Segment = {
  id: string;
  name: string;
  type: string;
  subscribers: string;
  deleted_at?: string;
  subscribers_count?: {
    all: string;
    subscribed: string;
    unsubscribed: string;
    unconfirmed: string;
    bounced: string;
    inactive: string;
    trash: string;
  };
};

const EMPTY_ARRAY = [];

export function RecipientsRow() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const selectedSegmentIds = mailpoetEmailData?.segment_ids || EMPTY_ARRAY;
  const recipientsPopoverAnchor = useRef(null);

  const [segments, setSegments] = useState<Segment[]>([]);
  const [isLoadingSegments, setIsLoadingSegments] = useState(true);
  const [recipientType, setRecipientType] = useState<
    'all_customers' | 'segment'
  >('segment');

  // Fetch segments on mount.
  useEffect(() => {
    let mounted = true;
    setIsLoadingSegments(true);
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'segments',
      action: 'listing',
      data: {},
    })
      .done((response) => {
        const allSegments = response.data || [];
        const activeSegments = allSegments.filter(
          (segment: Segment) => !segment.deleted_at,
        );
        if (mounted) {
          setSegments(activeSegments as Segment[]);
        }
      })
      .fail(() => {
        if (mounted) {
          setSegments([]);
        }
      })
      .always(() => {
        if (mounted) {
          setIsLoadingSegments(false);
        }
      });
    return () => {
      mounted = false;
    };
  }, []);

  // Determine recipient type based on selected segments.
  useEffect(() => {
    if (segments.length === 0) {
      return;
    }

    if (selectedSegmentIds.length === 0) {
      setRecipientType('segment');
      return;
    }

    const firstSegment = segments.find(
      (segment) => segment.id.toString() === selectedSegmentIds[0].toString(),
    );
    if (firstSegment?.type === 'woocommerce_users') {
      setRecipientType('all_customers');
    } else {
      setRecipientType('segment');
    }
  }, [selectedSegmentIds, segments]);

  const updateEmailMailPoetProperty = (name: string, value: unknown) => {
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
          [name]: value,
        },
      },
    );
  };

  const setSelectedSegments = (segmentNames: string[]) => {
    const defaultSegments = segments.filter(
      (segment) => segment.type === 'default',
    );
    const segmentIds = segmentNames
      .map((name) => {
        const segment = defaultSegments.find(
          (_segment) => _segment.name === name,
        );
        return segment ? segment.id : null;
      })
      .filter((id): id is string => id !== null);

    updateEmailMailPoetProperty('segment_ids', segmentIds);
  };

  const handleRecipientTypeChange = (newType: 'all_customers' | 'segment') => {
    setRecipientType(newType);

    if (newType === 'all_customers') {
      const allCustomersSegment = segments.find(
        (segment) => segment.type === 'woocommerce_users',
      );
      if (allCustomersSegment) {
        updateEmailMailPoetProperty('segment_ids', [allCustomersSegment.id]);
      }
    } else {
      updateEmailMailPoetProperty('segment_ids', []);
    }
  };

  // Filter segments by type
  const defaultSegments = segments.filter((s) => s.type === 'default');
  const allCustomersSegment = segments.find(
    (s) => s.type === 'woocommerce_users',
  );
  const allCustomersSegmentCount = parseInt(
    allCustomersSegment?.subscribers_count?.all || '0',
    10,
  );

  const selectedSegments: Segment[] = selectedSegmentIds
    .map((id: string): Segment | undefined =>
      segments.find(
        (segment: Segment) => segment.id.toString() === id.toString(),
      ),
    )
    .filter((segment) => segment !== undefined);

  // Calculate total recipients from selected segments
  const recipientCount = selectedSegments.reduce((total, segment) => {
    const count = parseInt(segment.subscribers_count?.all || '0', 10);
    return total + count;
  }, 0);

  // Filter selected segments to only show default type segments
  const selectedDefaultSegments = selectedSegments.filter(
    (segment) => segment.type === 'default',
  );

  let buttonLabel = __('Select recipients', 'mailpoet');
  if (recipientType === 'all_customers') {
    buttonLabel = __('All customers', 'mailpoet');
  } else if (selectedDefaultSegments.length > 0) {
    buttonLabel = selectedDefaultSegments
      .map((segment) => segment.name)
      .join(', ');
  }

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
                {buttonLabel}
              </Button>
            )}
            renderContent={({ onClose }) => (
              <div className="mailpoet-status-panel__recipients-selector">
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
                {isLoadingSegments ? (
                  <Spinner />
                ) : (
                  <>
                    <RadioControl
                      selected={recipientType}
                      options={[
                        {
                          label: __('Send to all customers', 'mailpoet'),
                          value: 'all_customers',
                          /* @ts-expect-error description prop is available in the external @wordpress/components package */
                          description: sprintf(
                            _n(
                              '%s recipient',
                              '%s recipients',
                              allCustomersSegmentCount,
                              'mailpoet',
                            ),
                            allCustomersSegmentCount,
                          ),
                        },
                        {
                          label: __('Send to a segment', 'mailpoet'),
                          value: 'segment',
                        },
                      ]}
                      onChange={(value) =>
                        handleRecipientTypeChange(
                          value as 'all_customers' | 'segment',
                        )
                      }
                    />

                    {recipientType === 'segment' && (
                      <div className="mailpoet-status-panel__recipients-segments">
                        <FormTokenField
                          label={__('Select segment(s)', 'mailpoet')}
                          value={selectedDefaultSegments.map(
                            (segment) => segment.name,
                          )}
                          suggestions={defaultSegments.map(
                            (segment) => segment.name,
                          )}
                          onChange={setSelectedSegments}
                          __experimentalExpandOnFocus
                          __experimentalAutoSelectFirstMatch
                          __experimentalShowHowTo={false}
                        />
                        <div className="mailpoet-status-panel__recipients-total-count">
                          {__('Total recipients: ', 'mailpoet')}{' '}
                          {recipientCount.toLocaleString()}
                        </div>
                      </div>
                    )}
                  </>
                )}
              </div>
            )}
          />
        </FlexItem>
      </Flex>
    </PanelRow>
  );
}
