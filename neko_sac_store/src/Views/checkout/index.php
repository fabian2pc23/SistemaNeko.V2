<?php

/** @var array $items */
/** @var float $subtotal */
/** @var float $igv */
/** @var float $total */
/** @var int $cantidadItems */
/** @var string $culqiPublicKey */
/** @var bool $isSandbox */
?>

<!-- Configuración de pasarela de pagos para JavaScript -->
<script>
    window.CULQI_PUBLIC_KEY = '<?= $culqiPublicKey ?? '' ?>';
    window.IS_SANDBOX = <?= ($isSandbox ?? true) ? 'true' : 'false' ?>;
    window.CHECKOUT_TOTAL = <?= $total ?>;
</script>

<div class="row g-4">
    <!-- Columna Izquierda: Formulario de Checkout -->
    <div class="col-lg-7">
        <div class="checkout-card">
            <div class="checkout-header">
                <h2 class="checkout-title">
                    <i class="bi bi-bag-check"></i> Finalizar Compra
                </h2>
                <p class="text-muted">Complete sus datos para procesar el pedido</p>
            </div>

            <form id="checkoutForm" class="needs-validation" novalidate>
                <!-- Datos del Cliente -->
                <div class="checkout-section">
                    <h5 class="section-subtitle">
                        <i class="bi bi-person-circle"></i> Información Personal
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                            <div class="invalid-feedback">
                                Por favor ingrese su nombre completo
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">
                                Ingrese un email válido
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono / Celular *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                placeholder="987654321" required>
                            <div class="invalid-feedback">
                                Ingrese un número de teléfono
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dirección de Entrega -->
                <div class="checkout-section">
                    <h5 class="section-subtitle">
                        <i class="bi bi-geo-alt"></i> Dirección de Entrega
                    </h5>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="direccion" class="form-label">Dirección Completa *</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                placeholder="Calle, número, distrito, ciudad" required></textarea>
                            <div class="invalid-feedback">
                                Por favor ingrese su dirección
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notas" class="form-label">Notas del Pedido (Opcional)</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2"
                                placeholder="Ej: Entregar en la mañana, tocar el timbre 2 veces..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Método de Pago -->
                <div class="checkout-section">
                    <h5 class="section-subtitle">
                        <i class="bi bi-credit-card"></i> Método de Pago
                    </h5>

                    <div class="payment-methods">
                        <div class="payment-method-card">
                            <input type="radio" class="btn-check" name="metodo_pago" id="metodo_yape"
                                value="yape" checked>
                            <label class="payment-option" for="metodo_yape">
                                <div class="payment-icon yape-icon">
                                    <i class="bi bi-phone"></i>
                                </div>
                                <div class="payment-info">
                                    <h6>Yape</h6>
                                    <p class="small text-muted mb-0">Pago instantáneo con Yape</p>
                                </div>
                                <div class="payment-check">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                            </label>
                        </div>

                        <div class="payment-method-card">
                            <input type="radio" class="btn-check" name="metodo_pago" id="metodo_tarjeta"
                                value="tarjeta">
                            <label class="payment-option" for="metodo_tarjeta">
                                <div class="payment-icon card-icon">
                                    <i class="bi bi-credit-card-2-front"></i>
                                </div>
                                <div class="payment-info">
                                    <h6>Tarjeta de Crédito/Débito</h6>
                                    <p class="small text-muted mb-0">Visa, Mastercard, American Express</p>
                                </div>
                                <div class="payment-check">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botón de Continuar -->
                <div class="checkout-actions">
                    <a href="<?= BASE_URL ?>/carrito" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver al carrito
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg checkout-submit-btn">
                        Continuar al Pago <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Columna Derecha: Resumen del Pedido -->
    <div class="col-lg-5">
        <div class="order-summary sticky-top">
            <h5 class="summary-title">
                <i class="bi bi-receipt"></i> Resumen del Pedido
            </h5>

            <!-- Items del pedido -->
            <div class="summary-items">
                <?php foreach ($items as $item): ?>
                    <div class="summary-item">
                        <div class="item-image">
                            <img src="<?= getProductImageUrl($item['producto']['imagen'] ?? null) ?>"
                                alt="<?= htmlspecialchars($item['producto']['nombre']) ?>"
                                onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
                        </div>
                        <div class="item-details">
                            <h6><?= htmlspecialchars($item['producto']['nombre']) ?></h6>
                            <p class="small text-muted">Cantidad: <?= $item['cantidad'] ?></p>
                        </div>
                        <div class="item-price">
                            S/ <?= number_format($item['subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Totales -->
            <div class="summary-totals">
                <div class="summary-row">
                    <span>Subtotal (<?= $cantidadItems ?> items):</span>
                    <span>S/ <?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>IGV (18%):</span>
                    <span>S/ <?= number_format($igv, 2) ?></span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row summary-total">
                    <span>Total a Pagar:</span>
                    <span class="total-amount">S/ <?= number_format($total, 2) ?></span>
                </div>
            </div>

            <!-- Garantías -->
            <div class="summary-guarantees">
                <div class="guarantee-item">
                    <i class="bi bi-shield-check text-success"></i>
                    <span>Compra 100% segura</span>
                </div>
                <div class="guarantee-item">
                    <i class="bi bi-truck text-primary"></i>
                    <span>Envío a Lambayeque</span>
                </div>
                <div class="guarantee-item">
                    <i class="bi bi-arrow-clockwise text-info"></i>
                    <span>Garantía de devolución</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago con Yape -->
<div class="modal fade" id="modalYape" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <div class="yape-logo-header">
                        <i class="bi bi-phone text-purple"></i>
                        <span class="fw-bold">Pagar con Yape</span>
                    </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="yape-container">
                    <!-- QR Code -->
                    <div class="yape-qr-section text-center mb-4">
                        <div class="qr-code-container">
                            <div class="qr-placeholder">
                                <i class="bi bi-qr-code display-1 text-purple"></i>
                                <p class="mt-2 small text-muted">Escanea este código QR con tu app Yape</p>
                            </div>
                        </div>
                        <div class="yape-amount mt-3">
                            <p class="mb-1 text-muted small">Monto a pagar:</p>
                            <h3 class="text-purple fw-bold" id="yapeTotalAmount">S/ 0.00</h3>
                        </div>
                    </div>

                    <!-- Formulario de Yape -->
                    <form id="yapeForm">
                        <div class="mb-3">
                            <label for="numero_operacion" class="form-label">
                                Número de Operación Yape *
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="numero_operacion" name="numero_operacion"
                                placeholder="Ej: 123456789" required>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i>
                                Ingresa el número de operación que aparece en tu app Yape
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefono_yape" class="form-label">
                                Número de Celular Yape *
                            </label>
                            <input type="tel" class="form-control form-control-lg"
                                id="telefono_yape" name="telefono_yape"
                                placeholder="987654321" required>
                        </div>

                        <!-- Instrucciones -->
                        <div class="alert alert-info">
                            <strong><i class="bi bi-lightbulb"></i> Instrucciones:</strong>
                            <ol class="mb-0 mt-2 ps-3">
                                <li>Abre tu app Yape</li>
                                <li>Escanea el código QR mostrado arriba</li>
                                <li>Confirma el pago en tu app</li>
                                <li>Ingresa el número de operación aquí</li>
                            </ol>
                        </div>

                        <!-- Botón de procesamiento -->
                        <button type="submit" class="btn btn-purple btn-lg w-100">
                            <span class="btn-text">
                                <i class="bi bi-check-circle"></i> Confirmar Pago
                            </span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago con Tarjeta -->
<div class="modal fade" id="modalTarjeta" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bi bi-credit-card-2-front text-primary"></i>
                    Pagar con Tarjeta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card-payment-container">
                    <!-- Tarjeta Visual -->
                    <div class="credit-card-display mb-4">
                        <div class="credit-card">
                            <div class="card-chip">
                                <i class="bi bi-cpu"></i>
                            </div>
                            <div class="card-number" id="displayCardNumber">**** **** **** ****</div>
                            <div class="card-holder">
                                <div class="label">TITULAR</div>
                                <div id="displayCardName">NOMBRE APELLIDO</div>
                            </div>
                            <div class="card-expiry">
                                <div class="label">VÁLIDA HASTA</div>
                                <div id="displayCardExpiry">MM/AAAA</div>
                            </div>
                            <div class="card-logo" id="cardLogo">
                                <i class="bi bi-credit-card"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Tarjeta -->
                    <form id="tarjetaForm">
                        <div class="mb-3">
                            <label for="numero_tarjeta" class="form-label">
                                Número de Tarjeta *
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="numero_tarjeta" name="numero_tarjeta"
                                placeholder="1234 5678 9012 3456"
                                maxlength="19" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_titular" class="form-label">
                                Nombre del Titular *
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="nombre_titular" name="nombre_titular"
                                placeholder="Como aparece en la tarjeta"
                                required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-7">
                                <label for="fecha_expiracion" class="form-label">
                                    Fecha de Expiración *
                                </label>
                                <input type="text" class="form-control form-control-lg"
                                    id="fecha_expiracion" name="fecha_expiracion"
                                    placeholder="MM/AAAA" maxlength="7" required>
                            </div>
                            <div class="col-5">
                                <label for="cvv" class="form-label">
                                    CVV *
                                    <i class="bi bi-question-circle"
                                        data-bs-toggle="tooltip"
                                        title="Código de 3 o 4 dígitos al reverso de tu tarjeta"></i>
                                </label>
                                <input type="text" class="form-control form-control-lg"
                                    id="cvv" name="cvv"
                                    placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <!-- Monto -->
                        <div class="card-amount-box mb-3">
                            <span>Monto a cobrar:</span>
                            <span class="amount" id="cardTotalAmount">S/ 0.00</span>
                        </div>

                        <!-- Seguridad -->
                        <div class="alert alert-success mb-3">
                            <i class="bi bi-shield-lock"></i>
                            <small>
                                <strong>Pago seguro:</strong> Tus datos están protegidos con encriptación SSL
                            </small>
                        </div>

                        <!-- Botón -->
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <span class="btn-text">
                                <i class="bi bi-lock-fill"></i> Pagar S/ <span id="cardButtonAmount">0.00</span>
                            </span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>

                        <p class="text-center text-muted small mt-3 mb-0">
                            <i class="bi bi-info-circle"></i>
                            Al confirmar aceptas nuestros términos y condiciones
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Procesando -->
<div class="modal fade" id="modalProcesando" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Procesando...</span>
                </div>
                <h5>Procesando Pago...</h5>
                <p class="text-muted mb-0">Por favor espere</p>
            </div>
        </div>
    </div>
</div>