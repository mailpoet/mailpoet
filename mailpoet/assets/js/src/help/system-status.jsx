import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { CronStatus } from './cron-status.jsx';
import { QueueStatus } from './queue-status';
import { ActionSchedulerStatus } from './action-scheduler-status';

function renderStatusMessage(
  status,
  successMessage,
  errorMessage,
  link,
  additionalInfo,
) {
  const noticeType = status ? 'success' : 'error';
  let noticeMessage = status ? successMessage : errorMessage;

  if (link) {
    noticeMessage = ReactStringReplace(
      noticeMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a className="mailpoet-text-link" href={link} key="kb-link">
          {match}
        </a>
      ),
    );
  }

  return (
    <div className={`mailpoet_notice notice inline notice-${noticeType}`}>
      <p>{noticeMessage}</p>
      {additionalInfo ? (
        <p>
          <i>{additionalInfo}</i>
        </p>
      ) : null}
    </div>
  );
}

function renderCronSection(data) {
  const status = data.cron.isReachable;
  const url = data.cron.url;
  const error = `${MailPoet.I18n.t(
    'systemStatusConnectionUnsuccessful',
  )} ${MailPoet.I18n.t('systemStatusCronConnectionUnsuccessfulInfo')}`;
  const success = MailPoet.I18n.t('systemStatusConnectionSuccessful');
  const additionalInfo = !status ? data.cron.pingResponse : null;

  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusCronTitle')}</h4>
      <p>
        <a
          className="mailpoet-text-link"
          href={url}
          target="_blank"
          rel="noopener noreferrer"
        >
          {url}
        </a>
      </p>
      {renderStatusMessage(
        status,
        success,
        error,
        'https://kb.mailpoet.com/article/231-sending-does-not-work',
        additionalInfo,
      )}
    </div>
  );
}

function renderMSSSection(data) {
  const errorMessage = data.mss.enabled
    ? `${MailPoet.I18n.t(
        'systemStatusConnectionUnsuccessful',
      )} ${MailPoet.I18n.t('systemStatusMSSConnectionUnsuccessfulInfo')}`
    : MailPoet.I18n.t('systemStatusMSSConnectionCanNotConnect');
  const successMessage = data.mss.enabled
    ? MailPoet.I18n.t('systemStatusConnectionSuccessful')
    : MailPoet.I18n.t('systemStatusMSSConnectionCanConnect');
  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusMSSTitle')}</h4>
      {renderStatusMessage(
        data.mss.isReachable,
        successMessage,
        errorMessage,
        'https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key',
        null,
      )}
    </div>
  );
}

export function SystemStatus() {
  const systemStatusData = window.systemStatusData;
  const actionSchedulerData = window.actionSchedulerData;

  return (
    <>
      <div className="mailpoet_notice notice inline">
        <p>
          {systemStatusData.mss.enabled
            ? MailPoet.I18n.t('systemStatusIntroCronMSS')
            : MailPoet.I18n.t('systemStatusIntroCron')}
        </p>
      </div>
      {renderCronSection(systemStatusData)}
      {renderMSSSection(systemStatusData)}
      <CronStatus status_data={systemStatusData.cronStatus} />
      <ActionSchedulerStatus {...actionSchedulerData} />
      {actionSchedulerData ? (
        <QueueStatus statusData={systemStatusData.queueStatus} />
      ) : null}
    </>
  );
}
