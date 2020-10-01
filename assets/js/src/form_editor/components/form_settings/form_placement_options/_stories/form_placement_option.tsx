import React from 'react';
import { action } from '_storybook/action';
import Option from '../form_placement_option';
import Icon from '../icons/sidebar_icon';

export default {
  title: 'FormEditor/Form Placement Options',
};

export const Options = () => (
  <>
    <Option
      label="Active option"
      icon={Icon}
      active
      onClick={action('active option click')}
    />
    <Option
      label="Inactive option"
      icon={Icon}
      active={false}
      onClick={action('inactive option click')}
    />
    <Option
      label="Always inactive"
      icon={Icon}
      active
      canBeActive={false}
      onClick={action('inactive option click')}
    />
  </>
);

export const OptionsListInSidebar = () => (
  <div className="edit-post-sidebar mailpoet_form_editor_sidebar">
    <div className="form-placement-option-list">
      <Option
        label="Active option"
        icon={Icon}
        active
        onClick={action('active option click')}
      />
      <Option
        label="Inactive option"
        icon={Icon}
        active={false}
        onClick={action('inactive option click')}
      />
      <Option
        label="Always inactive"
        icon={Icon}
        active
        canBeActive={false}
        onClick={action('inactive option click')}
      />
    </div>
  </div>
);
