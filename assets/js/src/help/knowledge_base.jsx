import React from 'react';
import MailPoet from 'mailpoet';

import Tabs from './tabs.jsx';

function KnowledgeBase() {

  return (
    <div>

      <Tabs tab="knowledgeBase" />

      <p>{MailPoet.I18n.t('knowledgeBaseIntro')}</p>
      <ul>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/116-common-problems">Common Problems</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/165-newsletters">Newsletters</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/156-migration-questions">Migration Questions</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/149-sending-methods">Sending Methods</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/139-subscription-forms">Subscription Forms</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/114-getting-started">Getting Started</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/123-newsletter-designer">Newsletter Designer</a></li>
        <li><a target="_blank" href="http://beta.docs.mailpoet.com/category/121-subscribers-and-lists">Subscribers and Lists</a></li>
      </ul>
      <a target="_blank" href="http://beta.docs.mailpoet.com/" className="button button-primary">{MailPoet.I18n.t('knowledgeBaseButton')}</a>
    </div>
  );
};

module.exports = KnowledgeBase;
