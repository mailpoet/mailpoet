import { check, closeSmall, Icon } from '@wordpress/icons';

type Props = {
  index: number;
};

export function BranchBadge({ index }: Props): JSX.Element {
  return index === 0 ? (
    <div className="mailpoet-automation-if-else-yes">
      <Icon icon={check} />
    </div>
  ) : (
    <div className="mailpoet-automation-if-else-no">
      <Icon icon={closeSmall} />
    </div>
  );
}
