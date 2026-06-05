import { FormTokenField, RadioControl, Spinner } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import type { RecipientType, UseRecipients } from './use-recipients';

export function RecipientsSelector({
  recipients,
}: {
  recipients: UseRecipients;
}) {
  const {
    isLoadingSegments,
    isWooActive,
    recipientType,
    handleRecipientTypeChange,
    allowedSegments,
    selectedAllowedSegments,
    setSelectedSegments,
    recipientCount,
    isLoadingRecipientCount,
    allCustomersSegmentCount,
  } = recipients;

  if (isLoadingSegments) {
    return <Spinner />;
  }

  const showSegmentField = !isWooActive || recipientType === 'segment';

  return (
    <>
      {isWooActive && (
        <RadioControl
          selected={recipientType}
          options={[
            {
              label: __('Send to all customers', 'mailpoet'),
              value: 'all_customers',
              description: sprintf(
                _n(
                  '%s recipient',
                  '%s recipients',
                  allCustomersSegmentCount,
                  'mailpoet',
                ),
                allCustomersSegmentCount.toString(),
              ),
            },
            {
              label: __('Send to a segment', 'mailpoet'),
              value: 'segment',
            },
          ]}
          onChange={(value) =>
            handleRecipientTypeChange(value as RecipientType)
          }
        />
      )}

      {showSegmentField && (
        <div className="mailpoet-status-panel__recipients-segments">
          <FormTokenField
            label={
              isWooActive
                ? __('Select segment(s)', 'mailpoet')
                : __('Select list(s) and segment(s)', 'mailpoet')
            }
            value={selectedAllowedSegments.map((segment) => segment.name)}
            suggestions={allowedSegments.map((segment) => segment.name)}
            onChange={setSelectedSegments}
            __experimentalExpandOnFocus
            __experimentalAutoSelectFirstMatch
            __experimentalShowHowTo={false}
          />
          <div className="mailpoet-status-panel__recipients-total-count">
            {__('Total recipients: ', 'mailpoet')}{' '}
            {isLoadingRecipientCount ? (
              <Spinner className="mailpoet-status-panel__recipients-loader" />
            ) : (
              (recipientCount ?? 0).toLocaleString()
            )}
          </div>
        </div>
      )}
    </>
  );
}
