import React from 'react';

type Props = {
  name: string,
  value?: number|undefined
  onChange: (value: string|undefined) => any
}

const CloseButtonSettings = ({
  name,
  value,
  onChange,
}: Props) => (
  <div>
    <h3 className="mailpoet-styles-settings-heading">
      {name}
    </h3>
  </div>
);

export default CloseButtonSettings;
