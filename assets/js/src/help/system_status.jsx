import MailPoet from 'mailpoet';
import React from 'react';
import ReactStringReplace from 'react-string-replace';
import CronStatus from './cron_status.jsx';
import QueueStatus from './queue_status.jsx';
import Tabs from './tabs.jsx';

function renderStatusMessage(status, error, link, linkBeacon, additionalInfo) {
  const noticeType = (status) ? 'success' : 'error';
  let noticeMessage = (status)
    ? MailPoet.I18n.t('systemStatusConnectionSuccessful')
    : `${MailPoet.I18n.t('systemStatusConnectionUnsuccessful')} ${error}`;

  if (link) {
    noticeMessage = ReactStringReplace(
      noticeMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a href={link} data-beacon-article={linkBeacon} key="kb-link">{match}</a>
      )
    );
  }

  return (
    <div className={`mailpoet_notice notice inline notice-${noticeType}`} style={{ marginTop: '1em' }}>
      <p>{noticeMessage}</p>
      {additionalInfo ? (<p><i>{additionalInfo}</i></p>) : null}
    </div>
  );
}

function renderCronSection(data) {
  const status = data.cron.isReachable;
  const url = data.cron.url;
  const error = MailPoet.I18n.t('systemStatusCronConnectionUnsuccessfulInfo');
  const additionalInfo = !status ? data.cron.pingResponse : null;

  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusCronTitle')}</h4>
      <p>
        <a href={url} target="_blank" rel="noopener noreferrer">{url}</a>
      </p>
      {renderStatusMessage(status, error, 'https://kb.mailpoet.com/article/231-sending-does-not-work', '5a0257ac2c7d3a272c0d7ad6', additionalInfo)}
    </div>
  );
}

function renderMSSSection(data) {
  if (!data.mss.enabled) return undefined;

  const status = data.mss.enabled.isReachable;

  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusMSSTitle')}</h4>
      {renderStatusMessage(status, MailPoet.I18n.t('systemStatusMSSConnectionUnsuccessfulInfo'), false)}
    </div>
  );
}

function SystemStatus() {
  const systemStatusData = window.systemStatusData;

  return (
    <div>
      <Tabs tab="systemStatus" />
      <div className="mailpoet_notice notice inline" style={{ marginTop: '1em' }}>
        <p>{systemStatusData.mss.enabled ? MailPoet.I18n.t('systemStatusIntroCronMSS') : MailPoet.I18n.t('systemStatusIntroCron')}</p>
      </div>
      {renderCronSection(systemStatusData)}
      {renderMSSSection(systemStatusData)}
      <CronStatus status_data={systemStatusData.cronStatus} />
      <QueueStatus status_data={systemStatusData.queueStatus} />
    </div>
  );
}
export default SystemStatus;
