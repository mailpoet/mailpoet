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
  } else {
    return (<p>{MailPoet.I18n.t('systemInfoDataError')}</p>);
  }
}

function KnowledgeBase() {
  const data = window.help_scout_data;
  return (
    <div>

      <Tabs tab="systemInfo" />

      <div className="mailpoet_notice notice inline notice-success" style={{ marginTop: '1em' }}>
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      {printData(data)}
    </div>
  );
}

module.exports = KnowledgeBase;
