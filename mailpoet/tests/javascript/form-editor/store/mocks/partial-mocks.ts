import { Block } from '@wordpress/blocks';
import { State } from '../../../../../assets/js/src/form-editor/store/state-types';
import {
  CustomField,
  FormData,
  FormSettingsType,
} from '../../../../../assets/js/src/form-editor/store/form-data-types';

export const createStateMock = (data: Partial<State>): State => data as State;

export const createBlockMock = (data: Partial<Block>): Block => {
  if (!data.innerBlocks || data.innerBlocks.length === 0) {
    return data as Block;
  }
  const innerBlocks = data.innerBlocks.map((block) => createBlockMock(block));

  return { ...(data as Block), innerBlocks };
};

export const createBlocksMock = (data: unknown[]): Block[] =>
  data.map((block) => createBlockMock(block));

export const createFormDataMock = (data: Partial<FormData>): FormData =>
  data as FormData;

export const createFormSettingsMock = (
  data: Partial<FormSettingsType>,
): FormSettingsType => data as FormSettingsType;

export const createCustomFieldMock = (
  data: Partial<CustomField>,
): CustomField => data as CustomField;
