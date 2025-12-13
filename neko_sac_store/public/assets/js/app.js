document.addEventListener('DOMContentLoaded', () => {
    // ========================================
    // INICIALIZACIÓN DEL CARRUSEL DE PRODUCTOS
    // ========================================
    const heroSwiper = document.querySelector('.heroProductsSwiper');

    if (heroSwiper) {
        new Swiper('.heroProductsSwiper', {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            speed: 800,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            parallax: true,
            a11y: {
                prevSlideMessage: 'Producto anterior',
                nextSlideMessage: 'Siguiente producto',
            },
        });
        console.log('✅ Carrusel de productos inicializado');
    }

    // ========================================
    // SISTEMA DE AÑADIR AL CARRITO (AJAX)
    // ========================================
    document.querySelectorAll('form[action*="/carrito/agregar"]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalHTML = button.innerHTML;

            // Verificar si está deshabilitado (sin stock)
            if (button.disabled) {
                showNotification('Este producto está agotado', 'warning');
                return;
            }

            // Mostrar loading
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Agregando...';

            try {
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar contador del carrito
                    updateCartCount(data.cart_count);

                    // Mostrar notificación de éxito
                    showNotification(
                        `¡${data.producto.nombre} agregado al carrito!`,
                        'success',
                        data.producto
                    );

                    // Animación del botón
                    button.innerHTML = '<i class="bi bi-check me-1"></i>¡Agregado!';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-success');

                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-primary');
                        button.disabled = false;
                    }, 2000);

                } else {
                    showNotification(data.error || 'Error al agregar producto', 'error');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }

            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        });
    });

    // ========================================
    // FUNCIONES DE NOTIFICACIÓN
    // ========================================
    function showNotification(message, type = 'info', producto = null) {
        // Remover notificación anterior si existe
        const existingNotification = document.querySelector('.cart-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
        };

        const icons = {
            success: 'check-circle-fill',
            error: 'exclamation-circle-fill',
            warning: 'exclamation-triangle-fill',
            info: 'info-circle-fill'
        };

        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 350px;
            background: ${colors[type]};
            color: white;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            z-index: 9999;
            animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            overflow: hidden;
        `;

        let productInfo = '';
        if (producto && type === 'success') {
            const imgUrl = producto.imagen
                ? BASE_URL.replace('/neko_sac_store/public', '') + '/files/articulos/' + producto.imagen
                : BASE_URL + '/assets/img/placeholder.jpg';
            productInfo = `
                <div style="display: flex; align-items: center; gap: 12px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <img src="${imgUrl}" 
                         style="width: 50px; height: 50px; object-fit: contain; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 4px;"
                         onerror="this.src='${BASE_URL}/assets/img/placeholder.jpg'">
                    <div style="flex: 1;">
                        <div style="font-size: 0.85rem; font-weight: 600;">${producto.nombre}</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">S/ ${parseFloat(producto.precio).toFixed(2)}</div>
                    </div>
                </div>
                <div style="margin-top: 12px; display: flex; gap: 8px;">
                    <a href="${BASE_URL}/carrito" class="btn btn-light btn-sm flex-fill" style="font-size: 0.75rem;">
                        <i class="bi bi-cart me-1"></i>Ver Carrito
                    </a>
                    <a href="${BASE_URL}/checkout" class="btn btn-dark btn-sm flex-fill" style="font-size: 0.75rem;">
                        <i class="bi bi-credit-card me-1"></i>Pagar
                    </a>
                </div>
            `;
        }

        notification.innerHTML = `
            <div style="padding: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-${icons[type]}" style="font-size: 1.5rem;"></i>
                    <span style="flex: 1; font-weight: 500;">${message}</span>
                    <button onclick="this.closest('.cart-notification').remove()" 
                            style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; opacity: 0.7;">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                ${productInfo}
            </div>
            <div class="notification-progress" style="height: 4px; background: rgba(255,255,255,0.3);">
                <div style="height: 100%; background: white; animation: progressShrink 5s linear forwards;"></div>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Actualizar contador del carrito
    function updateCartCount(count) {
        const cartBadge = document.querySelector('.cart-count');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.animation = 'none';
            cartBadge.offsetHeight; // Trigger reflow
            cartBadge.style.animation = 'cartBounce 0.5s ease';
        }
    }

    // ========================================
    // ANIMACIONES DE SCROLL
    // ========================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // ========================================
    // SPOTLIGHT EFFECT EN PRODUCT CARDS
    // ========================================
    const spotlightCards = document.querySelectorAll('.product-card');

    spotlightCards.forEach(card => {
        card.classList.add('spotlight-card');

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);

            if (typeof gsap !== 'undefined') {
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -5;
                const rotateY = ((x - centerX) / centerX) * 5;

                gsap.to(card, {
                    duration: 0.5,
                    rotateX: rotateX,
                    rotateY: rotateY,
                    scale: 1.02,
                    ease: 'power2.out',
                    transformPerspective: 1000
                });
            }
        });

        card.addEventListener('mouseleave', () => {
            if (typeof gsap !== 'undefined') {
                gsap.to(card, {
                    duration: 0.5,
                    rotateX: 0,
                    rotateY: 0,
                    scale: 1,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Staggered Reveal
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
        gsap.from('.product-card', {
            scrollTrigger: {
                trigger: '.product-grid',
                start: 'top 85%',
            },
            duration: 0.8,
            y: 60,
            opacity: 0,
            stagger: 0.05,
            ease: 'back.out(1.2)'
        });
    }

    // ========================================
    // ESTILOS DE ANIMACIÓN
    // ========================================
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(400px) rotate(3deg); opacity: 0; }
            to { transform: translateX(0) rotate(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        @keyframes progressShrink {
            from { width: 100%; }
            to { width: 0%; }
        }
        @keyframes cartBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.4); }
        }
    `;
    document.head.appendChild(style);

    console.log('✅ NEKO SAC Store JS inicializado');
});
