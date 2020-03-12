import React from 'react';
import { t } from 'settings/utils';
import { Label, Inputs, SegmentsSelect } from 'settings/components';

type Props = {
  name: 'mailpoet_archive' | 'mailpoet_subscribers_count'
  title: string
  description: string
}

export default function Shortcode({ name, title, description }: Props) {
  const [segments, setSegments] = React.useState([]);
  const shortcode = `[${name}${segments.length ? ` segments="${segments.join(',')}"` : ''}]`;
  const selectText = (event) => {
    event.target.focus();
    event.target.select();
  };
  return (
    <>
      <Label
        title={title}
        description={description}
        htmlFor={`${name}-shortcode`}
      />
      <Inputs>
        <input
          readOnly
          type="text"
          value={shortcode}
          onClick={selectText}
          className="regular-text"
          id={`${name}-shortcode`}
        />
        <br />
        <SegmentsSelect
          value={segments}
          setValue={setSegments}
          id={`${name}-shortcode-segments`}
          placeholder={t`leaveEmptyToDisplayAll`}
        />
      </Inputs>
    </>
  );
}
