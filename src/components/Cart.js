import React from 'react';
import HeaderBar from './HeaderBar';

const Cart = ({ cart, onBack, increaseQuantity, decreaseQuantity, totalPrice, cartCount, onSubmitOrder }) => {
  const renderTopSection = () => (
    <>
      <HeaderBar title="Корзина" cartCount={cartCount} onCartClick={null} />
      <div className="back-row">
        <div className="back-button" onClick={onBack}>
          ← Назад
        </div>
      </div>
    </>
  );

  if (cart.length === 0) {
    return (
      <div className="cart-page">
        {renderTopSection()}
        <div className="cart-content">
          <p style={{ fontSize: 16 }}>Корзина пуста</p>
        </div>
      </div>
    );
  }

  return (
    <div className="cart-page">
      {renderTopSection()}
      <div className="cart-content">
        {cart.map((item) => (
          <div key={item.product.id} className="cart-product">
            <img
              src={item.product.images?.[0]}
              alt={item.product.fullName}
              className="cart-product-image"
            />
            <div className="cart-product-info">
              <div className="cart-product-name">{item.product.fullName}</div>
              <div className="cart-product-price">{item.product.price} ₽</div>
              <div className="cart-quantity">
                <button className="qty-btn" onClick={() => decreaseQuantity(item.product.id)}>
                  −
                </button>
                <span className="qty-value">{item.quantity}</span>
                <button className="qty-btn" onClick={() => increaseQuantity(item.product.id)}>
                  +
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <div className="cart-footer">
        <div className="cart-total">Итого: {totalPrice} ₽</div>
        <button className="cart-submit-btn" onClick={onSubmitOrder}>
          Перейти к оформлению заказа
        </button>
      </div>
    </div>
  );
};

export default Cart;