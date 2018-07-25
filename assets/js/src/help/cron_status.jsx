import MailPoet from 'mailpoet';
import React from 'react';
import KeyValueTable from 'common/key_value_table.jsx';
import PrintBoolean from 'common/print_boolean.jsx';

const CronStatus = (props) => {
  const status = props.status_data;
  const activeStatusMapping = {
    active: MailPoet.I18n.t('running'),
    inactive: MailPoet.I18n.t('cronWaiting'),
  };
  return (
    <div>
      <h2>{MailPoet.I18n.t('systemStatusCronStatusTitle')}</h2>
      <KeyValueTable max_width={'400px'}>{[
        {
          key: MailPoet.I18n.t('accessible'),
          value: <PrintBoolean>{status.accessible}</PrintBoolean>,
        },
        {
          key: MailPoet.I18n.t('status'),
          value: activeStatusMapping[status.status] ? activeStatusMapping[status.status] : MailPoet.I18n.t('unknown'),
        },
        {
          key: MailPoet.I18n.t('lastUpdated'),
          value: status.updated_at ? MailPoet.Date.full(status.updated_at * 1000) : MailPoet.I18n.t('unknown'),
        },
        {
          key: MailPoet.I18n.t('lastRunStarted'),
          value: status.run_accessed_at ? MailPoet.Date.full(status.run_started_at * 1000) : MailPoet.I18n.t('unknown'),
        },
        {
          key: MailPoet.I18n.t('lastRunCompleted'),
          value: status.run_completed_at ? MailPoet.Date.full(status.run_completed_at * 1000) : MailPoet.I18n.t('unknown'),
        },
        {
          key: MailPoet.I18n.t('lastSeenError'),
          value: status.last_error || MailPoet.I18n.t('none'),
        }]}
      </KeyValueTable>
    </div>
  );
};

CronStatus.propTypes = {
  status_data: React.PropTypes.shape({
    accessible: React.PropTypes.bool,
    status: React.PropTypes.string,
    updated_at: React.PropTypes.number,
    run_accessed_at: React.PropTypes.number,
    run_completed_at: React.PropTypes.number,
  }).isRequired,
};

CronStatus.defaultProps = {
  status_data: {
    accessible: null,
    status: null,
    updated_at: null,
    run_accessed_at: null,
    run_completed_at: null,
  },
};

module.exports = CronStatus;
