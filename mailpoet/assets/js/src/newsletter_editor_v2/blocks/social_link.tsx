import { addFilter } from '@wordpress/hooks';
import { useBlockProps } from '@wordpress/block-editor';

export const registerLink = () => {
  const modifySettings = (settings, name) => {
    if (name !== 'core/social-link') {
      return settings;
    }
    // eslint-disable-next-line no-param-reassign
    settings.edit = function Edit({ attributes }): JSX.Element {
      const blockProps = useBlockProps();
      return <span {...blockProps}>{attributes.service}</span>;
    };
    return settings;
  };

  addFilter(
    'blocks.registerBlockType',
    'mailpeot/social-link-modifications-register',
    modifySettings,
  );
};
