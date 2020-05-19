import React from 'react';
import { MediaUpload } from '@wordpress/block-editor';
import MailPoet from 'mailpoet';
import { Button, SelectControl } from '@wordpress/components';
import { customFieldTypes } from '../blocks/add_custom_field/add_custom_field_form';

type Props = {
  name: string,
  imageUrl?: string,
  onImageUrlChange: (value: string) => any,
  imageDisplay?: string,
  onImageDisplayChange: (value: string) => any,
}

const ImageSettings = ({
  name,
  imageUrl,
  onImageUrlChange,
  imageDisplay,
  onImageDisplayChange,
}: Props) => (
  <div className="mailpoet-styles-settings-image-url">
    <h3 className="mailpoet-styles-settings-heading">
      {name}
    </h3>
    <div className="mailpoet-styles-settings-image-url-body">
      <input type="text" value={imageUrl ?? ''} onChange={(event) => onImageUrlChange(event.target.value)} />
      <MediaUpload
        value={imageUrl}
        onSelect={(image) => onImageUrlChange(image.url)}
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
    <div className="mailpoet-styles-settings-image-url-display">
      <SelectControl
        value={imageDisplay}
        options={[
          {
            label: MailPoet.I18n.t('imagePlacementScale'),
            value: 'scale',
          },
          {
            label: MailPoet.I18n.t('imagePlacementFit'),
            value: 'fit',
          },
          {
            label: MailPoet.I18n.t('imagePlacementTile'),
            value: 'tile',
          },
        ]}
        onChange={onImageDisplayChange}
      />
    </div>
  </div>
);

export default ImageSettings;
