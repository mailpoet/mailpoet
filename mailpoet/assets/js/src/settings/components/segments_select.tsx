/* eslint-disable @typescript-eslint/no-explicit-any */
import { useSelector } from 'settings/store/hooks';
import ReactSelect from 'common/form/react_select/react_select';

type Props = {
  id: string;
  value: string[];
  placeholder?: string;
  setValue: (x: string[]) => void;
  segmentsSelector?: 'getDefaultSegments' | 'getSegments';
};

export default function SegmentsSelect(props: Props) {
  const selector = props.segmentsSelector
    ? props.segmentsSelector
    : 'getDefaultSegments';
  const segments = useSelector(selector)().map((segment) => ({
    value: segment.id,
    label: segment.name,
    count: segment.subscribers,
  }));

  const defaultValue = segments.filter((segment) =>
    props.value.includes(segment.value),
  );

  return (
    <ReactSelect
      isMulti
      defaultValue={defaultValue}
      id={props.id}
      placeholder={props.placeholder}
      options={segments}
      onChange={(selectedValues: { value: string }[]) => {
        props.setValue((selectedValues || []).map((x) => x.value));
      }}
    />
  );
}
