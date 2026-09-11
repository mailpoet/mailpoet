import { Card, Stack } from '@wordpress/ui';

export type EditorChoice = 'classic' | 'block';

type EditorChoiceOptionProps = {
  value: EditorChoice;
  title: React.ReactNode;
  description: string;
  illustration?: React.ReactNode;
  isSelected: boolean;
  onSelect: (value: EditorChoice) => void;
};

export function EditorChoiceOption({
  value,
  title,
  description,
  illustration,
  isSelected,
  onSelect,
}: EditorChoiceOptionProps): JSX.Element {
  return (
    <Card.Root
      render={
        // eslint-disable-next-line jsx-a11y/control-has-associated-label -- the label comes from the Card.Title children Card.Root renders into this button.
        <button
          type="button"
          role="radio"
          aria-checked={isSelected}
          onClick={() => onSelect(value)}
          data-automation-id={`editor_choice_${value}`}
        />
      }
      className={`mailpoet-editor-choice-modal__option${
        isSelected ? ' is-selected' : ''
      }`}
    >
      <Card.Header>
        <Card.Title>{title}</Card.Title>
      </Card.Header>
      <Card.Content>
        <Stack direction="column" gap="md">
          {illustration && (
            <div className="mailpoet-editor-choice-modal__option-illustration">
              {illustration}
            </div>
          )}
          {description}
        </Stack>
      </Card.Content>
    </Card.Root>
  );
}
