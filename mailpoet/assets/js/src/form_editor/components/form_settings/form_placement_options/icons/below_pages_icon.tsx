import { SVG, Path, Rect, G } from '@wordpress/components';

export default (
  <SVG
    width="144"
    height="120"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 144 120"
  >
    <defs>
      <Rect id="a" x="8" y="8" width="128" height="68" rx="1" />
      <Rect id="b" x="8" y="100" width="128" height="12" rx="1" />
    </defs>
    <G fill="none" fillRule="evenodd">
      <Path
        d="M4 0h136a4 4 0 0 1 4 4v112a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
        fill="#FFF"
        fillRule="nonzero"
      />
      <use fill="#FFF" xlinkHref="#a" />
      <use fillOpacity=".4" fill="#E5E9F8" xlinkHref="#a" />
      <use fill="#FFF" xlinkHref="#b" />
      <use fillOpacity=".4" fill="#E5E9F8" xlinkHref="#b" />
      <Rect
        fill="#FF5301"
        fillRule="nonzero"
        x="98"
        y="84"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="53"
        y="84"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="8"
        y="84"
        width="38"
        height="8"
        rx="1"
      />
    </G>
  </SVG>
);
