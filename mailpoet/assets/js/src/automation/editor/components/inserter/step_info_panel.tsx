import { StepIcon } from './step_icon';
import { Item } from './item';

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
          <h2 className="block-editor-block-card__title">{title}</h2>
          <span className="block-editor-block-card__description">
            {description}
          </span>
        </div>
      </div>
    </div>
  );
}
