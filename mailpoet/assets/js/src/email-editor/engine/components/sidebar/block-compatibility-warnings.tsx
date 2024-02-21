import { hasBlockSupport } from '@wordpress/blocks';
import { Fill, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

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

  return hasBorderSupport ? (
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
  ) : null;
}
