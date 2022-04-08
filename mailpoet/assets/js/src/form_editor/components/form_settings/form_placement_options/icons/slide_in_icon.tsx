import { SVG, Path, Rect, G } from '@wordpress/components';

export default (
  <SVG
    xmlns="http://www.w3.org/2000/svg"
    xmlnsXlink="http://www.w3.org/1999/xlink"
    width="76"
    height="63"
    viewBox="0 0 76 63"
  >
    <defs>
      <filter
        id="slide_in_prefix__a"
        width="216.9%"
        height="245.3%"
        x="-55.8%"
        y="-67.2%"
        filterUnits="objectBoundingBox"
      >
        <feOffset dy="4" in="SourceAlpha" result="shadowOffsetOuter1" />
        <feGaussianBlur
          in="shadowOffsetOuter1"
          result="shadowBlurOuter1"
          stdDeviation="9"
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
      <filter
        id="slide_in_prefix__c"
        width="226.3%"
        height="255.8%"
        x="-73.7%"
        y="-77.9%"
        filterUnits="objectBoundingBox"
      >
        <feOffset dx="-3" in="SourceAlpha" result="shadowOffsetOuter1" />
        <feGaussianBlur
          in="shadowOffsetOuter1"
          result="shadowBlurOuter1"
          stdDeviation="5.5"
        />
        <feColorMatrix
          in="shadowBlurOuter1"
          values="0 0 0 0 0.265158067 0 0 0 0 0.293073922 0 0 0 0 0.400749362 0 0 0 0.145352129 0"
        />
      </filter>
      <Rect
        id="slide_in_prefix__b"
        width="67.556"
        height="54.6"
        x="4.222"
        y="4.2"
        rx=".525"
      />
      <Path
        id="slide_in_prefix__d"
        d="M.525 0h27.45c.29 0 .525.235.525.525v22.05c0 .29-.235.525-.525.525H.525c-.29 0-.525-.235-.525-.525V.525C0 .235.235 0 .525 0z"
      />
    </defs>
    <G fill="none" fillRule="evenodd" filter="url(#slide_in_prefix__a)">
      <Path
        fill="#FFF"
        fillRule="nonzero"
        d="M2.1 0h71.8c1.16 0 2.1.94 2.1 2.1v58.8c0 1.16-.94 2.1-2.1 2.1H2.1C.94 63 0 62.06 0 60.9V2.1C0 .94.94 0 2.1 0z"
      />
      <use fill="#FFF" xlinkHref="#slide_in_prefix__b" />
      <use fill="#E5E9F8" fillOpacity=".4" xlinkHref="#slide_in_prefix__b" />
      <G fillRule="nonzero" transform="translate(43.278 35.7)">
        <use
          fill="#000"
          filter="url(#slide_in_prefix__c)"
          xlinkHref="#slide_in_prefix__d"
        />
        <use fill="#FFF" xlinkHref="#slide_in_prefix__d" />
        <Rect
          width="15.833"
          height="4.2"
          x="6.333"
          y="14.7"
          fill="#FF5301"
          rx=".525"
        />
        <Rect
          width="15.833"
          height="4.2"
          x="6.333"
          y="9.45"
          fill="#FFE0D0"
          rx=".525"
        />
        <Rect
          width="15.833"
          height="4.2"
          x="6.333"
          y="4.2"
          fill="#FFE0D0"
          rx=".525"
        />
      </G>
    </G>
  </SVG>
);
