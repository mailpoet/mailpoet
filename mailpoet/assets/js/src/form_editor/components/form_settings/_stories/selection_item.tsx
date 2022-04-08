import { action } from '_storybook/action';
import SelectionItem from '../selection_item';

export default {
  title: 'FormEditor/Selection Item',
};

export function CloseIconSettings(): JSX.Element {
  return (
    <div className="close-button-selection-item-list">
      <SelectionItem label="kjk1" active={false} onClick={action('on change')}>
        <div>Inactive</div>
      </SelectionItem>
      <SelectionItem
        label="kjk3"
        active={false}
        canBeActive={false}
        onClick={action('on change')}
      >
        <div>Cannot Activate</div>
      </SelectionItem>
      <SelectionItem label="kjk2" active onClick={action('on change')}>
        <div>Active</div>
      </SelectionItem>
      <SelectionItem
        label="kjk3"
        active={false}
        displaySettingsIcon={false}
        onClick={action('on change')}
      >
        <div>Without Settings Icon</div>
      </SelectionItem>
      <SelectionItem
        label="kjk3"
        active
        displaySettingsIcon={false}
        canBeActive={false}
        onClick={action('on change')}
      >
        <div>Without Settings Icon</div>
      </SelectionItem>
      <SelectionItem
        label="kjk3"
        active
        displaySettingsIcon={false}
        onClick={action('on change')}
      >
        <div>Without Settings Icon</div>
      </SelectionItem>
    </div>
  );
}
