import { Item } from '../components/inserter/item';

export type State = {
  inserter: {
    actionSteps: Item[];
    logicalSteps: Item[];
  };
  inserterSidebar: {
    isOpened: boolean;
  };
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
