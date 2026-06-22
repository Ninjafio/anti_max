import React, { useState } from "react";
const ProductCard = ({
  product,
  onClick,
  addToCart,
  decreaseQuantity,
  cart,
}) => {
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [activeScale, setActiveScale] = useState(false);
  const hasMultipleImages = product.images && product.images.length > 1;
  const cartItem = cart.find((item) => item.product.id === product.id);
  const quantityInCart = cartItem ? cartItem.quantity : 0;

  const handleDotClick = (e, idx) => {
    e.stopPropagation();
    if (idx === currentImageIndex) {
      setActiveScale(true);
      setTimeout(() => setActiveScale(false), 200);
    } else {
      setCurrentImageIndex(idx);
    }
  };
  const WP_BASE_URL = "http://localhost:8881";

  // 2. Функция для проверки и исправления URL картинки
  const getCorrectImageUrl = (imgUrl) => {
    if (!imgUrl) return "https://via.placeholder.com/370x556?text=No+Image";

    // Если пришел относительный путь (например, /wp-content/...)
    if (imgUrl.startsWith("/")) {
      return `${WP_BASE_URL}${imgUrl}`;
    }

    // Если вы тестируете с телефона, а в URL написано localhost,
    // можно раскомментировать строку ниже и заменить localhost на ваш локальный IP (например, 192.168.1.50)
    // return imgUrl.replace('localhost:8881', 'ВАШ_IP:8881');

    return imgUrl;
  };
  return (
    <div className="product-card">
      <div className="product-image-container" onClick={onClick}>
        <img
          src={getCorrectImageUrl(product.images?.[currentImageIndex])}
          alt={product.name}
          className="product-image"
          onError={(e) => {
            e.target.onerror = null;
            e.target.src = "https://placeholder.com";
          }}
        />
        {hasMultipleImages && (
          <div className="catalog-image-dots">
            {product.images.map((_, idx) => {
              const isActive = idx === currentImageIndex;
              return (
                <div
                  key={idx}
                  className={`catalog-dot ${isActive ? "active" : ""} ${isActive && activeScale ? "scaled" : ""}`}
                  onClick={(e) => handleDotClick(e, idx)}
                />
              );
            })}
          </div>
        )}
      </div>
      <div className="product-info">
        <div className="product-details" onClick={onClick}>
          <h3 className="product-name">{product.name}</h3>
          <p className="product-price">{product.price} ₽</p>
        </div>
        {quantityInCart > 0 ? (
          <div
            className="card-qty-selector"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              className="card-qty-btn"
              onClick={() => decreaseQuantity(product.id)}
            >
              −
            </button>
            <span className="card-qty-value">{quantityInCart} в корзине</span>
            <button className="card-qty-btn" onClick={() => addToCart(product)}>
              +
            </button>
          </div>
        ) : (
          <button
            className="add-to-cart-btn"
            onClick={(e) => {
              e.stopPropagation();
              addToCart(product);
            }}
          >
            В корзину
          </button>
        )}
      </div>
    </div>
  );
};
export default ProductCard;
