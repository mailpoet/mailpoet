import React from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import Tabs from './tabs.jsx';

function handleFocus(event) {
  event.target.select();
}

function printData(data) {
  if (_.isObject(data)) {
    const printableData = Object.keys(data).map((key) => {
      return `${key}: ${data[key]}`;
    });

    return (<textarea
      readOnly={true}
      onFocus={handleFocus}
      value={printableData.join('\n')}
      style={{
        width: '100%',
        height: '400px',
      }}
    />);
  }
  return (<p>{MailPoet.I18n.t('systemInfoDataError')}</p>);
}

function SystemInfo() {
  const systemInfoData = window.systemInfoData;
  return (
    <div>

      <Tabs tab="systemInfo" />

      <div className="mailpoet_notice notice inline" style={{ marginTop: '1em' }}>
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      {printData(systemInfoData)}
    </div>
  );
}

module.exports = SystemInfo;
