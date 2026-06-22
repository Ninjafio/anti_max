import React, { useState } from 'react';
import HeaderBar from './HeaderBar';

const ProductDetail = ({ product, onBack, addToCart, decreaseQuantity, cartCount, openCart, cart }) => {
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [activeScale, setActiveScale] = useState(false);
  const hasMultipleImages = product.images && product.images.length > 1;
  const cartItem = cart.find(item => item.product.id === product.id);
  const quantityInCart = cartItem ? cartItem.quantity : 0;

  const nextImage = () => {
    if (hasMultipleImages) {
      setCurrentImageIndex((prev) => (prev + 1) % product.images.length);
    }
  };

  const prevImage = () => {
    if (hasMultipleImages) {
      setCurrentImageIndex((prev) => (prev - 1 + product.images.length) % product.images.length);
    }
  };

  const handleDotClick = (idx) => {
    if (idx === currentImageIndex) {
      setActiveScale(true);
      setTimeout(() => setActiveScale(false), 200);
    } else {
      setCurrentImageIndex(idx);
    }
  };

  return (
    <div className="detail-page">
      <HeaderBar
        title="О товаре"
        cartCount={cartCount}
        onCartClick={openCart}
      />
      <div className="back-row">
        <div className="back-button" onClick={onBack}>
          ← Назад
        </div>
      </div>
      <div className="detail-image-wrapper">
        {hasMultipleImages && (
          <button className="slider-arrow arrow-left" onClick={prevImage}>
            &lt;
          </button>
        )}
        <img
          src={product.images?.[currentImageIndex] || 'https://via.placeholder.com/370x556?text=No+Image'}
          alt={product.fullName}
          className="detail-image"
        />
        {hasMultipleImages && (
          <button className="slider-arrow arrow-right" onClick={nextImage}>
            &gt;
          </button>
        )}
        {hasMultipleImages && (
          <div className="detail-image-dots">
            {product.images.map((_, idx) => {
              const isActive = idx === currentImageIndex;
              return (
                <div
                  key={idx}
                  className={`detail-dot ${isActive ? 'active' : ''} ${isActive && activeScale ? 'scaled' : ''}`}
                  onClick={() => handleDotClick(idx)}
                  style={{ cursor: 'pointer' }}
                />
              );
            })}
          </div>
        )}
      </div>
      <div className="detail-info">
        <div className="detail-text-block">
          <h1 className="detail-title">{product.fullName}</h1>
          <div className="detail-price">{product.price} ₽</div>
        </div>
        {quantityInCart > 0 ? (
          <div className="card-qty-selector">
            <button className="card-qty-btn" onClick={() => decreaseQuantity(product.id)}>
              −
            </button>
            <span className="card-qty-value">{quantityInCart} в корзине</span>
            <button className="card-qty-btn" onClick={() => addToCart(product)}>
              +
            </button>
          </div>
        ) : (
          <button className="detail-btn" onClick={() => addToCart(product)}>
            В корзину
          </button>
        )}
      </div>
    </div>
  );
};

export default ProductDetail;