import { __ } from '@wordpress/i18n';
import { Form } from 'form/form.jsx';
import { SubscribersLimitNotice } from 'notices/subscribers-limit-notice';
import { MailPoet } from 'mailpoet';
import { useParams } from 'react-router-dom';
import { BackButton, PageHeader } from '../../common/page-header';
import { TopBarWithBoundary } from '../../common/top-bar/top-bar';

const fields = [
  {
    name: 'name',
    label: MailPoet.I18n.t('segmentFormName'),
    type: 'text',
    tip: MailPoet.I18n.t('segmentFormNameTip'),
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
    isChecked: true, // default checked state
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

function SegmentForm() {
  const params = useParams();

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBoundary hideScreenOptions />

      <PageHeader
        heading={
          params.id
            ? __('Edit List', 'mailpoet')
            : __('Add New List', 'mailpoet')
        }
        headingPrefix={
          <BackButton
            href="#/"
            label={__('Lists', 'mailpoet')}
            aria-label={__('Navigate to the lists page', 'mailpoet')}
          />
        }
      />
      <SubscribersLimitNotice />

      <Form
        endpoint="segments"
        fields={fields}
        params={params}
        messages={messages}
      />
    </div>
  );
}

SegmentForm.displayName = 'SegmentForm';

export { SegmentForm };
