import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import Form from 'form/form.jsx';
import PropTypes from 'prop-types';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';

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

export default SegmentForm;
