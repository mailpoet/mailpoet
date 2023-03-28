import { Item } from './item';
import { StepIcon } from '../step-icon';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/block-editor/src/components/inserter/preview-panel.js

type Props = {
  item: Item;
};

export function StepInfoPanel({ item }: Props): JSX.Element {
  const { title, icon, description } = item;
  return (
    <div className="block-editor-inserter__preview-container">
      <div className="block-editor-block-card">
        <StepIcon icon={icon} />
        <div className="block-editor-block-card__content">
          <h2 className="block-editor-block-card__title">
            {title(null, 'inserter')}
          </h2>
          <span className="block-editor-block-card__description">
            {description(null, 'inserter')}
          </span>
        </div>
      </div>
    </div>
  );
}
