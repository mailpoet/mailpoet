import { SVG, Path, Rect, G } from '@wordpress/components';

export default (
  <SVG
    width="144"
    height="120"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 144 120"
  >
    <defs>
      <Rect id="fixed_bar_a" x="8" y="24" width="128" height="88" rx="1" />
    </defs>
    <G fill="none" fillRule="evenodd">
      <Path
        d="M4 0h136a4 4 0 0 1 4 4v112a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
        fill="#FFF"
        fillRule="nonzero"
      />
      <use fill="#FFF" xlinkHref="#a" />
      <use fillOpacity=".4" fill="#E5E9F8" xlinkHref="#fixed_bar_a" />
      <Rect
        fill="#FF5301"
        fillRule="nonzero"
        x="98"
        y="8"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="53"
        y="8"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="8"
        y="8"
        width="38"
        height="8"
        rx="1"
      />
    </G>
  </SVG>
);
