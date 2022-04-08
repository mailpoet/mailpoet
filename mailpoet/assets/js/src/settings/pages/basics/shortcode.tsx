import { useState } from 'react';
import { t } from 'common/functions';
import Input from 'common/form/input/input';
import { Label, Inputs, SegmentsSelect } from 'settings/components';

type Props = {
  name: 'mailpoet_archive' | 'mailpoet_subscribers_count';
  title: string;
  description: string;
};

export default function Shortcode({ name, title, description }: Props) {
  const [segments, setSegments] = useState([]);
  const shortcode = `[${name}${
    segments.length ? ` segments="${segments.join(',')}"` : ''
  }]`;
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
        <Input
          dimension="small"
          readOnly
          type="text"
          value={shortcode}
          onClick={selectText}
          id={`${name}-shortcode`}
        />
        <br />
        <SegmentsSelect
          value={segments}
          setValue={setSegments}
          id={`${name}-shortcode-segments`}
          placeholder={t('leaveEmptyToDisplayAll')}
          segmentsSelector="getSegments"
        />
      </Inputs>
    </>
  );
}
