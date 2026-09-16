import { useId } from 'react';
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
  const titleId = useId();
  const descriptionId = useId();

  return (
    <Card.Root
      render={
        <button
          type="button"
          aria-pressed={isSelected}
          aria-labelledby={titleId}
          aria-describedby={descriptionId}
          onClick={() => onSelect(value)}
          data-automation-id={`editor_choice_${value}`}
        />
      }
      className={`mailpoet-editor-choice-modal__option${
        isSelected ? ' is-selected' : ''
      }`}
    >
      <Card.Header render={<span />}>
        <Card.Title render={<span />} id={titleId}>
          {title}
        </Card.Title>
      </Card.Header>
      <Card.Content render={<span />}>
        <Stack render={<span />} direction="column" gap="md">
          {illustration && (
            <span
              className="mailpoet-editor-choice-modal__option-illustration"
              aria-hidden="true"
            >
              {illustration}
            </span>
          )}
          <span id={descriptionId}>{description}</span>
        </Stack>
      </Card.Content>
    </Card.Root>
  );
}
