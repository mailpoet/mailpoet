/* eslint-disable @typescript-eslint/no-explicit-any */
import React from 'react';
import { useSelector } from 'settings/store/hooks';
import ReactSelect from 'common/form/react_select/react_select';

type Props = {
  id: string;
  value: string[];
  placeholder?: string;
  setValue: (x: string[]) => void;
  segmentsSelector?: 'getDefaultSegments' | 'getSegments';
}

export default (props: Props) => {
  const selector = props.segmentsSelector ? props.segmentsSelector : 'getDefaultSegments';
  const segments = useSelector(selector)().map((segment) => ({
    value: segment.id,
    label: segment.name,
    count: segment.subscribers,
  }));

  const defaultValue = segments.filter((segment) => props.value.includes(segment.value));

  return (
    <ReactSelect
      isMulti
      defaultValue={defaultValue}
      id={props.id}
      placeholder={props.placeholder}
      options={segments}
      onChange={(selectedValues: any) => {
        props.setValue((selectedValues || []).map((x: any) => x.value));
      }}
    />
  );
};
