import { Popover } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { Inserter } from '../inserter';
import { store } from '../../store';

export function InserterPopover(): JSX.Element | null {
  const { inserterPopoverAnchor } = useSelect(
    (select) => ({
      inserterPopoverAnchor: select(store).getInserterPopoverAnchor(),
    }),
    [],
  );

  const { setInserterPopoverAnchor } = dispatch(store);

  if (!inserterPopoverAnchor) {
    return null;
  }

  return (
    <Popover
      anchorRect={inserterPopoverAnchor.getBoundingClientRect()}
      onClose={() => setInserterPopoverAnchor(undefined)}
    >
      <Inserter />
    </Popover>
  );
}
