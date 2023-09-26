import { action } from '_storybook/action';
import { FormPlacementOption } from '../form-placement-option';
import { SidebarIcon } from '../icons/sidebar-icon';

export default {
  title: 'FormEditor/Form Placement Options',
};

export function Options(): JSX.Element {
  return (
    <>
      <FormPlacementOption
        label="Active option"
        icon={SidebarIcon}
        active
        onClick={action('active option click')}
      />
      <FormPlacementOption
        label="Inactive option"
        icon={SidebarIcon}
        active={false}
        onClick={action('inactive option click')}
      />
      <FormPlacementOption
        label="Always inactive"
        icon={SidebarIcon}
        active
        canBeActive={false}
        onClick={action('inactive option click')}
      />
    </>
  );
}

export function OptionsListInSidebar(): JSX.Element {
  return (
    <div className="edit-post-sidebar mailpoet_form_editor_sidebar">
      <div className="form-placement-option-list">
        <FormPlacementOption
          label="Active option"
          icon={SidebarIcon}
          active
          onClick={action('active option click')}
        />
        <FormPlacementOption
          label="Inactive option"
          icon={SidebarIcon}
          active={false}
          onClick={action('inactive option click')}
        />
        <FormPlacementOption
          label="Always inactive"
          icon={SidebarIcon}
          active
          canBeActive={false}
          onClick={action('inactive option click')}
        />
      </div>
    </div>
  );
}
