/**
 * Scroll Reveal Utility
 * Handles premium entrance animations for product cards
 * using Intersection Observer API.
 */

const revealOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px',
};

const revealObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('reveal-active');
      observer.unobserve(entry.target); // Reveal only once
    }
  });
}, revealOptions);

/**
 * Observe all product cards currently in the DOM
 */
export const initScrollReveal = () => {
  const cards = document.querySelectorAll('.product-card');
  cards.forEach((card) => revealObserver.observe(card));
};

/**
 * Observe specific elements (useful for dynamically loaded products)
 * @param {NodeList|Element} elements
 */
export const observeProducts = (elements) => {
  if (elements instanceof NodeList) {
    elements.forEach((el) => revealObserver.observe(el));
  } else if (elements instanceof Element) {
    revealObserver.observe(elements);
  }
};

// Auto-initialize for static content
document.addEventListener('DOMContentLoaded', () => {
  initScrollReveal();
});
