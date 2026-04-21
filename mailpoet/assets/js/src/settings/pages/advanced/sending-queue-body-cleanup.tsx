import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Inputs, Label } from 'settings/components';

export function SendingQueueBodyCleanup() {
  const [retentionDays, setRetentionDays] = useSetting(
    'sending_queue_body_retention_days',
  );
  return (
    <>
      <Label
        title={t('sendingQueueBodyCleanupTitle')}
        description={t('sendingQueueBodyCleanupDescription')}
        htmlFor=""
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="sending-queue-body-cleanup-never"
            value=""
            checked={retentionDays === ''}
            onCheck={setRetentionDays}
          />
          <label htmlFor="sending-queue-body-cleanup-never">{t('never')}</label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="sending-queue-body-cleanup-7-days"
            value="7"
            checked={retentionDays === '7'}
            onCheck={setRetentionDays}
          />
          <label htmlFor="sending-queue-body-cleanup-7-days">
            {t('after7days')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="sending-queue-body-cleanup-30-days"
            value="30"
            checked={retentionDays === '30'}
            onCheck={setRetentionDays}
          />
          <label htmlFor="sending-queue-body-cleanup-30-days">
            {t('after30days')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="sending-queue-body-cleanup-90-days"
            value="90"
            checked={retentionDays === '90'}
            onCheck={setRetentionDays}
          />
          <label htmlFor="sending-queue-body-cleanup-90-days">
            {t('after90days')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="sending-queue-body-cleanup-365-days"
            value="365"
            checked={retentionDays === '365'}
            onCheck={setRetentionDays}
          />
          <label htmlFor="sending-queue-body-cleanup-365-days">
            {t('after365days')}
          </label>
        </div>
      </Inputs>
    </>
  );
}
