/**
 * Royal Beverages - Premium Contact Page JavaScript
 * Handles form validation and asynchronous simulated submission.
 */

import { toast } from '../toast.js';

document.addEventListener('DOMContentLoaded', () => {
  const contactForm = document.getElementById('contactForm');
  const submitBtn = document.getElementById('contactSubmit');
  const formWrapper = document.getElementById('contactFormWrapper');

  if (!contactForm) return;

  // Helper: Validate email
  const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  // Helper: Validate phone (standard Sri Lankan or international format)
  const isValidPhone = (phone) => {
    if (!phone) return true; // Optional field
    const phoneRegex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s./0-9]*$/;
    return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 9;
  };

  // Helper: Clear errors
  const clearErrors = () => {
    contactForm
      .querySelectorAll('.has-error')
      .forEach((el) => el.classList.remove('border-red-500'));
    contactForm.querySelectorAll('.error-text').forEach((el) => el.remove());
  };

  // Helper: Show error on field
  const showError = (fieldId, message) => {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.add('border-red-500');
    const errorEl = document.createElement('span');
    errorEl.className =
      'error-text text-[8px] text-red-500 font-black uppercase tracking-widest mt-1 block';
    errorEl.textContent = message;
    field.parentElement.appendChild(errorEl);
  };

  // Handle form submission
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();

    let hasErrors = false;
    const formData = {
      name: document.getElementById('contactName').value.trim(),
      email: document.getElementById('contactEmail').value.trim(),
      phone: document.getElementById('contactPhone').value.trim(),
      subject: document.getElementById('contactSubject').value,
      message: document.getElementById('contactMessage').value.trim(),
    };

    // Validation Logic
    if (!formData.name) {
      showError('contactName', 'Please enter your name');
      hasErrors = true;
    }

    if (!formData.email) {
      showError('contactEmail', 'Email address is required');
      hasErrors = true;
    } else if (!isValidEmail(formData.email)) {
      showError('contactEmail', 'Please enter a valid email address');
      hasErrors = true;
    }

    if (formData.phone && !isValidPhone(formData.phone)) {
      showError('contactPhone', 'Please enter a valid phone number');
      hasErrors = true;
    }

    if (!formData.subject) {
      showError('contactSubject', 'Please select a inquiry topic');
      hasErrors = true;
    }

    if (!formData.message) {
      showError('contactMessage', 'Please provide detail for your inquiry');
      hasErrors = true;
    } else if (formData.message.length < 10) {
      showError('contactMessage', 'Message is too short');
      hasErrors = true;
    }

    if (hasErrors) {
      toast.error('Please fix the errors above');
      return;
    }

    // Processing State
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse">Sending...</span>';

    try {
      // Simulate network latency
      await new Promise((resolve) => setTimeout(resolve, 1500));

      // Implementation note: In a production environment, this would hit /api/v1/contact

      // Success State Transition
      formWrapper.innerHTML = `
                <div class="py-20 text-center animate-premium-fade">
                    <div class="mb-10 flex justify-center">
                        <div class="w-16 h-16 bg-black flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-black uppercase tracking-tightest mb-4">Message Sent Successfully</h2>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-relaxed mb-12 max-w-xs mx-auto">
                        Thank you, ${formData.name}. We have received your inquiry and will respond within 24 hours.
                    </p>
                    <button id="resetContactForm" class="text-[10px] font-black uppercase tracking-widest border-b-2 border-black pb-1 hover:text-gray-400 hover:border-gray-100 transition-all">
                        Send Another Message
                    </button>
                </div>
            `;

      document.getElementById('resetContactForm').addEventListener('click', () => {
        window.location.reload();
      });

      toast.success('Your message has been securely dispatched');
    } catch (error) {
      console.error('[Contact] Dispatch failure:', error);
      toast.error('Secure transmission failed. Please retry.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Send Message';
    }
  });
});
