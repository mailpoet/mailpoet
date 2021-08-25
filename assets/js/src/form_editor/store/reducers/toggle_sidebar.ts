type ToggleAction = {
  type: string;
  toggleTo: boolean;
}

export const toggleSidebar = (state, action: ToggleAction) => ({
  ...state,
  sidebarOpened: action.toggleTo,
});

export const toggleInserterSidebar = (state, action: ToggleAction) => ({
  ...state,
  isInserterOpened: action.toggleTo,
});
