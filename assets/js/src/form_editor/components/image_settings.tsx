import React from 'react';
import { MediaUpload } from '@wordpress/block-editor';
import MailPoet from 'mailpoet';
import { Button } from '@wordpress/components';

type Props = {
  name: string,
  value?: string,
  onChange: (value: string) => any,
}

const ImageSettings = ({
  name,
  value,
  onChange,
}: Props) => (
  <div className="mailpoet-styles-settings-image-url">
    <h3 className="mailpoet-styles-settings-heading">
      {name}
    </h3>
    <div className="mailpoet-styles-settings-image-url-body">
      <input type="text" value={value ?? ''} onChange={(event) => onChange(event.target.value)} />
      <MediaUpload
        value={value}
        onSelect={(image) => onChange(image.url)}
        allowedTypes={['image']}
        render={({ open }) => (
          <Button
            isSecondary
            isSmall
            onClick={open}
          >
            {MailPoet.I18n.t('formSettingsStylesSelectImage')}
          </Button>
        )}
      />
    </div>
  </div>
);

export default ImageSettings;
