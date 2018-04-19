import React from 'react';
import { Link } from 'react-router';
import MailPoet from 'mailpoet';
import Form from 'form/form.jsx';

const fields = [
  {
    name: 'name',
    label: MailPoet.I18n.t('name'),
    type: 'text',
  },
  {
    name: 'description',
    label: MailPoet.I18n.t('description'),
    type: 'textarea',
    tip: MailPoet.I18n.t('segmentDescriptionTip'),
  },
];

const messages = {
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentUpdated'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentAdded'));
    MailPoet.trackEvent('Lists > Add new', {
      'MailPoet Free version': window.mailpoet_version,
    });
  },
};

const SegmentForm = params => (
  <div>
    <h1 className="title">
      {MailPoet.I18n.t('segment')}
      <Link className="page-title-action" to="/">{MailPoet.I18n.t('backToList')}</Link>
    </h1>

    <Form
      endpoint="segments"
      fields={fields}
      params={params}
      messages={messages}
    />
  </div>
);

export default SegmentForm;
