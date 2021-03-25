import React, { useEffect, useState } from 'react';
import {
  assign,
  compose,
  prop,
} from 'lodash/fp';
import { useRouteMatch, Link, useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import Select from 'common/form/react_select/react_select';
import { EmailSegmentOptions } from './dynamic_segments_filters/email';
import { WooCommerceOptions } from './dynamic_segments_filters/woocommerce';
import { WordpressRoleSegmentOptions } from './dynamic_segments_filters/wordpress_role';
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
          setItem(response.data);
          setSegmentType(findSegmentType(response.data));
        })
        .fail(compose([setErrors, prop('errors')]));
    }

    if (match.params.id !== undefined) {
      loadSegment(match.params.id);
    }
  }, [segmentFilters, match.params.id]);

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
              <div className="regular-text mailpoet-form-input">
                <input
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
              <div className="mailpoet-form-textarea">
                <textarea
                  name="description"
                  id="field_description"
                  defaultValue={item.description}
                  onChange={
                    (e): void => setItem(assign(item, { description: e.target.value }))
                  }
                />
              </div>
            </div>
          </div>
          <div className="mailpoet-form-field-filters form-field-row-filters">
            <h4 className="mailpoet-h4">
              <label htmlFor="field_filters">{MailPoet.I18n.t('formPageTitle')}</label>
            </h4>
            <div className="mailpoet-form-field">
              <div className="mailpoet-form-input mailpoet-form-select" data-automation-id="select-segment-action">
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
                />
              </div>
            </div>
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
            <button type="submit" className="mailpoet-button" onClick={handleSave}>
              <span>
                {MailPoet.I18n.t('save')}
              </span>
            </button>
          </div>
        </div>
      </form>
    </>
  );
};

export default DynamicSegmentForm;
