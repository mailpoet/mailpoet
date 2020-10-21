import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import Form from 'form/form.jsx';
import PropTypes from 'prop-types';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import Background from 'common/background/background';

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

const SegmentForm = (props) => (
  <div>
    <Background color="#fff" />
    <h1 className="title">
      {MailPoet.I18n.t('segment')}
      {' '}
      <Link className="mailpoet-button mailpoet-button-small" to="/">{MailPoet.I18n.t('backToList')}</Link>
    </h1>

    <SubscribersLimitNotice />

    <Form
      endpoint="segments"
      fields={fields}
      params={props.match.params}
      messages={messages}
    />
  </div>
);

SegmentForm.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

export default SegmentForm;
