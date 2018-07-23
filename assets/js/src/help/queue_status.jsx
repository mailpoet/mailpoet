import MailPoet from 'mailpoet';
import React from 'react';

function renderStatusTableRow(title, value) {
  return (
    <tr>
      <td className={'row-title'}>{ title }</td><td>{ value }</td>
    </tr>
  );
}

const QueueStatus = (props) => {
  const status = props.status_data;
  return (
    <div>
      <h2>{MailPoet.I18n.t('systemStatusQueueTitle')}</h2>
      <table className={'widefat fixed'} style={{ maxWidth: '400px' }}>
        <tbody>
          {renderStatusTableRow(
            MailPoet.I18n.t('status'),
            status.status === 'paused' ? MailPoet.I18n.t('paused') : MailPoet.I18n.t('running'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('startedAt'),
            status.started ? MailPoet.Date.full(status.started * 1000) : MailPoet.I18n.t('unknown'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('sentEmails'),
            status.sent || 0)
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('retryAttempts'),
            status.retry_attempt || MailPoet.I18n.t('none'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('retryAt'),
            status.retry_at ? MailPoet.Date.full(status.retry_at * 1000) : MailPoet.I18n.t('none'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('error'),
            status.error || MailPoet.I18n.t('none'))
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('totalCompletedTasks'),
            status.tasksStatusCounts.completed)
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('totalRunningTasks'),
            status.tasksStatusCounts.running)
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('totalPausedTasks'),
            status.tasksStatusCounts.paused)
          }
          {renderStatusTableRow(
            MailPoet.I18n.t('totalScheduledTasks'),
            status.tasksStatusCounts.scheduled)
          }
        </tbody>
      </table>
    </div>
  );
};

QueueStatus.propTypes = {
  status_data: React.PropTypes.shape({
    status: React.PropTypes.string,
    started: React.PropTypes.number,
    sent: React.PropTypes.number,
    retry_attempt: React.PropTypes.number,
    retry_at: React.PropTypes.number,
    tasksStatusCounts: React.PropTypes.shape({
      completed: React.PropTypes.number.isRequired,
      running: React.PropTypes.number.isRequired,
      paused: React.PropTypes.number.isRequired,
      scheduled: React.PropTypes.number.isRequired,
    }).isRequired,
  }).isRequired,
};

QueueStatus.defaultProps = {
  status_data: {
    status: null,
    started: null,
    sent: null,
    retry_attempt: null,
    retry_at: null,
  },
};

module.exports = QueueStatus;
