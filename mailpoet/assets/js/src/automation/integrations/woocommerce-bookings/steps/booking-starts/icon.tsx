export function Icon(): JSX.Element {
  return (
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      {/* Calendar base */}
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M19 3H18V1H16V3H8V1H6V3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3ZM19 19H5V8H19V19Z"
      />
      {/* Clock circle */}
      <circle cx="15" cy="13" r="4" fill="white" />
      <circle cx="15" cy="13" r="3.2" />
      {/* Clock hands */}
      <path d="M15 11V13L16.5 14" stroke="white" strokeWidth="1" fill="none" />
    </svg>
  );
}
