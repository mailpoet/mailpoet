import React, { useState, useEffect } from 'react';
import _ from 'underscore';
import {
  Link,
  match as matchType,
  RouteComponentProps,
  withRouter,
} from 'react-router-dom';
import MailPoet from 'mailpoet';
import Form from 'form/form.jsx';

import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import wordpressRoleFields from './dynamic_segments_filters/wordpress_role.jsx';
import emailFields from './dynamic_segments_filters/email.jsx';
import woocommerceFields from './dynamic_segments_filters/woocommerce.jsx';
import { loadCount, Result } from './subscribers_calculator';
import { SubscribersCounter } from './subscribers_counter';

type Filters = Record<string, string>;

const messages = {
  onUpdate: (): void => MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentUpdated')),
  onCreate: (data): void => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentAdded'));
    MailPoet.trackEvent('Segments > Add new', {
      'MailPoet Free version': MailPoet.version,
      type: data.segmentType || 'unknown type',
      subtype: data.action || data.wordpressRole || 'unknown subtype',
    });
  },
};

function getAvailableFilters(): Filters {
  const filters: Filters = {
    email: MailPoet.I18n.t('email'),
    userRole: MailPoet.I18n.t('wpUserRole'),
  };
  if (MailPoet.isWoocommerceActive) {
    filters.woocommerce = MailPoet.I18n.t('woocommerce');
  }
  return filters;
}

interface UrlProps {
  id?: string;
}

interface Props {
  history: RouteComponentProps['history'];
  match: matchType<UrlProps>;
}

// object/any for now until we have properly typed form fields
interface FilterFieldType {
  isValid: boolean;
  fields: object[];
}

const DynamicSegmentForm: React.FunctionComponent<Props> = ({ match, history }) => {
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

  function getCount(): Promise<Result | void> {
    if (isFormValid) {
      return loadCount(item);
    }

    return Promise.resolve();
  }

  function countLoad(): void {
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
    }, (errorResponse) => {
      item.subscribersCount.loading = false;
      item.subscribersCount.count = undefined;
      item.subscribersCount.errors = errorResponse.errors.map((error) => error.message);
      setItem(item);
    });
  }

  function getChildFields(): Promise<FilterFieldType> {
    switch (item.segmentType) {
      case 'userRole':
        return wordpressRoleFields(item);

      case 'email':
        return emailFields(item);

      case 'woocommerce':
        return woocommerceFields(item);

      default: return Promise.resolve({ fields: [], isValid: false });
    }
  }

  function loadFields(): void {
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


  function onItemLoad(loadedData): void {
    setItem(_.mapObject(loadedData, (val) => (_.isNull(val) ? '' : val)));
    loadFields();
  }

  function getFields(): object[] {
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

  function handleValueChange(e): boolean {
    const field = e.target.name;

    item[field] = e.target.value;

    setItem(item);
    loadFields();
    return true;
  }

  function handleSave(e): void {
    e.preventDefault();
    setErrors(undefined);
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
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
        params={match.params}
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

export default withRouter(DynamicSegmentForm);
