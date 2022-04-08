import { SVG, Path, Rect, G } from '@wordpress/components';

export default (
  <SVG
    xmlns="http://www.w3.org/2000/svg"
    width="144"
    height="120"
    viewBox="0 0 144 120"
  >
    <defs>
      <Rect
        id="sidebar_icon_prefix__b"
        width="82"
        height="104"
        x="8"
        y="8"
        rx="1"
      />
      <Rect
        id="sidebar_icon_prefix__c"
        width="38"
        height="30"
        x="98"
        y="8"
        rx="1"
      />
      <Rect
        id="sidebar_icon_prefix__d"
        width="38"
        height="30"
        x="98"
        y="82"
        rx="1"
      />
      <filter
        id="sidebar_icon_prefix__a"
        width="212.5%"
        height="235%"
        x="-56.2%"
        y="-67.5%"
        filterUnits="objectBoundingBox"
      >
        <feOffset dy="7" in="SourceAlpha" result="shadowOffsetOuter1" />
        <feGaussianBlur
          in="shadowOffsetOuter1"
          result="shadowBlurOuter1"
          stdDeviation="17.5"
        />
        <feColorMatrix
          in="shadowBlurOuter1"
          result="shadowMatrixOuter1"
          values="0 0 0 0 0.265158067 0 0 0 0 0.293073922 0 0 0 0 0.400749362 0 0 0 0.145352129 0"
        />
        <feMerge>
          <feMergeNode in="shadowMatrixOuter1" />
          <feMergeNode in="SourceGraphic" />
        </feMerge>
      </filter>
    </defs>
    <G fill="none" fillRule="evenodd" filter="url(#sidebar_icon_prefix__a)">
      <Path
        fill="#FFF"
        fillRule="nonzero"
        d="M4 0h136c2.21 0 4 1.79 4 4v112c0 2.21-1.79 4-4 4H4c-2.21 0-4-1.79-4-4V4c0-2.21 1.79-4 4-4z"
      />
      <use fill="#FFF" xlinkHref="#sidebar_icon_prefix__b" />
      <use
        fill="#E5E9F8"
        fillOpacity=".4"
        xlinkHref="#sidebar_icon_prefix__b"
      />
      <use fill="#FFF" xlinkHref="#sidebar_icon_prefix__c" />
      <use
        fill="#E5E9F8"
        fillOpacity=".4"
        xlinkHref="#sidebar_icon_prefix__c"
      />
      <use fill="#FFF" xlinkHref="#sidebar_icon_prefix__d" />
      <use
        fill="#E5E9F8"
        fillOpacity=".4"
        xlinkHref="#sidebar_icon_prefix__d"
      />
      <Rect
        width="38"
        height="8"
        x="98"
        y="66"
        fill="#FF5301"
        fillRule="nonzero"
        rx="1"
      />
      <Rect
        width="38"
        height="8"
        x="98"
        y="56"
        fill="#FFE0D0"
        fillRule="nonzero"
        rx="1"
      />
      <Rect
        width="38"
        height="8"
        x="98"
        y="46"
        fill="#FFE0D0"
        fillRule="nonzero"
        rx="1"
      />
    </G>
  </SVG>
);
