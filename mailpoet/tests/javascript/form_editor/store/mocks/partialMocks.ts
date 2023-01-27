import { BlockInstance } from '@wordpress/blocks';
import { State } from '../../../../../assets/js/src/form_editor/store/state_types';
import {
  CustomField,
  FormData,
  FormSettingsType,
} from '../../../../../assets/js/src/form_editor/store/form_data_types';

export const createStateMock = (data: Partial<State>): State => data as State;

export const createBlockMock = (
  data: Partial<BlockInstance>,
): BlockInstance => {
  if (!data.innerBlocks || data.innerBlocks === []) {
    return data as BlockInstance;
  }
  const innerBlocks = data.innerBlocks.map((block) => createBlockMock(block));

  return { ...(data as BlockInstance), innerBlocks };
};

export const createBlocksMock = (data: unknown[]): BlockInstance[] =>
  data.map((block) => createBlockMock(block));

export const createFormDataMock = (data: Partial<FormData>): FormData =>
  data as FormData;

export const createFormSettingsMock = (
  data: Partial<FormSettingsType>,
): FormSettingsType => data as FormSettingsType;

export const createCustomFieldMock = (
  data: Partial<CustomField>,
): CustomField => data as CustomField;
