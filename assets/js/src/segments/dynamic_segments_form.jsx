import React from 'react';
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
import SubscribersCalculator from './subscribers_calculator.ts';
import subscribersCounter from './subscribers_counter.tsx';

const messages = {
  onUpdate: () => MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentUpdated')),
  onCreate: (data) => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentAdded'));
    MailPoet.trackEvent('Segments > Add new', {
      'MailPoet Free version': window.mailpoet_version,
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

class DynamicSegmentForm extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      item: {
        segmentType: 'email',
        subscribersCount: {
          loading: false,
          count: undefined,
          errors: undefined,
        },
      },
      childFields: [],
      errors: undefined,
    };
    this.loadFields();
    this.handleValueChange = this.handleValueChange.bind(this);
    this.handleSave = this.handleSave.bind(this);
    this.onItemLoad = this.onItemLoad.bind(this);
    this.subscribersCalculator = new SubscribersCalculator();
  }

  onItemLoad(loadedData) {
    const item = _.mapObject(loadedData, (val) => (_.isNull(val) ? '' : val));
    this.setState({ item }, this.loadFields);
  }

  getFields() {
    const { childFields } = this.state;
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
            component: subscribersCounter,
          },
        ],
      },
    ];
  }

  getChildFields() {
    const { item } = this.state;
    switch (item.segmentType) {
      case 'userRole':
        return wordpressRoleFields();

      case 'email':
        return emailFields(item);

      case 'woocommerce':
        return woocommerceFields(item);

      default: return [];
    }
  }

  getCount() {
    const { item } = this.state;
    switch (item.segmentType) {
      case 'userRole':
      case 'email':
      case 'woocommerce':
        return this.subscribersCalculator.loadCount(item);

      default: return Promise.resolve();
    }
  }

  loadFields() {
    this.getChildFields().then((fields) => this.setState({
      childFields: fields,
    }));
  }

  loadCount() {
    const { item } = this.state;
    item.subscribersCount = {
      loading: true,
      count: undefined,
      errors: undefined,
    };

    this.setState({
      item,
    }, () => {
      this.getCount().then((response) => {
        item.subscribersCount.loading = false;
        if (response) {
          item.subscribersCount.count = response.count;
          item.subscribersCount.errors = response.errors;
        }
        this.setState({
          item,
        });
      });
    });
  }

  handleValueChange(e) {
    const { item } = this.state;
    const field = e.target.name;

    item[field] = e.target.value;

    this.setState({
      item,
    });
    this.loadFields();
    this.loadCount();
    return true;
  }

  handleSave(e) {
    const { item } = this.state;
    const { history, match } = this.props;

    e.preventDefault();
    this.setState({ errors: undefined });
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
        this.setState({ errors: response.errors });
      }
    });
  }

  render() {
    const fields = this.getFields();
    const { match } = this.props;
    const { item, errors } = this.state;
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
          fields={fields}
          params={match.params}
          messages={messages}
          onChange={this.handleValueChange}
          onSubmit={this.handleSave}
          onItemLoad={this.onItemLoad}
          item={item}
          errors={errors}
        />
      </>
    );
  }
}

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
