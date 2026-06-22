import React from 'react';

const HeaderBar = ({ title, cartCount = 0, onCartClick }) => {
  return (
    <header className="app-header">
      <div className="app-header-pill">
        <div className="app-header-title">
          {title}
        </div>
        {onCartClick && (
          <div className="cart-icon" onClick={onCartClick}>
            <img src="https://cdn-icons-png.flaticon.com/512/7887/7887460.png" alt="Корзина" />
            {cartCount > 0 && (
              <span className="cart-badge">
                {cartCount > 99 ? '99+' : cartCount}
              </span>
            )}
          </div>
        )}
      </div>
    </header>
  );
};

export default HeaderBar;