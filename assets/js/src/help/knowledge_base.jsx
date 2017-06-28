import React from 'react'
import MailPoet from 'mailpoet'

import Tabs from './tabs.jsx'

function KnowledgeBase() {

  return (
    <div>
      <h1 className="title">
        {MailPoet.I18n.t('pageTitle')}
      </h1>

      <Tabs tab="knowledgeBase" />

      <div>asdfasdf</div>
    </div>
  );
};

module.exports = KnowledgeBase;