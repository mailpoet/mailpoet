import React, { useEffect, useState } from 'react';
import {
  assign,
  compose,
  has,
  prop,
} from 'lodash/fp';
import { useRouteMatch, Link, useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import Select from 'common/form/react_select/react_select';
import Textarea from 'common/form/textarea/textarea';
import { EmailSegmentOptions } from './dynamic_segments_filters/email';
import { WooCommerceOptions } from './dynamic_segments_filters/woocommerce';
import { WordpressRoleSegmentOptions } from './dynamic_segments_filters/wordpress_role';
import { WooCommerceSubscriptionOptions } from './dynamic_segments_filters/woocommerce_subscription';
import { SubscribersCounter } from './subscribers_counter';
import { FormFilterFields } from './form_filter_fields';

import {
  AnyFormItem,
  FilterValue,
} from './types';
import APIErrorsNotice from '../../notices/api_errors_notice';

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

type GroupFilterValue = {
  label: string;
  options: FilterValue[];
}

function getAvailableFilters(): GroupFilterValue[] {
  const filters = [
    {
      label: MailPoet.I18n.t('email'),
      options: EmailSegmentOptions,
    },
    {
      label: MailPoet.I18n.t('wpUserRole'),
      options: WordpressRoleSegmentOptions,
    },
  ];
  if (MailPoet.isWoocommerceActive) {
    filters.push({
      label: MailPoet.I18n.t('woocommerce'),
      options: WooCommerceOptions,
    });
  }
  if (MailPoet.isWoocommerceActive) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceSubscriptions'),
      options: WooCommerceSubscriptionOptions,
    });
  }
  return filters;
}

const DynamicSegmentForm: React.FunctionComponent = () => {
  const [segmentFilters] = useState(getAvailableFilters());
  const [errors, setErrors] = useState([]);
  const [segmentType, setSegmentType] = useState<FilterValue | undefined>(undefined);
  const [item, setItem] = useState<AnyFormItem>({});
  const match = useRouteMatch<{id: string}>();
  const history = useHistory();

  useEffect(() => {
    function findSegmentType(itemSearch): FilterValue | undefined {
      let found;
      segmentFilters.forEach((filter) => {
        filter.options.forEach((option) => {
          if (option.group === itemSearch.segmentType) {
            if (itemSearch.action === undefined) {
              found = option;
            } else if (itemSearch.action === option.value) {
              found = option;
            }
          }
        });
      });
      return found;
    }

    function convertSavedData(data: {
      [key: string]: string | number;
    }): AnyFormItem {
      const converted = JSON.parse(JSON.stringify(data));
      // for compatibility with older data
      if (has('link_id', data)) converted.link_id = data.link_id.toString();
      if (has('newsletter_id', data)) converted.newsletter_id = data.newsletter_id.toString();
      if (has('product_id', data)) converted.product_id = data.product_id.toString();
      if (has('category_id', data)) converted.category_id = data.category_id.toString();
      return converted;
    }

    function loadSegment(segmentId): void {
      MailPoet.Ajax.post({
        api_version: MailPoet.apiVersion,
        endpoint: 'dynamic_segments',
        action: 'get',
        data: {
          id: segmentId,
        },
      })
        .done((response) => {
          if (response.data.is_plugin_missing) {
            history.push('/segments');
          } else {
            setItem(convertSavedData(response.data));
            setSegmentType(findSegmentType(response.data));
          }
        })
        .fail(() => {
          history.push('/segments');
        });
    }

    if (match.params.id !== undefined) {
      loadSegment(match.params.id);
    }
  }, [segmentFilters, match.params.id, history]);

  function handleSave(e): void {
    e.preventDefault();
    setErrors([]);
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
    }).fail(compose([setErrors, prop('errors')]));
  }

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors} />
      ))}

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link className="mailpoet-button mailpoet-button-small" to="/segments">{MailPoet.I18n.t('backToList')}</Link>
      </Heading>

      <form className="mailpoet_form">
        <div className="mailpoet-form-grid">
          <div className="mailpoet-form-field-name form-field-row-name">
            <Heading level={4}>
              <label htmlFor="field_name">
                {MailPoet.I18n.t('name')}
              </label>
            </Heading>
            <div className="mailpoet-form-field">
              <Input
                type="text"
                name="name"
                id="field_name"
                defaultValue={item.name}
                onChange={
                  (e): void => setItem(assign(item, { name: e.target.value }))
                }
              />
            </div>
          </div>
          <div className="mailpoet-form-field-description form-field-row-description">
            <Heading level={4}>
              <label htmlFor="field_description">
                {MailPoet.I18n.t('description')}
              </label>
            </Heading>
            <p className="mailpoet-form-description">
              {MailPoet.I18n.t('segmentDescriptionTip')}
            </p>
            <div className="mailpoet-form-field">
              <Textarea
                name="description"
                id="field_description"
                defaultValue={item.description}
                onChange={
                  (e): void => setItem(assign(item, { description: e.target.value }))
                }
              />
            </div>
          </div>
          <div>
            <Heading level={4}>
              <label htmlFor="field_filters">
                {MailPoet.I18n.t('formPageTitle')}
              </label>
            </Heading>
            <Select
              placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
              options={segmentFilters}
              value={segmentType}
              onChange={(newValue: FilterValue): void => {
                setItem(assign(item, {
                  segmentType: newValue.group,
                  action: newValue.value,
                }));
                setSegmentType(newValue);
              }}
              automationId="select-segment-action"
              isFullWidth
            />
            {segmentType !== undefined && (
              <FormFilterFields
                segmentType={segmentType}
                updateItem={setItem}
                item={item}
              />
            )}
          </div>
          <SubscribersCounter item={item} />
          <div className="mailpoet-form-actions">
            <Button type="submit" onClick={handleSave}>
              {MailPoet.I18n.t('save')}
            </Button>
          </div>
        </div>
      </form>
    </>
  );
};

export default DynamicSegmentForm;
