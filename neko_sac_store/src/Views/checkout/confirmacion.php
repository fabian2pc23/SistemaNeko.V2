<?php
/** @var string $codigo_pedido */
/** @var int $idpedido */
/** @var string $nombre */
/** @var string $email */
?>

<div class="confirmation-page">
    <div class="confirmation-container">
        <!-- Icono de éxito animado -->
        <div class="success-animation">
            <div class="checkmark-circle">
                <div class="checkmark-background"></div>
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark-circle-svg" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
        </div>

        <!-- Mensaje de éxito -->
        <div class="success-message">
            <h1 class="success-title">¡Pedido Realizado con Éxito!</h1>
            <p class="success-subtitle">
                Gracias por tu compra, <strong><?= htmlspecialchars($nombre) ?></strong>
            </p>
        </div>

        <!-- Detalles del pedido -->
        <div class="order-details-card">
            <div class="order-number">
                <span class="label">Número de Pedido:</span>
                <span class="value"><?= htmlspecialchars($codigo_pedido ?? 'N/A') ?></span>
            </div>

            <div class="order-info">
                <div class="info-item">
                    <i class="bi bi-envelope"></i>
                    <div>
                        <small class="text-muted">Confirmación enviada a:</small>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($email) ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <small class="text-muted">Fecha y hora:</small>
                        <p class="mb-0 fw-semibold"><?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="bi bi-check-circle"></i>
                    <div>
                        <small class="text-muted">Estado del pago:</small>
                        <p class="mb-0">
                            <span class="badge bg-success">Pagado</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximos pasos -->
        <div class="next-steps">
            <h5 class="steps-title">
                <i class="bi bi-list-check"></i> ¿Qué sigue?
            </h5>
            <div class="steps-timeline">
                <div class="step">
                    <div class="step-icon done">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div class="step-content">
                        <h6>Pedido Recibido</h6>
                        <p>Hemos recibido tu pedido correctamente</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-icon current">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="step-content">
                        <h6>Preparación</h6>
                        <p>Estamos preparando tu pedido</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="step-content">
                        <h6>En Camino</h6>
                        <p>Tu pedido será enviado pronto</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-icon">
                        <i class="bi bi-house-check"></i>
                    </div>
                    <div class="step-content">
                        <h6>Entregado</h6>
                        <p>Recibirás tu pedido en 3-5 días hábiles</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="additional-info">
            <div class="info-box">
                <i class="bi bi-info-circle-fill text-info"></i>
                <div>
                    <strong>Importante:</strong>
                    Recibirás un correo de confirmación con los detalles de tu pedido y 
                    el número de seguimiento en las próximas 24 horas.
                </div>
            </div>

            <div class="info-box">
                <i class="bi bi-headset text-primary"></i>
                <div>
                    <strong>¿Necesitas ayuda?</strong>
                    Contáctanos al <strong>+51 999 999 999</strong> o 
                    <a href="mailto:ventas@nekosac.com">ventas@nekosac.com</a>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/tienda" class="btn btn-primary btn-lg">
                <i class="bi bi-shop"></i> Seguir Comprando
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-printer"></i> Imprimir Confirmación
            </button>
        </div>

        <!-- Tarjeta de promoción -->
        <div class="promo-card">
            <div class="promo-icon">
                <i class="bi bi-gift"></i>
            </div>
            <div class="promo-content">
                <h6>¡Gana puntos con tu compra!</h6>
                <p>Por esta compra has ganado puntos que puedes usar en tu próximo pedido</p>
            </div>
        </div>
    </div>
</div>

<!-- Confetti Animation (celebración) -->
<style>
.confirmation-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
}

.confirmation-container {
    max-width: 700px;
    margin: 0 auto;
}

/* Success Animation */
.success-animation {
    text-align: center;
    margin-bottom: 2rem;
}

.checkmark-circle {
    width: 120px;
    height: 120px;
    position: relative;
    margin: 0 auto;
    animation: scaleIn 0.6s ease-out;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.checkmark-background {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    position: absolute;
    box-shadow: 0 10px 40px rgba(16, 185, 129, 0.3);
}

.checkmark {
    width: 120px;
    height: 120px;
    position: relative;
    z-index: 1;
}

.checkmark-circle-svg {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke: #ffffff;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    animation-delay: 0.3s;
}

.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #ffffff;
    stroke-width: 3;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    animation-delay: 0.6s;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

/* Success Message */
.success-message {
    text-align: center;
    margin-bottom: 2.5rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.3s;
    animation-fill-mode: both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-title {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

.success-subtitle {
    font-size: 1.1rem;
    color: #6b7280;
}

/* Order Details Card */
.order-details-card {
    background: #ffffff;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.5s;
    animation-fill-mode: both;
}

.order-number {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.order-number .label {
    display: block;
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.order-number .value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e40af;
    font-family: 'Courier New', monospace;
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.info-item i {
    font-size: 1.5rem;
    color: #3b82f6;
    flex-shrink: 0;
}

/* Next Steps Timeline */
.next-steps {
    background: #ffffff;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.7s;
    animation-fill-mode: both;
}

.steps-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #111827;
}

.steps-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step {
    display: flex;
    gap: 1rem;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 1.25rem;
    top: 3rem;
    width: 2px;
    height: calc(100% + 0.5rem);
    background: #e5e7eb;
}

.step-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #9ca3af;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.step-icon.done {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: #10b981;
    color: #ffffff;
}

.step-icon.current {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-color: #3b82f6;
    color: #ffffff;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
}

.step-content h6 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #111827;
}

.step-content p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

/* Additional Info */
.additional-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.9s;
    animation-fill-mode: both;
}

.info-box {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    display: flex;
    gap: 1rem;
}

.info-box i {
    font-size: 1.5rem;
    flex-shrink: 0;
}

/* Confirmation Actions */
.confirmation-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 1.1s;
    animation-fill-mode: both;
}

.confirmation-actions .btn {
    min-width: 200px;
}

/* Promo Card */
.promo-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 1.3s;
    animation-fill-mode: both;
}

.promo-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #ffffff;
    flex-shrink: 0;
}

.promo-content h6 {
    font-weight: 700;
    color: #92400e;
    margin-bottom: 0.25rem;
}

.promo-content p {
    margin: 0;
    color: #78350f;
    font-size: 0.9rem;
}

/* Print Styles */
@media print {
    .confirmation-actions,
    .promo-card {
        display: none !important;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .success-title {
        font-size: 1.5rem;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .confirmation-actions .btn {
        width: 100%;
    }
    
    .promo-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>
