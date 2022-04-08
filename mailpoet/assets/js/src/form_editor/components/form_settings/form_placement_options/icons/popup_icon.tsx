import { SVG, Path, Rect, G } from '@wordpress/components';

export default (
  <SVG
    width="144"
    height="120"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 144 120"
  >
    <defs>
      <Rect id="popup_icon_a" x="8" y="8" width="128" height="104" rx="1" />
      <Path
        d="M49 34h46a4 4 0 0 1 4 4v40a4 4 0 0 1-4 4H49a4 4 0 0 1-4-4V38a4 4 0 0 1 4-4z"
        id="popup_icon_c"
      />
      <filter
        x="-61.1%"
        y="-56.2%"
        width="222.2%"
        height="237.5%"
        filterUnits="objectBoundingBox"
        id="popup_icon_b"
      >
        <feOffset dy="6" in="SourceAlpha" result="shadowOffsetOuter1" />
        <feGaussianBlur
          stdDeviation="10"
          in="shadowOffsetOuter1"
          result="shadowBlurOuter1"
        />
        <feColorMatrix
          values="0 0 0 0 0.265158067 0 0 0 0 0.293073922 0 0 0 0 0.400749362 0 0 0 0.145352129 0"
          in="shadowBlurOuter1"
        />
      </filter>
    </defs>
    <G fill="none" fillRule="evenodd">
      <Path
        d="M4 0h136a4 4 0 0 1 4 4v112a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
        fill="#FFF"
        fillRule="nonzero"
      />
      <use fill="#FFF" xlinkHref="#popup_icon_a" />
      <use fillOpacity=".4" fill="#E5E9F8" xlinkHref="#popup_icon_a" />
      <G fillRule="nonzero">
        <use
          fill="#000"
          filter="url(#popup_icon_b)"
          xlinkHref="#popup_icon_c"
        />
        <use fill="#FFF" xlinkHref="#popup_icon_c" />
      </G>
      <Rect
        fill="#FF5301"
        fillRule="nonzero"
        x="53"
        y="66"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="53"
        y="54"
        width="38"
        height="8"
        rx="1"
      />
      <Rect
        fill="#FFE0D0"
        fillRule="nonzero"
        x="53"
        y="42"
        width="38"
        height="8"
        rx="1"
      />
    </G>
  </SVG>
);
