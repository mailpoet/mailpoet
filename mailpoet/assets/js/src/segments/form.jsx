import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Background } from 'common/background/background';
import { Form } from 'form/form.jsx';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice';
import { MailPoet } from 'mailpoet';

const fields = [
  {
    name: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
  },
  {
    name: 'description',
    label: __('Description', 'mailpoet'),
    type: 'textarea',
    tip: __(
      'This text box is for your own use and is never shown to your subscribers.',
      'mailpoet',
    ),
  },
  {
    name: 'showInManageSubscriptionPage',
    label: __('List visibility', 'mailpoet'),
    type: 'checkbox',
    values: {
      showInManageSubscriptionPage: __(
        'Show this list on the "Manage Subscription" page',
        'mailpoet',
      ),
    },
    isChecked: true, // default checked state
  },
];

const messages = {
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(__('List successfully updated!', 'mailpoet'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(__('List successfully added!', 'mailpoet'));
    MailPoet.trackEvent('Lists > Add new');
  },
};

function SegmentForm(props) {
  return (
    <div>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{__('List', 'mailpoet')}</span>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to="/"
        >
          {__('Back', 'mailpoet')}
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

SegmentForm.displayName = 'SegmentForm';

export { SegmentForm };
