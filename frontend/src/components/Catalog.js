import React, { useState, useEffect } from "react";
import HeaderBar from "./HeaderBar";
import ProductCard from "./ProductCard";
import ProductDetail from "./ProductDetail";
import Cart from "./Cart";
import OrderPage from "./OrderPage";
import CustomScrollbar from "./CustomScrollbar";

// Настройки подключения к вашему локальному сайту WordPress/WooCommerce
const API_URL = 'http://localhost:8881/wp-json/custom/v1/products';

const Catalog = () => {
  const [products, setProducts] = useState([]); // Сюда будут загружаться товары из API
  const [loading, setLoading] = useState(true); // Состояние загрузки
  const [error, setError] = useState(null); // Состояние ошибки

  const [currentPage, setCurrentPage] = useState("catalog");
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [cart, setCart] = useState([]);
  const [showScrollTop, setShowScrollTop] = useState(false);

  // Загрузка товаров из WooCommerce REST API
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        setLoading(true);
        const response = await fetch(API_URL);

        if (!response.ok) {
          throw new Error('Не удалось получить товары с сервера');
        }

        const data = await response.json();
        setProducts(data); // Данные уже лежат в идеальном формате [{id, name, fullName, price, images}]
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);


  // Следим за скроллом для кнопки "Наверх"
  useEffect(() => {
    const handleScroll = () => {
      if (currentPage === "catalog" && window.scrollY > 300) {
        setShowScrollTop(true);
      } else {
        setShowScrollTop(false);
      }
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, [currentPage]);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const addToCart = (product) => {
    setCart((prev) => {
      const itemIndex = prev.findIndex(
        (item) => item.product.id === product.id,
      );
      if (itemIndex > -1) {
        return prev.map((item, idx) =>
          idx === itemIndex ? { ...item, quantity: item.quantity + 1 } : item,
        );
      }
      return [...prev, { product, quantity: 1 }];
    });
  };

  const increaseQuantity = (productId) => {
    setCart((prev) =>
      prev.map((item) =>
        item.product.id === productId
          ? { ...item, quantity: item.quantity + 1 }
          : item,
      ),
    );
  };

  const decreaseQuantity = (productId) => {
    setCart((prev) =>
      prev
        .map((item) =>
          item.product.id === productId
            ? { ...item, quantity: item.quantity - 1 }
            : item,
        )
        .filter((item) => item.quantity > 0),
    );
  };

  const openDetail = (product) => {
    setSelectedProduct(product);
    setCurrentPage("detail");
  };

  const openCart = () => setCurrentPage("cart");

  const goBack = () => {
    setCurrentPage("catalog");
    setSelectedProduct(null);
  };

  const openOrderPage = () => {
    setCurrentPage("order");
  };

  const closeOrderPage = () => {
    setCurrentPage("cart");
  };

  const clearCart = () => {
    setCart([]);
  };

  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = cart.reduce(
    (sum, item) => sum + item.product.price * item.quantity,
    0,
  );

  // ==================== РЕНДЕРИНГ СТРАНИЦ ====================

  if (currentPage === "detail" && selectedProduct) {
    return (
      <ProductDetail
        product={selectedProduct}
        onBack={goBack}
        addToCart={addToCart}
        decreaseQuantity={decreaseQuantity}
        cartCount={totalItems}
        openCart={openCart}
        cart={cart}
      />
    );
  }

  if (currentPage === "cart") {
    return (
      <Cart
        cart={cart}
        onBack={goBack}
        increaseQuantity={increaseQuantity}
        decreaseQuantity={decreaseQuantity}
        totalPrice={totalPrice}
        cartCount={totalItems}
        onSubmitOrder={openOrderPage}
      />
    );
  }

  if (currentPage === "order") {
    return (
      <OrderPage
        cart={cart}
        clearCart={clearCart}
        onBack={closeOrderPage}
        openCart={openCart}
      />
    );
  }

  // Основной каталог
  return (
    <div className="catalog-container">
      <CustomScrollbar />
      <HeaderBar
        title="Каталог"
        cartCount={totalItems}
        onCartClick={openCart}
      />

      <main className="catalog-main">
        {/* Индикаторы загрузки и ошибок */}
        {loading && <div className="loading-spinner">Загрузка товаров...</div>}
        {error && (
          <div className="error-message">Произошла ошибка: {error}</div>
        )}

        {/* Вывод списка загруженных товаров */}
        {!loading &&
          !error &&
          products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              onClick={() => openDetail(product)}
              addToCart={addToCart}
              decreaseQuantity={decreaseQuantity}
              cart={cart}
            />
          ))}
      </main>

      <button
        className={`scroll-to-top-btn ${showScrollTop ? "visible" : ""}`}
        onClick={scrollToTop}
      >
        ↑
      </button>
    </div>
  );
};

export default Catalog;
