import React, { useState, useEffect } from 'react';
import _ from 'underscore';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import Form from 'form/form.jsx';
import PropTypes from 'prop-types';

import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import wordpressRoleFields from './dynamic_segments_filters/wordpress_role.jsx';
import emailFields from './dynamic_segments_filters/email.jsx';
import woocommerceFields from './dynamic_segments_filters/woocommerce.jsx';
import { loadCount } from './subscribers_calculator.ts';
import { SubscribersCounter } from './subscribers_counter.tsx';

const messages = {
  onUpdate: () => MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentUpdated')),
  onCreate: (data) => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentAdded'));
    MailPoet.trackEvent('Segments > Add new', {
      'MailPoet Free version': MailPoet.version,
      type: data.segmentType || 'unknown type',
      subtype: data.action || data.wordpressRole || 'unknown subtype',
    });
  },
};

function getAvailableFilters() {
  const filters = {
    email: MailPoet.I18n.t('email'),
    userRole: MailPoet.I18n.t('wpUserRole'),
  };
  if (window.is_woocommerce_active) {
    filters.woocommerce = MailPoet.I18n.t('woocommerce');
  }
  return filters;
}

const DynamicSegmentForm = ({ match, history }) => {
  const [item, setItem] = useState({
    segmentType: 'email',
    subscribersCount: {
      loading: false,
      count: undefined,
      errors: undefined,
    },
  });
  const [childFields, setChildFields] = useState([]);
  const [errors, setErrors] = useState(undefined);
  const [isFormValid, setIsFormValid] = useState(false);

  function getCount() {
    if (isFormValid) {
      return loadCount(item);
    }

    return Promise.resolve();
  }

  function countLoad() {
    item.subscribersCount = {
      loading: true,
      count: undefined,
      errors: undefined,
    };
    setItem(item);

    getCount().then((response) => {
      item.subscribersCount.loading = false;
      if (response) {
        item.subscribersCount.count = response.count;
        item.subscribersCount.errors = response.errors;
      }
      setItem(item);
    });
  }

  function getChildFields() {
    switch (item.segmentType) {
      case 'userRole':
        return wordpressRoleFields(item);

      case 'email':
        return emailFields(item);

      case 'woocommerce':
        return woocommerceFields(item);

      default: return [];
    }
  }

  function loadFields() {
    getChildFields()
      .then((response) => {
        setChildFields(response.fields);
        setIsFormValid(response.isValid);
      })
      .then(() => countLoad());
  }

  useEffect(() => {
    loadFields();
  });


  function onItemLoad(loadedData) {
    setItem(_.mapObject(loadedData, (val) => (_.isNull(val) ? '' : val)));
    loadFields();
  }

  function getFields() {
    return [
      {
        name: 'name',
        label: MailPoet.I18n.t('name'),
        type: 'text',
      },
      {
        name: 'description',
        label: MailPoet.I18n.t('description'),
        type: 'textarea',
        tip: MailPoet.I18n.t('descriptionTip'),
      },
      {
        name: 'filters',
        description: 'main',
        label: MailPoet.I18n.t('formSegmentTitle'),
        fields: [
          {
            name: 'segmentType',
            type: 'select',
            values: getAvailableFilters(),
          },
          ...childFields,
          {
            name: 'counter',
            type: 'reactComponent',
            component: SubscribersCounter,
          },
        ],
      },
    ];
  }

  function handleValueChange(e) {
    const field = e.target.name;

    item[field] = e.target.value;

    setItem(item);
    loadFields();
    return true;
  }

  function handleSave(e) {
    e.preventDefault();
    setErrors(undefined);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'dynamic_segments',
      action: 'save',
      data: item,
    }).done(() => {
      history.push('/segments');

      if (match.params.id !== undefined) {
        messages.onUpdate();
      } else {
        messages.onCreate(item);
      }
    }).fail((response) => {
      if (response.errors.length > 0) {
        setErrors(response.errors);
      }
    });
  }

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link className="mailpoet-button mailpoet-button-small" to="/segments">{MailPoet.I18n.t('backToList')}</Link>
      </Heading>

      <Form
        endpoint="dynamic_segments"
        fields={getFields()}
        params={getFields()}
        messages={messages}
        onChange={handleValueChange}
        onSubmit={handleSave}
        onItemLoad={onItemLoad}
        item={item}
        errors={errors}
      />
    </>
  );
};

DynamicSegmentForm.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(DynamicSegmentForm);
