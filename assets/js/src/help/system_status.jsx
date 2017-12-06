import MailPoet from 'mailpoet';
import React from 'react';
import ReactStringReplace from 'react-string-replace';
import Tabs from './tabs.jsx';

function renderStatusMessage(status, error, link) {
  const noticeType = (status) ? 'success' : 'error';
  let noticeMessage = (status) ?
    MailPoet.I18n.t('systemStatusConnectionSuccessful') :
    `${MailPoet.I18n.t('systemStatusConnectionUnsuccessful')} ${error}`;

  if (link) {
    noticeMessage = ReactStringReplace(
      noticeMessage,
      /\[link\](.*?)\[\/link\]/g,
      match => (
        <a href={`${link}`} key="kb-link">{ match }</a>
      )
    );
  }

  return (
    <div className={`mailpoet_notice notice inline notice-${noticeType}`} style={{ marginTop: '1em' }}>
      <p>{noticeMessage}</p>
    </div>
  );
}

function renderCronSection(data) {
  const status = data.cron.isReachable;
  const url = data.cron.url;

  return (
    <div>
      <h2>{MailPoet.I18n.t('systemStatusCronTitle')}</h2>
      <p>
        <a href={url} target="_blank">{url}</a>
      </p>
      {renderStatusMessage(status, MailPoet.I18n.t('systemStatusCronConnectionUnsuccessfulInfo'), '//beta.docs.mailpoet.com/article/231-sending-does-not-work')}
    </div>
  );
}

function renderMSSSection(data) {
  if (!data.mss.enabled) return;

  const status = data.mss.enabled.isReachable;

  return (
    <div>
      <h2>{MailPoet.I18n.t('systemStatusMSSTitle')}</h2>
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
    </div>
  );
}
module.exports = SystemStatus;
