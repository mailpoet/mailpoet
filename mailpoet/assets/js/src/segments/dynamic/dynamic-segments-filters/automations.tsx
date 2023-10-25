import { useSelect } from '@wordpress/data';
import { FilterProps, AutomationsFormItem } from '../types';
import { storeName } from '../store';
import { AutomationsActionTypes } from './automation-options';
import {
  GeneralAutomationsFields,
  validateAutomationsFields,
} from './fields/automations/general-automations-fields';

export function validateAutomations(formItems: AutomationsFormItem): boolean {
  if (
    !Object.values(AutomationsActionTypes).some((v) => v === formItems.action)
  ) {
    return false;
  }
  if (formItems.action === AutomationsActionTypes.ENTERED_AUTOMATION) {
    return validateAutomationsFields(formItems);
  }
  return true;
}

const componentsMap = {
  [AutomationsActionTypes.ENTERED_AUTOMATION]: GeneralAutomationsFields,
  [AutomationsActionTypes.EXITED_AUTOMATION]: GeneralAutomationsFields,
};

export function AutomationsFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: AutomationsFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const Component = componentsMap[segment.action];

  if (!Component) {
    return null;
  }

  return <Component filterIndex={filterIndex} />;
}
