import MailPoet from 'mailpoet';
import React from 'react';
import PrintBoolean from 'common/print_boolean.jsx';

function renderStatusTableRow(title, value) {
  return (
    <tr>
      <td className={'row-title'}>{ title }</td><td>{ value }</td>
    </tr>
  );
}

const CronStatus = (props) => {
  const status = props.status_data;
  const activeStatusMapping = {
    active: MailPoet.I18n.t('cronRunning'),
    inactive: MailPoet.I18n.t('cronWaiting'),
  };
  return (
    <div>
      <h2>{MailPoet.I18n.t('systemStatusCronStatusTitle')}</h2>
      <table className={'widefat fixed'} style={{ maxWidth: '400px' }}>
        <tbody>
          {renderStatusTableRow(
            MailPoet.I18n.t('accessible'),
            <PrintBoolean>{status.accessible}</PrintBoolean>)
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('status'),
            activeStatusMapping[status.status] ? activeStatusMapping[status.status] : MailPoet.I18n.t('unknown'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('lastUpdated'),
            status.updated_at ? MailPoet.Date.full(status.updated_at * 1000) : MailPoet.I18n.t('unknown'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('lastRunStarted'),
            status.run_accessed_at ? MailPoet.Date.full(status.run_started_at * 1000) : MailPoet.I18n.t('unknown'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('lastRunCompleted'),
            status.run_completed_at ? MailPoet.Date.full(status.run_completed_at * 1000) : MailPoet.I18n.t('unknown'))
          }
          {renderStatusTableRow(MailPoet.I18n.t('lastSeenError'), status.last_error || '-')}
        </tbody>
      </table>
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
