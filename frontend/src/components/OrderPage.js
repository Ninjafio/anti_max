import React, { useState } from "react";
import HeaderBar from "./HeaderBar";

const CHECKOUT_API_URL = "http://localhost:8881/wp-json/custom/v1/checkout";

const OrderPage = ({ cart, clearCart, onBack, openCart }) => {
  const [formData, setFormData] = useState({
    fullName: "",
    birthDate: "",
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const totalAmount = cart.reduce(
    (sum, item) => sum + item.product.price * item.quantity,
    0,
  );

const handleInputChange = (e) => {
  const { name, value } = e.target;

  if (name === 'birthDate') {
    let cleaned = value.replace(/\D/g, '');
    
    if (cleaned.length > 8) {
      cleaned = cleaned.substring(0, 8);
    }

    let formatted = '';
    if (cleaned.length > 0) {
      formatted += cleaned.substring(0, 2);
    }
    if (cleaned.length > 2) {
      formatted += '.' + cleaned.substring(2, 4);
    }
    if (cleaned.length > 4) {
      formatted += '.' + cleaned.substring(4, 8);
    }

    setFormData(prev => ({ ...prev, [name]: formatted }));
  } else {
    setFormData(prev => ({ ...prev, [name]: value }));
  }
};


  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.fullName || !formData.birthDate || cart.length === 0) {
      alert("Пожалуйста, заполните все обязательные поля");
      return;
    }

    try {
      setIsSubmitting(true);

      const response = await fetch(CHECKOUT_API_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          fullName: formData.fullName,
          birthDate: formData.birthDate,
          cart: cart,
        }),
      });

      const result = await response.json();

      if (response.ok && result.success) {
        alert("Заявка успешно отправлена в отдел персонала!");
        clearCart();
        onBack();
      } else {
        throw new Error(result.message || "Ошибка сервера при отправке заказа");
      }
    } catch (error) {
      alert(`Не удалось отправить заявку: ${error.message}`);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="order-page">
      <HeaderBar
        title="Оформление заказа"
        cartCount={cart.length}
        onCartClick={openCart}
      />

      <div className="back-row">
        <div className="back-button" onClick={onBack}>
          ← Назад
        </div>
      </div>

      <form onSubmit={handleSubmit} className="order-form">
        <div className="form-section">
          <h3>Данные сотрудника</h3>

          <div className="form-group">
            <label>
              <span className="required">*</span> ФИО
            </label>
            <input
              type="text"
              name="fullName"
              value={formData.fullName}
              onChange={handleInputChange}
              placeholder="Введите ваше ФИО"
              required
            />
          </div>

          <div className="form-group">
            <label>
              <span className="required">*</span> Дата рождения
            </label>
            <input
              type="text"
              name="birthDate"
              value={formData.birthDate}
              onChange={handleInputChange}
              placeholder="ДД.ММ.ГГГГ"
              pattern="(0[1-9]|[12][0-9]|3[01])\.(0[1-9]|1[012])\.(19|20)\d\d"
              title="Введите дату в формате ДД.ММ.ГГГГ (например, 25.10.1995)"
              required
            />
          </div>
        </div>

        <div className="form-section">
          <h3>Ваш заказ</h3>

          <div className="order-items">
            {cart.map((item) => (
              <div key={item.product.id} className="order-item">
                <img
                  src={
                    item.product.images?.[0] ||
                    "data:image/svg+xml;utf8,<svg xmlns='http://w3.org' width='90' height='90'><rect width='100%' height='100%' fill='%23eee'/></svg>"
                  }
                  alt={item.product.name}
                  className="order-item-image"
                />
                <div className="order-item-info">
                  <h4>{item.product.name}</h4>
                  <p className="price">{item.product.price} ₽</p>
                  <p className="quantity">Количество: {item.quantity}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="order-footer">
          <div className="order-total">
            Итого: {totalAmount.toLocaleString("ru-RU")} ₽
          </div>

          <button
            type="submit"
            className="submit-order-btn"
            disabled={isSubmitting || cart.length === 0}
          >
            {isSubmitting
              ? "Отправляем заявку..."
              : "Отправить заявку в отдел персонала"}
          </button>
        </div>
      </form>
    </div>
  );
};

export default OrderPage;
