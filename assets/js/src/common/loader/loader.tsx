import React from 'react';

type Props = {
  variant?: 'light' | 'dark'
  size?: number
};

const Loader = ({ variant, size }: Props) => {
  let color = '#ff5301';
  if (variant === 'light') color = '#ffe0d0';
  if (variant === 'dark') color = '#071c6d';
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      style={{
        width: `${size || 34}px`,
        height: `${size || 34}px`,
        margin: 'auto',
        background: 'none',
        WebkitAnimationPlayState: 'running',
        animationPlayState: 'running',
        WebkitAnimationDelay: '0s',
        animationDelay: '0s',
      }}
      display="block"
      preserveAspectRatio="xMidYMid"
      viewBox="0 0 100 100"
    >
      <circle
        cx="50"
        cy="50"
        r="45"
        fill="none"
        stroke={color}
        strokeDasharray="150.79644737231007 52.26548245743669"
        strokeWidth="10"
        style={{
          WebkitAnimationPlayState: 'running',
          animationPlayState: 'running',
          WebkitAnimationDelay: '0s',
          animationDelay: '0s',
        }}
      >
        <animateTransform
          attributeName="transform"
          dur="1s"
          keyTimes="0;1"
          repeatCount="indefinite"
          type="rotate"
          values="0 50 50;360 50 50"
        />
      </circle>
    </svg>
  );
};

export default Loader;
