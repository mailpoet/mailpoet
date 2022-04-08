import { MediaUpload } from '@wordpress/block-editor';
import MailPoet from 'mailpoet';
import { Button, BaseControl, SelectControl } from '@wordpress/components';

type Props = {
  name: string;
  imageUrl?: string;
  onImageUrlChange: (value: string) => void;
  imageDisplay?: string;
  onImageDisplayChange: (value: string) => void;
};

function ImageSettings({
  name,
  imageUrl,
  onImageUrlChange,
  imageDisplay,
  onImageDisplayChange,
}: Props): JSX.Element {
  return (
    <div className="mailpoet-styles-settings-image-url">
      <BaseControl.VisualLabel>{name}</BaseControl.VisualLabel>
      <div className="mailpoet-styles-settings-image-url-body">
        <input
          type="text"
          value={imageUrl ?? ''}
          onChange={(event): void => onImageUrlChange(event.target.value)}
        />
        <MediaUpload
          value={imageUrl}
          onSelect={(image: { url: string }): void =>
            onImageUrlChange(image.url)
          }
          allowedTypes={['image']}
          render={({ open }): JSX.Element => (
            <Button isSecondary isSmall onClick={open}>
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
}
export default ImageSettings;
