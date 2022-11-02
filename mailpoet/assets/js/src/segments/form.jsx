import { Link } from 'react-router-dom';
import PropTypes from 'prop-types';
import { Background } from 'common/background/background';
import { Form } from 'form/form.jsx';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice.jsx';
import { MailPoet } from 'mailpoet';

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
  {
    name: 'showInManageSubscriptionPage',
    label: MailPoet.I18n.t('showInManageSubscriptionPage'),
    type: 'checkbox',
    values: {
      showInManageSubscriptionPage: MailPoet.I18n.t(
        'showInManageSubscriptionPageTip',
      ),
    },
  },
];

const messages = {
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentUpdated'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentAdded'));
    MailPoet.trackEvent('Lists > Add new');
  },
};

function SegmentForm(props) {
  return (
    <div>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('segment')}</span>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to="/"
        >
          {MailPoet.I18n.t('backToList')}
        </Link>
      </Heading>

      <SubscribersLimitNotice />

      <Form
        endpoint="segments"
        fields={fields}
        params={props.match.params}
        messages={messages}
      />
    </div>
  );
}

SegmentForm.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

export { SegmentForm };
