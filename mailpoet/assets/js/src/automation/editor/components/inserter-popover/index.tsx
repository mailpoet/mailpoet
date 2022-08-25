import { Popover } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { Inserter } from '../inserter';
import { store } from '../../store';

export function InserterPopover(): JSX.Element | null {
  const { inserterPopover } = useSelect(
    (select) => ({
      inserterPopover: select(store).getInserterPopover(),
    }),
    [],
  );

  const { setInserterPopover } = dispatch(store);

  if (!inserterPopover) {
    return null;
  }

  return (
    <Popover
      anchorRect={inserterPopover.anchor.getBoundingClientRect()}
      onClose={() => setInserterPopover(undefined)}
    >
      <Inserter />
    </Popover>
  );
}
