/**
 * NEKO SAC STORE - Checkout JavaScript
 * Integración con Culqi para pagos con tarjeta
 * Procesamiento de Yape
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================
    // VARIABLES GLOBALES
    // ========================================
    const checkoutForm = document.getElementById('checkoutForm');
    const yapeForm = document.getElementById('yapeForm');
    const tarjetaForm = document.getElementById('tarjetaForm');
    const modalYape = document.getElementById('modalYape');
    const modalTarjeta = document.getElementById('modalTarjeta');
    const modalProcesando = document.getElementById('modalProcesando');

    let bsModalYape, bsModalTarjeta, bsModalProcesando;

    if (modalYape) bsModalYape = new bootstrap.Modal(modalYape);
    if (modalTarjeta) bsModalTarjeta = new bootstrap.Modal(modalTarjeta);
    if (modalProcesando) bsModalProcesando = new bootstrap.Modal(modalProcesando);

    // Variable para guardar datos del checkout
    let checkoutData = null;

    // Configuración de Culqi desde PHP
    const CULQI_PUBLIC_KEY = window.CULQI_PUBLIC_KEY || '';
    const IS_SANDBOX = window.IS_SANDBOX !== undefined ? window.IS_SANDBOX : true;

    // ========================================
    // INICIALIZACIÓN DE CULQI
    // ========================================
    let culqiInitialized = false;

    function initCulqi() {
        if (typeof Culqi === 'undefined') {
            console.warn('Culqi.js no está cargado. Usando modo simulado.');
            return false;
        }

        if (CULQI_PUBLIC_KEY && CULQI_PUBLIC_KEY !== 'pk_test_0b18bc03120d3f83') {
            Culqi.publicKey = CULQI_PUBLIC_KEY;
        } else {
            // Usar key de prueba por defecto
            Culqi.publicKey = 'pk_test_0b18bc03120d3f83';
        }

        culqiInitialized = true;
        console.log('✅ Culqi inicializado' + (IS_SANDBOX ? ' (Sandbox)' : ' (Producción)'));
        return true;
    }

    // ========================================
    // FORMULARIO PRINCIPAL DE CHECKOUT
    // ========================================
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!checkoutForm.checkValidity()) {
                e.stopPropagation();
                checkoutForm.classList.add('was-validated');
                return;
            }

            try {
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;
                const formData = new FormData(checkoutForm);

                bsModalProcesando?.show();

                const response = await fetch(BASE_URL + '/checkout/procesar-pago', {
                    method: 'POST',
                    body: formData
                });

                let data;
                const contentType = response.headers.get("content-type");

                if (contentType && contentType.indexOf("application/json") !== -1) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Respuesta del servidor:', text);
                    throw new Error('Error del servidor');
                }

                bsModalProcesando?.hide();

                if (data.success) {
                    checkoutData = {
                        idpedido: data.idpedido,
                        codigo_pedido: data.codigo_pedido,
                        total: data.total,
                        metodo_pago: data.metodo_pago,
                        nombre: formData.get('nombre'),
                        email: formData.get('email'),
                        telefono: formData.get('telefono')
                    };

                    const total = parseFloat(data.total);

                    setTimeout(() => {
                        if (data.metodo_pago === 'yape') {
                            document.getElementById('yapeTotalAmount').textContent = 'S/ ' + total.toFixed(2);
                            bsModalYape?.show();
                        } else {
                            // Tarjeta - Mostrar modal
                            document.getElementById('cardTotalAmount').textContent = 'S/ ' + total.toFixed(2);
                            document.getElementById('cardButtonAmount').textContent = total.toFixed(2);
                            bsModalTarjeta?.show();

                            // Intentar inicializar Culqi
                            initCulqi();
                        }
                    }, 300);
                } else {
                    showToast(data.error || 'Error al procesar el pedido', 'error');
                }

            } catch (error) {
                bsModalProcesando?.hide();
                console.error('Error:', error);
                showToast('Error: ' + error.message, 'error');
            }
        });
    }

    // ========================================
    // FORMULARIO DE PAGO YAPE
    // ========================================
    if (yapeForm) {
        yapeForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!checkoutData) {
                showToast('No hay datos de pedido. Recarga la página.', 'error');
                return;
            }

            const submitBtn = yapeForm.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');

            try {
                submitBtn.disabled = true;
                btnText?.classList.add('d-none');
                spinner?.classList.remove('d-none');

                const formData = new FormData(yapeForm);

                const response = await fetch(BASE_URL + '/checkout/procesar-yape', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    bsModalYape?.hide();
                    showSuccessAnimation();
                    setTimeout(() => {
                        window.location.href = BASE_URL + '/checkout/confirmacion';
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Error al procesar el pago');
                }

            } catch (error) {
                console.error('Error:', error);
                showToast(error.message, 'error');
                submitBtn.disabled = false;
                btnText?.classList.remove('d-none');
                spinner?.classList.add('d-none');
            }
        });
    }

    // ========================================
    // FORMULARIO DE PAGO CON TARJETA
    // ========================================
    if (tarjetaForm) {
        const numeroTarjetaInput = document.getElementById('numero_tarjeta');
        const nombreTitularInput = document.getElementById('nombre_titular');
        const fechaExpInput = document.getElementById('fecha_expiracion');

        // Formatear número de tarjeta
        if (numeroTarjetaInput) {
            numeroTarjetaInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\s/g, '');
                let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formatted;

                document.getElementById('displayCardNumber').textContent = formatted || '**** **** **** ****';

                const firstDigit = value.charAt(0);
                const cardLogo = document.getElementById('cardLogo');

                if (firstDigit === '4') {
                    cardLogo.innerHTML = '<span class="fw-bold text-white">VISA</span>';
                } else if (firstDigit === '5') {
                    cardLogo.innerHTML = '<span class="fw-bold text-white">MASTERCARD</span>';
                } else if (firstDigit === '3') {
                    cardLogo.innerHTML = '<span class="fw-bold text-white">AMEX</span>';
                } else {
                    cardLogo.innerHTML = '<i class="bi bi-credit-card"></i>';
                }
            });
        }

        // Actualizar nombre del titular
        if (nombreTitularInput) {
            nombreTitularInput.addEventListener('input', (e) => {
                const value = e.target.value.toUpperCase();
                document.getElementById('displayCardName').textContent = value || 'NOMBRE APELLIDO';
            });
        }

        // Formatear fecha de expiración
        if (fechaExpInput) {
            fechaExpInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 6);
                }
                e.target.value = value;
                document.getElementById('displayCardExpiry').textContent = value || 'MM/AAAA';
            });
        }

        // Enviar formulario de tarjeta
        tarjetaForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!checkoutData) {
                showToast('No hay datos de pedido. Recarga la página.', 'error');
                return;
            }

            const submitBtn = tarjetaForm.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');

            try {
                submitBtn.disabled = true;
                btnText?.classList.add('d-none');
                spinner?.classList.remove('d-none');

                // Obtener datos de la tarjeta
                const cardNumber = document.getElementById('numero_tarjeta').value.replace(/\s/g, '');
                const cardHolder = document.getElementById('nombre_titular').value;
                const expiry = document.getElementById('fecha_expiracion').value;
                const cvv = document.getElementById('cvv').value;

                // Separar mes y año
                const [month, year] = expiry.split('/');

                // Si Culqi está disponible, usar tokenización real
                if (culqiInitialized && typeof Culqi !== 'undefined') {
                    // Configurar Culqi checkout
                    Culqi.settings({
                        title: 'NEKO SAC Store',
                        currency: 'PEN',
                        description: 'Pedido ' + checkoutData.codigo_pedido,
                        amount: Math.round(checkoutData.total * 100)
                    });

                    // Crear token con los datos de la tarjeta
                    try {
                        const token = await createCulqiToken({
                            card_number: cardNumber,
                            cvv: cvv,
                            expiration_month: month,
                            expiration_year: year,
                            email: checkoutData.email
                        });

                        // Enviar token al servidor
                        await processCulqiPayment(token);

                    } catch (culqiError) {
                        console.log('Culqi no disponible, usando modo simulado:', culqiError);
                        await processSimulatedPayment();
                    }
                } else {
                    // Modo simulado (para desarrollo)
                    await processSimulatedPayment();
                }

            } catch (error) {
                console.error('Error:', error);
                showToast(error.message, 'error');
                submitBtn.disabled = false;
                btnText?.classList.remove('d-none');
                spinner?.classList.add('d-none');
            }
        });
    }

    // ========================================
    // FUNCIONES DE PAGO
    // ========================================

    async function createCulqiToken(cardData) {
        return new Promise((resolve, reject) => {
            if (typeof Culqi === 'undefined') {
                reject(new Error('Culqi no disponible'));
                return;
            }

            // En producción, usar Culqi.createToken
            // Por ahora, retornamos un error para usar modo simulado
            reject(new Error('Token creation not implemented in frontend'));
        });
    }

    async function processCulqiPayment(token) {
        const response = await fetch(BASE_URL + '/checkout/procesar-culqi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                email: checkoutData.email
            })
        });

        const data = await response.json();

        if (data.success) {
            bsModalTarjeta?.hide();
            showSuccessAnimation();
            setTimeout(() => {
                window.location.href = BASE_URL + '/checkout/confirmacion';
            }, 2000);
        } else {
            throw new Error(data.error || 'Error al procesar el pago');
        }
    }

    async function processSimulatedPayment() {
        // Simular procesamiento
        await new Promise(resolve => setTimeout(resolve, 1500));

        const formData = new FormData(tarjetaForm);

        const response = await fetch(BASE_URL + '/checkout/procesar-tarjeta', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            bsModalTarjeta?.hide();
            showSuccessAnimation();
            setTimeout(() => {
                window.location.href = BASE_URL + '/checkout/confirmacion';
            }, 2000);
        } else {
            throw new Error(data.error || 'Error al procesar el pago');
        }
    }

    // ========================================
    // FUNCIONES AUXILIARES
    // ========================================

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-' + type;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 9999;
            animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            max-width: 400px;
            font-weight: 500;
        `;

        const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
        toast.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-${icon}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    function showSuccessAnimation() {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.9), rgba(16,185,129,0.3));
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        `;

        overlay.innerHTML = `
            <div style="text-align: center; color: white;">
                <div style="margin-bottom: 1.5rem; animation: scaleIn 0.5s ease;">
                    <i class="bi bi-check-circle-fill" style="font-size: 6rem; color: #10b981; filter: drop-shadow(0 0 20px rgba(16,185,129,0.5));"></i>
                </div>
                <h2 style="margin-bottom: 0.5rem; font-weight: 700;">¡Pago Exitoso!</h2>
                <p style="opacity: 0.8;">Redirigiendo a confirmación...</p>
                <div class="spinner-border text-light mt-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
    }

    // Estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(400px) rotate(5deg); opacity: 0; }
            to { transform: translateX(0) rotate(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);

    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

    console.log('✅ Checkout JavaScript inicializado');
    console.log('📍 BASE_URL:', BASE_URL);
    console.log('🔐 Modo:', IS_SANDBOX ? 'Sandbox/Pruebas' : 'Producción');
});
