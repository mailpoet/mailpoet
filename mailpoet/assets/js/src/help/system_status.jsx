import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { CronStatus } from './cron_status.jsx';
import { QueueStatus } from './queue_status.jsx';
import { ActionSchedulerStatus } from './action_scheduler_status';

function renderStatusMessage(
  status,
  successMessage,
  errorMessage,
  link,
  linkBeacon,
  additionalInfo,
) {
  const noticeType = status ? 'success' : 'error';
  let noticeMessage = status ? successMessage : errorMessage;

  if (link) {
    noticeMessage = ReactStringReplace(
      noticeMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a
          className="mailpoet-text-link"
          href={link}
          data-beacon-article={linkBeacon}
          key="kb-link"
        >
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
        '5a0257ac2c7d3a272c0d7ad6',
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
        '5ef1da9d2c7d3a10cba966c5',
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
        <QueueStatus status_data={systemStatusData.queueStatus} />
      ) : null}
    </>
  );
}
