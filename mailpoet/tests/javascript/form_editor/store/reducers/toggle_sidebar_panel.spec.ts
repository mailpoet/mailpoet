import { expect } from 'chai';
import { toggleSidebarPanel as reducer } from '../../../../../assets/js/src/form_editor/store/reducers/toggle_sidebar_panel';
import { ToggleSidebarPanelAction } from '../../../../../assets/js/src/form_editor/store/actions_types';
import { createStateMock } from '../mocks/partialMocks';

describe('Toggle Sidebar Panel Reducer', () => {
  let initialState = createStateMock(null);
  beforeEach(() => {
    initialState = createStateMock({
      isPreviewShown: false, // another unrelated State property
      sidebar: {
        activeSidebar: 'settings',
        activeTab: 'form',
        openedPanels: [],
      },
    });
  });

  it('Should keep unrelated state properties untouched', () => {
    const action: ToggleSidebarPanelAction = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isPreviewShown).to.equal(false);
    expect(finalState.sidebar.activeTab).to.equal('form');
  });

  it('Should open closed panel', () => {
    const action: ToggleSidebarPanelAction = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    const finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.include('nice-panel');
  });

  it('Should close opened panel', () => {
    const action: ToggleSidebarPanelAction = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
    };
    initialState.sidebar.openedPanels.push('nice-panel');
    const finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.not.include('nice-panel');
  });

  it('Should toggle panel to required state', () => {
    const action: ToggleSidebarPanelAction = {
      type: 'TOGGLE_SIDEBAR_PANEL',
      id: 'nice-panel',
      toggleTo: true,
    };
    reducer(initialState, action);
    let finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.include('nice-panel');
    action.toggleTo = false;
    finalState = reducer(initialState, action);
    expect(finalState.sidebar.openedPanels).to.not.include('nice-panel');
  });
});
