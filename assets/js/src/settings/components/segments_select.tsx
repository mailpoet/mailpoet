import React from 'react';
import $ from 'jquery';
import 'select2';
import { useSelector } from 'settings/store/hooks';

type Props = {
  id: string
  value: string[]
  placeholder?: string
  setValue: (x: string[]) => any
}

export default (props: Props) => {
  const id = props.id;
  const setValue = React.useCallback(props.setValue, []);
  const segments = useSelector('getSegments')();
  React.useLayoutEffect(() => {
    const idSelector = `#${id}`;
    $(idSelector).select2();
    $(idSelector).on('change', (e) => {
      const value = Array.from(e.target.selectedOptions).map((x: any) => x.value);
      setValue(value);
    });
    return () => $(idSelector).select2('destroy');
  }, [id, setValue]);

  return (
    <select id={id} data-placeholder={props.placeholder} defaultValue={props.value} multiple>
      {segments.map((seg) => (
        <option key={seg.id} value={seg.id}>
          {`${seg.name} (${seg.subscribers})`}
        </option>
      ))}
    </select>
  );
};
