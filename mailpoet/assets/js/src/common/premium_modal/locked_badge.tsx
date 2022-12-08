const lockIcon = (
  <svg
    width="12"
    height="12"
    viewBox="0 0 12 12"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <g clipPath="url(#clip0_1896_34966)">
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M6 1.625C4.96447 1.625 4.125 2.46447 4.125 3.5V5H3.5C3.22386 5 3 5.22386 3 5.5V9.5C3 9.77614 3.22386 10 3.5 10H8.5C8.77614 10 9 9.77614 9 9.5V5.5C9 5.22386 8.77614 5 8.5 5H7.875V3.5C7.875 2.46447 7.03553 1.625 6 1.625ZM7.125 5V3.5C7.125 2.87868 6.62132 2.375 6 2.375C5.37868 2.375 4.875 2.87868 4.875 3.5V5H7.125Z"
        fill="#BD8600"
      />
    </g>
    <defs>
      <clipPath id="clip0_1896_34966">
        <rect width="12" height="12" fill="white" />
      </clipPath>
    </defs>
  </svg>
);

export function LockedBadge({ text }): JSX.Element {
  return (
    <p className="mailpoet-locked-badge">
      {lockIcon} {text}
    </p>
  );
}
