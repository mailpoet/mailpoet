function asNum(num?: string): number | undefined {
  const numI = parseInt(num, 10);
  if (Number.isNaN(numI)) {
    return undefined;
  }
  return numI;
}

export default asNum;
