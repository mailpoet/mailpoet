import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import KeyValueTable from 'common/key_value_table.jsx';
import PrintBoolean from 'common/print_boolean.jsx';

function CronStatus(props) {
  const status = props.status_data;
  const activeStatusMapping = {
    active: MailPoet.I18n.t('running'),
    inactive: MailPoet.I18n.t('cronWaiting'),
  };
  const lastError = Array.isArray(status.last_error) ? (
    <>
      {status.last_error.map((error) => (
        <div key={error.worker}>
          {error.worker}: <i>{error.message}</i>
        </div>
      ))}
    </>
  ) : (
    status.last_error
  );
  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusCronStatusTitle')}</h4>
      <KeyValueTable
        max_width="400px"
        rows={[
          {
            key: MailPoet.I18n.t('accessible'),
            value: <PrintBoolean>{status.accessible}</PrintBoolean>,
          },
          {
            key: MailPoet.I18n.t('status'),
            value: activeStatusMapping[status.status]
              ? activeStatusMapping[status.status]
              : MailPoet.I18n.t('unknown'),
          },
          {
            key: MailPoet.I18n.t('lastUpdated'),
            value: status.updated_at
              ? MailPoet.Date.full(status.updated_at * 1000)
              : MailPoet.I18n.t('unknown'),
          },
          {
            key: MailPoet.I18n.t('lastRunStarted'),
            value: status.run_accessed_at
              ? MailPoet.Date.full(status.run_started_at * 1000)
              : MailPoet.I18n.t('unknown'),
          },
          {
            key: MailPoet.I18n.t('lastRunCompleted'),
            value: status.run_completed_at
              ? MailPoet.Date.full(status.run_completed_at * 1000)
              : MailPoet.I18n.t('unknown'),
          },
          {
            key: MailPoet.I18n.t('lastSeenError'),
            value: lastError || MailPoet.I18n.t('none'),
          },
          {
            key: MailPoet.I18n.t('lastSeenErrorDate'),
            value: status.last_error_date
              ? MailPoet.Date.full(status.last_error_date * 1000)
              : MailPoet.I18n.t('unknown'),
          },
        ]}
      />
    </div>
  );
}

CronStatus.propTypes = {
  status_data: PropTypes.shape({
    accessible: PropTypes.bool,
    last_error_date: PropTypes.string,
    status: PropTypes.string,
    updated_at: PropTypes.number,
    run_accessed_at: PropTypes.number,
    run_completed_at: PropTypes.number,
    run_started_at: PropTypes.number,
    last_error: PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
  }).isRequired,
};

export default CronStatus;
