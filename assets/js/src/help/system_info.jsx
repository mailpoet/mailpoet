import React from 'react'
import MailPoet from 'mailpoet'

import Tabs from './tabs.jsx'

function KnowledgeBase() {

  return (
    <div>
      <h1 className="title">
        {MailPoet.I18n.t('pageTitle')}
      </h1>

      <Tabs tab="systemInfo" />

      <div className="mailpoet_notice notice inline notice-success" style={{marginTop: "1em"}}>
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      <textarea readOnly={true}></textarea>
    </div>
  );
};

module.exports = KnowledgeBase;