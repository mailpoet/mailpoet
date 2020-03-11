import React from 'react';
import $ from 'jquery';
import 'select2';

type Props = {
  id: string
  value: string[]
  setValue: (x: string[]) => any
}

export default ({ id, value, setValue }: Props) => {
  React.useLayoutEffect(() => {
    const idSelector = `#${id}`;
    $(idSelector).select2();
    $(idSelector).on('change', (e) => {
      setValue(Array.from(e.target.selectedOptions).map((x: any) => x.value));
    });
    return () => $(idSelector).select2('destroy');
  }, [id, setValue]);
  const segments: any[] = (window as any).mailpoet_segments;
  return (
    <select id={id} defaultValue={value} multiple>
      {segments.map((seg) => (
        <option key={seg.id} value={seg.id}>
          {`${seg.name} (${seg.subscribers})`}
        </option>
      ))}
    </select>
  );
};
