import { MailPoet } from 'mailpoet';
import { KeyValueTable } from 'common/key-value-table';

type ActionSchedulerStatusProps = {
  version: string;
  storage: string;
  latestTrigger: string;
  latestCompletedTrigger: string;
  latestCompletedRun?: string;
};

function ActionSchedulerStatus({
  version,
  storage,
  latestTrigger,
  latestCompletedTrigger,
  latestCompletedRun,
}: ActionSchedulerStatusProps) {
  return (
    <>
      <h4>{MailPoet.I18n.t('actionSchedulerStatus')}</h4>
      <KeyValueTable
        max_width="400px"
        rows={[
          {
            key: MailPoet.I18n.t('version'),
            value: version,
          },
          {
            key: MailPoet.I18n.t('storage'),
            value: storage,
          },
          {
            key: MailPoet.I18n.t('latestActionSchedulerTrigger'),
            value: MailPoet.Date.full(latestTrigger),
          },
          {
            key: MailPoet.I18n.t('latestActionSchedulerCompletedTrigger'),
            value: MailPoet.Date.full(latestCompletedTrigger),
          },
          {
            key: MailPoet.I18n.t('latestActionSchedulerCompletedRun'),
            value: MailPoet.Date.full(latestCompletedRun),
          },
        ]}
      />
    </>
  );
}

export { ActionSchedulerStatus };
