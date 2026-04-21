import { __ } from '@wordpress/i18n';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Inputs, Label } from 'settings/components';

export function SendingStatusRetention() {
  const [retentionDays, setRetentionDays] = useSetting(
    'sending_status_retention_days',
  );
  return (
    <>
      <Label
        title={__('Sending status data retention', 'mailpoet')}
        description={__(
          'Choose how long per-subscriber sending records are kept after a newsletter has been sent. Select "Never" to keep records indefinitely — recommended if you have compliance or audit requirements.',
          'mailpoet',
        )}
        htmlFor=""
      />
      <Inputs>
        <div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="sending-status-retention-never"
              automationId="sending-status-retention-option-never"
              value=""
              checked={retentionDays === ''}
              onCheck={setRetentionDays}
            />
            <label htmlFor="sending-status-retention-never">
              {__('Never', 'mailpoet')}
            </label>
          </div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="sending-status-retention-1-month"
              automationId="sending-status-retention-option-1-month"
              value="30"
              checked={retentionDays === '30'}
              onCheck={setRetentionDays}
            />
            <label htmlFor="sending-status-retention-1-month">
              {__('After 1 month', 'mailpoet')}
            </label>
          </div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="sending-status-retention-2-months"
              automationId="sending-status-retention-option-2-months"
              value="60"
              checked={retentionDays === '60'}
              onCheck={setRetentionDays}
            />
            <label htmlFor="sending-status-retention-2-months">
              {__('After 2 months', 'mailpoet')}
            </label>
          </div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="sending-status-retention-3-months"
              automationId="sending-status-retention-option-3-months"
              value="90"
              checked={retentionDays === '90'}
              onCheck={setRetentionDays}
            />
            <label htmlFor="sending-status-retention-3-months">
              {__('After 3 months', 'mailpoet')}
            </label>
          </div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="sending-status-retention-6-months"
              automationId="sending-status-retention-option-6-months"
              value="180"
              checked={retentionDays === '180'}
              onCheck={setRetentionDays}
            />
            <label htmlFor="sending-status-retention-6-months">
              {__('After 6 months', 'mailpoet')}
            </label>
          </div>
        </div>
      </Inputs>
    </>
  );
}
