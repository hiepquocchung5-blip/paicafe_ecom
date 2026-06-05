function animateCartPop() {
    const cartLink = document.getElementById('mobile-cart-link');
    cartLink.classList.add('cart-pop-animation');
    cartLink.addEventListener('animationend', () => {
      cartLink.classList.remove('cart-pop-animation');
    }, { once: true });
  }
