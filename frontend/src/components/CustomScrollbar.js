import React, { useEffect, useState, useRef } from 'react';
const CustomScrollbar = () => {
  const [thumbHeight, setThumbHeight] = useState(0);
  const [thumbTop, setThumbTop] = useState(0);
  const [visible, setVisible] = useState(false);
  const scrollTimeout = useRef(null);
  const handleScroll = () => {
    const totalHeight = document.documentElement.scrollHeight;
    const viewportHeight = window.innerHeight;
    const currentScroll = window.scrollY;
    if (totalHeight <= viewportHeight) {
      setVisible(false);
      return;
    }
    setVisible(true);
    const computedThumbHeight = Math.max((viewportHeight / totalHeight) * viewportHeight, 40);
    const maxScrollTop = totalHeight - viewportHeight;
    const maxThumbTop = viewportHeight - computedThumbHeight;
    const computedThumbTop = (currentScroll / maxScrollTop) * maxThumbTop;
    setThumbHeight(computedThumbHeight);
    setThumbTop(computedThumbTop);
    clearTimeout(scrollTimeout.current);
    scrollTimeout.current = setTimeout(() => {
      if (!document.body.classList.contains('scrolling')) {
        setVisible(false);
      }
    }, 1500);
  };
  useEffect(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleScroll);
    handleScroll();
    return () => {
      window.removeEventListener('scroll', handleScroll);
      window.removeEventListener('resize', handleScroll);
      clearTimeout(scrollTimeout.current);
    };
  }, []);
  if (!visible) return null;
  return (
    <div className="custom-scrollbar-track">
      <div
        className="custom-scrollbar-thumb"
        style={{
          height: `${thumbHeight}px`,
          transform: `translateY(${thumbTop}px)`
        }}
      />
    </div>
  );
};
export default CustomScrollbar;