import { hasBlockSupport, getBlockSupport } from '@wordpress/blocks';
import { Fill, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export const hasBackgroundImageSupport = (nameOrType: string) => {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore not yet supported in the types
  const backgroundSupport = getBlockSupport(nameOrType, 'background') as Record<
    string,
    boolean
  >;

  return backgroundSupport && backgroundSupport?.backgroundImage !== false;
};

export function BlockCompatibilityWarnings(): JSX.Element {
  // Select the currently selected block
  const selectedBlock = useSelect(
    (sel) => sel('core/block-editor').getSelectedBlock(),
    [],
  );

  // Check if the selected block has enabled border configuration
  const hasBorderSupport = hasBlockSupport(
    selectedBlock?.name,
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore - border is not yet supported in the types
    '__experimentalBorder',
    false,
  );

  return (
    <>
      {hasBorderSupport && (
        <Fill name="InspectorControlsBorder">
          <Notice
            className="mailpoet__grid-full-width"
            status="warning"
            isDismissible={false}
          >
            {__(
              'Border display may vary or be unsupported in some email clients.',
              'mailpoet',
            )}
          </Notice>
        </Fill>
      )}
      {hasBackgroundImageSupport(selectedBlock?.name) && (
        <Fill name="InspectorControlsBackground">
          <Notice
            className="mailpoet__grid-full-width"
            status="warning"
            isDismissible={false}
          >
            {__(
              'Select a background color for email clients that do not support background images.',
              'mailpoet',
            )}
          </Notice>
        </Fill>
      )}
    </>
  );
}
