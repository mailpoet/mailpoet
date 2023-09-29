import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { filter } from 'lodash/fp';
import { Select } from 'common/form/select/select';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react-select/react-select';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  WindowWooCommerceCountries,
  WooCommerceFormItem,
} from '../../../types';

export function validateCustomerInCountry(
  formItems: WooCommerceFormItem,
): boolean {
  const countryCodeIsInvalid =
    formItems.country_code === undefined || formItems.country_code.length === 0;

  return !countryCodeIsInvalid;
}

export function CustomerInCountryFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);
  const woocommerceCountries: WindowWooCommerceCountries = useSelect(
    (select) => select(storeName).getWooCommerceCountries(),
    [],
  );
  const countryOptions = woocommerceCountries.map((country) => ({
    value: country.code,
    label: country.name,
  }));

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  return (
    <>
      <Select
        key="select-operator-country"
        value={segment.operator}
        isMinWidth
        onChange={(e): void => {
          void updateSegmentFilter({ operator: e.target.value }, filterIndex);
        }}
        automationId="select-operator-country"
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
      <ReactSelect
        dimension="small"
        key="select-segment-country"
        isMulti
        placeholder={MailPoet.I18n.t('selectWooCountry')}
        options={countryOptions}
        value={filter((option) => {
          if (!segment.country_code) return undefined;
          return segment.country_code.indexOf(option.value) !== -1;
        }, countryOptions)}
        onChange={(options: SelectOption[]): void => {
          void updateSegmentFilter(
            {
              country_code: (options || []).map((x: SelectOption) => x.value),
            },
            filterIndex,
          );
        }}
        automationId="select-segment-country"
      />
    </>
  );
}
