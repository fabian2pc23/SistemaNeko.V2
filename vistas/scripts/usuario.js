var tabla;

/* ==================== Avatar por rol (preview) ==================== */
function getDefaultAvatarForRole(roleText) {
	const k = (roleText || '').toLowerCase().trim();
	if (k === 'administrador' || k === 'admin') return 'administrador.png';
	if (k === 'almacenero') return 'almacenero.png';
	if (k === 'vendedor') return 'vendedor.png';
	return 'usuario.png';
}

function setupPhoneValidation() {
	const telefonoInput = document.getElementById('telefono');
	if (telefonoInput) {
		$(telefonoInput).off('input keypress');

		$(telefonoInput).on('input', function () {
			this.value = this.value.replace(/[^0-9\s\-+]/g, '');
		});

		$(telefonoInput).on('keypress', function (e) {
			const char = String.fromCharCode(e.which);
			if (!/[0-9\s\-+]/.test(char)) {
				e.preventDefault();
				return false;
			}
		});

		console.log('✓ Validación de teléfono activada');
	}
}

// ========== VALIDACIÓN DE DOCUMENTOS ==========
function setupDocumentValidation() {
	const tipo_documento = document.getElementById("tipo_documento");
	const num_documento = document.getElementById("num_documento");
	const nombre = document.getElementById("nombre");
	const hint_tipo = document.getElementById("hint_tipo");
	const hint_numero = document.getElementById("hint_numero");

	if (!tipo_documento || !num_documento || !nombre) {
		console.error('❌ Elementos no encontrados para validación de documentos');
		return;
	}

	let timer;
	let inflight;
	let lastQueried = '';

	console.log('✓ Validación de documentos iniciada');

	$(num_documento).off('input keypress blur');
	$(tipo_documento).off('change');

	$(num_documento).on('input', function (e) {
		const tipoDoc = $(tipo_documento).val();

		if (tipoDoc === 'DNI' || tipoDoc === 'RUC') {
			this.value = this.value.replace(/\D/g, '');
		}

		debounceConsulta();
	});

	$(num_documento).on('keypress', function (e) {
		const tipoDoc = $(tipo_documento).val();

		if (tipoDoc === 'DNI' || tipoDoc === 'RUC') {
			const charCode = (e.which) ? e.which : e.keyCode;
			if (charCode > 31 && (charCode < 48 || charCode > 57)) {
				e.preventDefault();
				return false;
			}
		}
	});

	$(tipo_documento).on('change', function () {
		$(num_documento).val('');
		$(nombre).val('');
		$('#direccion').val('');
		$(nombre).attr('readonly', 'readonly');
		lastQueried = '';

		const tipoSeleccionado = $(this).val();

		if (tipoSeleccionado == "DNI") {
			$(num_documento).attr("maxlength", "8");
			$(num_documento).attr("pattern", "[0-9]{8}");
			$(hint_numero).text("DNI: 8 dígitos").removeClass().addClass("text-muted");
			$(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará RENIEC automáticamente');
			console.log('✓ Modo DNI activado');
		}
		else if (tipoSeleccionado == "RUC") {
			$(num_documento).attr("maxlength", "11");
			$(num_documento).attr("pattern", "[0-9]{11}");
			$(hint_numero).text("RUC: 11 dígitos").removeClass().addClass("text-muted");
			$(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará SUNAT automáticamente');
			console.log('✓ Modo RUC activado');
		}
		else if (tipoSeleccionado == "Carnet de Extranjería") {
			$(num_documento).attr("maxlength", "12");
			$(num_documento).removeAttr("pattern");
			$(hint_numero).text("Carnet: 9-12 caracteres").removeClass().addClass("text-muted");
			$(hint_tipo).html('<i class="fa fa-info-circle text-info"></i> Deberás ingresar el nombre manualmente');
			$(nombre).removeAttr('readonly');
			console.log('✓ Modo Carnet activado');
		}
		else {
			$(num_documento).attr("maxlength", "20");
			$(num_documento).removeAttr("pattern");
			$(hint_numero).text("Ingresa el número de documento").removeClass().addClass("text-muted");
			$(hint_tipo).text("Selecciona el tipo de documento");
		}
	});

	function consultarRENIEC() {
		const tipoDoc = $(tipo_documento).val();
		const numDoc = $(num_documento).val();

		if (tipoDoc !== 'DNI' || !/^\d{8}$/.test(numDoc)) {
			return;
		}

		if (numDoc === lastQueried) {
			return;
		}

		console.log('🔍 Consultando RENIEC para DNI:', numDoc);

		if (inflight) inflight.abort();
		inflight = new AbortController();

		const prevNombre = $(nombre).val();
		const prevDireccion = $('#direccion').val();

		$(nombre).val('Consultando RENIEC...').css('background', '#ffffcc');
		$('#direccion').val('Obteniendo dirección...').css('background', '#ffffcc');
		$(hint_numero).html('<i class="fa fa-spinner fa-spin text-info"></i> Consultando...').removeClass().addClass('text-info');

		$.ajax({
			url: '../ajax/reniec.php',
			type: 'GET',
			data: { dni: numDoc },
			dataType: 'json',
			timeout: 10000,
			success: function (data) {
				console.log('✓ Respuesta RENIEC completa:', data);

				if (data.success === true) {
					const nombreCompleto = ((data.nombres || '') + ' ' + (data.apellidos || '')).trim();
					$(nombre).val(nombreCompleto).css('background', '#d4edda');

					if (data.direccion && data.direccion.trim() !== '') {
						$('#direccion').val(data.direccion).css('background', '#d4edda');
						console.log('✅ Dirección obtenida:', data.direccion);

						setTimeout(function () {
							$('#direccion').css('background', '');
						}, 3000);
					} else {
						$('#direccion').val('').css('background', '');
						console.warn('⚠️ No se obtuvo dirección desde RENIEC');
					}

					$(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por RENIEC').removeClass().addClass('text-success');
					lastQueried = numDoc;

					setTimeout(function () {
						$(nombre).css('background', '');
						$(hint_numero).removeClass().addClass('text-muted');
					}, 3000);
				} else {
					throw new Error(data.message || 'Error al consultar RENIEC');
				}
			},
			error: function (xhr, status, error) {
				console.error('❌ Error RENIEC:', error, xhr.responseText);
				$(nombre).val(prevNombre).css('background', '#f8d7da');
				$('#direccion').val(prevDireccion).css('background', '');
				$(hint_numero).html('<i class="fa fa-times text-danger"></i> ' + (error || 'Error al consultar RENIEC')).removeClass().addClass('text-danger');

				setTimeout(function () {
					$(nombre).css('background', '');
					$(hint_numero).text('DNI: 8 dígitos').removeClass().addClass('text-muted');
				}, 4000);
			}
		});
	}

	function consultarSUNAT() {
		const tipoDoc = $(tipo_documento).val();
		const numDoc = $(num_documento).val();

		if (tipoDoc !== 'RUC' || !/^\d{11}$/.test(numDoc)) {
			return;
		}

		if (numDoc === lastQueried) {
			return;
		}

		console.log('🔍 Consultando SUNAT para RUC:', numDoc);

		if (inflight) inflight.abort();
		inflight = new AbortController();

		const prevNombre = $(nombre).val();
		const prevDireccion = $('#direccion').val();

		$(nombre).val('Consultando SUNAT...').css('background', '#ffffcc');
		$('#direccion').val('Obteniendo dirección...').css('background', '#ffffcc');
		$(hint_numero).html('<i class="fa fa-spinner fa-spin text-info"></i> Consultando...').removeClass().addClass('text-info');

		$.ajax({
			url: '../ajax/sunat.php',
			type: 'GET',
			data: { ruc: numDoc },
			dataType: 'json',
			timeout: 10000,
			success: function (data) {
				console.log('✓ Respuesta SUNAT completa:', data);

				if (data.success === true) {
					$(nombre).val(data.razon_social || '').css('background', '#d4edda');

					if (data.direccion && data.direccion.trim() !== '') {
						$('#direccion').val(data.direccion).css('background', '#d4edda');
						console.log('✅ Dirección obtenida:', data.direccion);

						setTimeout(function () {
							$('#direccion').css('background', '');
						}, 3000);
					} else {
						$('#direccion').val('').css('background', '');
						console.warn('⚠️ No se obtuvo dirección desde SUNAT');
					}

					$(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por SUNAT').removeClass().addClass('text-success');
					lastQueried = numDoc;

					setTimeout(function () {
						$(nombre).css('background', '');
						$(hint_numero).removeClass().addClass('text-muted');
					}, 3000);
				} else {
					throw new Error(data.message || 'Error al consultar SUNAT');
				}
			},
			error: function (xhr, status, error) {
				console.error('❌ Error SUNAT:', error, xhr.responseText);
				$(nombre).val(prevNombre).css('background', '#f8d7da');
				$('#direccion').val(prevDireccion).css('background', '');
				$(hint_numero).html('<i class="fa fa-times text-danger"></i> ' + (error || 'Error al consultar SUNAT')).removeClass().addClass('text-danger');

				setTimeout(function () {
					$(nombre).css('background', '');
					$(hint_numero).text('RUC: 11 dígitos').removeClass().addClass('text-muted');
				}, 4000);
			}
		});
	}

	function debounceConsulta() {
		clearTimeout(timer);
		timer = setTimeout(function () {
			const tipoDoc = $(tipo_documento).val();
			if (tipoDoc === 'DNI') {
				consultarRENIEC();
			} else if (tipoDoc === 'RUC') {
				consultarSUNAT();
			}
		}, 1000);
	}

	$(num_documento).on('blur', function () {
		const tipoDoc = $(tipo_documento).val();
		if (tipoDoc === 'DNI') {
			consultarRENIEC();
		} else if (tipoDoc === 'RUC') {
			consultarSUNAT();
		}
	});
}

// ========== VALIDACIÓN DE EMAIL ==========
function setupEmailValidation() {
	const emailInput = document.getElementById('email');
	const emailHint = document.getElementById('email-hint');
	const emailStatus = document.getElementById('email-status');

	if (!emailInput) {
		console.error('❌ Campo email no encontrado');
		return;
	}

	console.log('✓ Validación de email iniciada');

	let timer;
	let lastChecked = '';

	$(emailInput).off('input blur');

	function isValidFormat(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	function validateEmail() {
		const email = $(emailInput).val().trim();

		if (!email) {
			$(emailStatus).text('');
			$(emailHint).text('Se usará como usuario de acceso al sistema').removeClass().addClass('text-muted');
			emailInput.setCustomValidity('');
			return;
		}

		if (email === lastChecked) {
			return;
		}

		if (!isValidFormat(email)) {
			$(emailStatus).text('❌');
			$(emailHint).text('Formato de correo inválido').removeClass().addClass('text-danger');
			emailInput.setCustomValidity('Formato inválido');
			return;
		}

		$(emailStatus).text('⏳');
		$(emailHint).text('Verificando correo...').removeClass().addClass('text-info');

		console.log('🔍 Validando email:', email);

		$.ajax({
			url: '../ajax/validate_email.php',
			type: 'GET',
			data: { email: email },
			dataType: 'json',
			timeout: 10000,
			success: function (data) {
				console.log('✓ Respuesta validación email:', data);

				if (data.success && data.valid) {
					$(emailStatus).text('✅');
					$(emailHint).text('Correo válido y verificado').removeClass().addClass('text-success');
					emailInput.setCustomValidity('');
					lastChecked = email;
				} else {
					$(emailStatus).text('❌');
					$(emailHint).text(data.message || 'Este correo no es válido').removeClass().addClass('text-danger');
					emailInput.setCustomValidity(data.message || 'Email inválido');
				}
			},
			error: function (xhr, status, error) {
				console.warn('⚠️ Error validación email:', error);
				$(emailStatus).text('⚠️');
				$(emailHint).text('No se pudo verificar. Asegúrate que sea un correo real.').removeClass().addClass('text-warning');
				emailInput.setCustomValidity('');
			}
		});
	}

	function debounce() {
		clearTimeout(timer);
		timer = setTimeout(validateEmail, 1200);
	}

	$(emailInput).on('input', debounce);
	$(emailInput).on('blur', validateEmail);
}

// ========== VALIDACIÓN DE CONTRASEÑA ==========
function setupPasswordValidation() {
	const pwd = document.getElementById('clave');
	const strengthDiv = document.getElementById('pwd-strength');

	if (!pwd || !strengthDiv) {
		console.error('❌ Elementos de contraseña no encontrados');
		return;
	}

	console.log('✓ Validación de contraseña iniciada');

	$(pwd).off('input focus');

	function mark(id, ok) {
		const el = document.getElementById(id);
		if (!el) return;

		const icon = $(el).find('i');
		if (ok) {
			icon.removeClass('fa-times text-danger').addClass('fa-check text-success');
		} else {
			icon.removeClass('fa-check text-success').addClass('fa-times text-danger');
		}
	}

	function checkStrength(v) {
		if (!v) {
			$(strengthDiv).hide();
			return false;
		}

		$(strengthDiv).show();

		const len = v.length >= 10 && v.length <= 64;
		const up = /[A-Z]/.test(v);
		const low = /[a-z]/.test(v);
		const num = /[0-9]/.test(v);
		const spe = /[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(v);

		mark('r-len', len);
		mark('r-up', up);
		mark('r-low', low);
		mark('r-num', num);
		mark('r-spe', spe);

		return len && up && low && num && spe;
	}

	$(pwd).on('input', function () {
		checkStrength($(this).val());
	});

	$(pwd).on('focus', function () {
		if ($(this).val()) {
			$(strengthDiv).show();
		}
	});
}

// ========== VER/OCULTAR CONTRASEÑA ==========
function togglePasswordVisibility() {
	const toggleBtn = document.getElementById('toggleClave');
	const pwdInput = document.getElementById('clave');

	if (!toggleBtn || !pwdInput) {
		console.error('❌ Botón toggle contraseña no encontrado');
		return;
	}

	console.log('✓ Toggle contraseña activado');

	$(toggleBtn).off('click');

	$(toggleBtn).on('click', function () {
		if ($(pwdInput).attr('type') === 'password') {
			$(pwdInput).attr('type', 'text');
			$(this).text('👁️');
		} else {
			$(pwdInput).attr('type', 'password');
			$(this).text('👁️');
		}
	});
}

// ========== FUNCIONES ORIGINALES ==========

function limpiar() {
	$("#nombre").val("");
	$("#tipo_documento").val("");
	$("#num_documento").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#email").val("");
	$("#cargo").val("");
	$("#clave").val("");
	$("#imagenmuestra").attr("src", "");
	$("#imagenactual").val("");
	$("#idusuario").val("");
	$("#imagenmuestra").hide();
	$("#pwd-strength").hide();
	$("#email-status").text("");
	$("#email-hint").text("Se usará como usuario de acceso al sistema").removeClass().addClass("text-muted");
	$("#hint_numero").text("Ingresa el número de documento").removeClass().addClass("text-muted");
	$("#hint_tipo").text("Selecciona el tipo de documento").removeClass().addClass("text-muted");

	if (document.getElementById('email')) {
		document.getElementById('email').setCustomValidity('');
	}
	$("#nombre").attr('readonly', 'readonly');

	$("#clave").prop("required", true);

	// Limpiar múltiples roles
	$("#cargo").selectpicker('val', []);
	if (typeof lastSelectedRoles !== 'undefined') {
		lastSelectedRoles = [];
	}
	$("#permisos input[type='checkbox']").prop("checked", false);

	cargarRoles();
}

function mostrarform(flag) {
	limpiar();
	if (flag) {
		$("#listadoregistros").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled", false);
		$("#btnagregar").hide();
	}
	else {
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
	}
}

function cancelarform() {
	limpiar();
	mostrarform(false);
}

function listar() {
	tabla = $('#tbllistado').DataTable(
		{
			"aProcessing": true,
			"aServerSide": true,
			dom: 'Brtip',
			buttons: [
				{ extend: 'copyHtml5', className: 'buttons-copy' },
				{ extend: 'excelHtml5', className: 'buttons-excel' },
				{ extend: 'csvHtml5', className: 'buttons-csv' },
				{ extend: 'pdf', className: 'buttons-pdf' }
			],
			"ajax":
			{
				url: '../ajax/usuario.php?op=listar',
				type: "get",
				dataType: "json",
				error: function (e) {
					console.log(e.responseText);
				}
			},
			"language": {
				"lengthMenu": "Mostrar : _MENU_ registros",
				"buttons": {
					"copyTitle": "Tabla Copiada",
					"copySuccess": {
						_: '%d líneas copiadas',
						1: '1 línea copiada'
					}
				}
			},
			"bDestroy": true,
			"iDisplayLength": 10,
			"order": [[0, "desc"]]
		});
}



function guardaryeditar(e) {
	e.preventDefault();

	// Validaciones...
	var tipoDocumento = $("#tipo_documento").val();
	if (!tipoDocumento || tipoDocumento === '') {
		bootbox.alert({
			message: "⚠️ Debes seleccionar un <strong>TIPO DE DOCUMENTO</strong> antes de guardar el usuario.",
			className: 'bootbox-warning'
		});
		$("#tipo_documento").focus();
		return;
	}

	var numDocumento = $("#num_documento").val().trim();
	if (!numDocumento || numDocumento === '') {
		bootbox.alert({
			message: "⚠️ Debes ingresar el <strong>NÚMERO DE DOCUMENTO</strong> antes de guardar el usuario.",
			className: 'bootbox-warning'
		});
		$("#num_documento").focus();
		return;
	}

	if (tipoDocumento === 'DNI' && !/^\d{8}$/.test(numDocumento)) {
		bootbox.alert({
			message: "⚠️ El <strong>DNI</strong> debe tener exactamente <strong>8 dígitos</strong>.",
			className: 'bootbox-warning'
		});
		$("#num_documento").focus();
		return;
	}

	if (tipoDocumento === 'RUC' && !/^\d{11}$/.test(numDocumento)) {
		bootbox.alert({
			message: "⚠️ El <strong>RUC</strong> debe tener exactamente <strong>11 dígitos</strong>.",
			className: 'bootbox-warning'
		});
		$("#num_documento").focus();
		return;
	}

	if (tipoDocumento === 'Carnet de Extranjería' && (numDocumento.length < 9 || numDocumento.length > 12)) {
		bootbox.alert({
			message: "⚠️ El <strong>Carnet de Extranjería</strong> debe tener entre <strong>9 y 12 caracteres</strong>.",
			className: 'bootbox-warning'
		});
		$("#num_documento").focus();
		return;
	}

	var nombre = $("#nombre").val().trim();
	if (!nombre || nombre === '' || nombre === 'Consultando RENIEC...' || nombre === 'Consultando SUNAT...') {
		bootbox.alert({
			message: "⚠️ El <strong>NOMBRE</strong> debe estar completo.<br><br>Por favor espera a que se valide el documento o ingrésalo manualmente.",
			className: 'bootbox-warning'
		});
		$("#nombre").focus();
		return;
	}

	var email = $("#email").val().trim();
	if (!email || email === '') {
		bootbox.alert({
			message: "⚠️ Debes ingresar un <strong>EMAIL</strong> antes de guardar el usuario.",
			className: 'bootbox-warning'
		});
		$("#email").focus();
		return;
	}

	if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
		bootbox.alert({
			message: "⚠️ El formato del <strong>EMAIL</strong> no es válido.<br><br><strong>Ejemplo:</strong> usuario@dominio.com",
			className: 'bootbox-warning'
		});
		$("#email").focus();
		return;
	}

	var emailStatus = $("#email-status").text().trim();
	if (emailStatus === '❌') {
		bootbox.alert({
			message: "⚠️ El <strong>EMAIL</strong> ingresado no es válido o ya está registrado.<br><br>Por favor verifica e intenta nuevamente.",
			className: 'bootbox-warning'
		});
		$("#email").focus();
		return;
	}

	// ✅ VALIDAR MÚLTIPLES ROLES (SELECTPICKER)
	var roles = $("#cargo").val();
	if (!roles || roles.length === 0) {
		bootbox.alert({
			message: "⚠️ Debes agregar al menos <strong>UN ROL</strong> antes de guardar el usuario.",
			className: 'bootbox-warning'
		});
		return;
	}

	var clave = $("#clave").val().trim();
	var esNuevoUsuario = !$("#idusuario").val() || $("#idusuario").val() === '';

	if (esNuevoUsuario && (!clave || clave === '')) {
		bootbox.alert({
			message: "⚠️ Debes ingresar una <strong>CONTRASEÑA</strong> para el nuevo usuario.",
			className: 'bootbox-warning'
		});
		$("#clave").focus();
		return;
	}

	if (clave && clave !== '') {
		if (clave.length < 10 || clave.length > 64) {
			bootbox.alert({
				message: "⚠️ La <strong>CONTRASEÑA</strong> debe tener entre <strong>10 y 64 caracteres</strong>.",
				className: 'bootbox-warning'
			});
			$("#clave").focus();
			return;
		}

		if (!/[A-Z]/.test(clave)) {
			bootbox.alert({
				message: "⚠️ La <strong>CONTRASEÑA</strong> debe contener al menos <strong>1 letra MAYÚSCULA</strong>.",
				className: 'bootbox-warning'
			});
			$("#clave").focus();
			return;
		}

		if (!/[a-z]/.test(clave)) {
			bootbox.alert({
				message: "⚠️ La <strong>CONTRASEÑA</strong> debe contener al menos <strong>1 letra MINÚSCULA</strong>.",
				className: 'bootbox-warning'
			});
			$("#clave").focus();
			return;
		}

		if (!/[0-9]/.test(clave)) {
			bootbox.alert({
				message: "⚠️ La <strong>CONTRASEÑA</strong> debe contener al menos <strong>1 NÚMERO</strong>.",
				className: 'bootbox-warning'
			});
			$("#clave").focus();
			return;
		}

		if (!/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(clave)) {
			bootbox.alert({
				message: "⚠️ La <strong>CONTRASEÑA</strong> debe contener al menos <strong>1 carácter ESPECIAL</strong> (!@#$%^&*...).",
				className: 'bootbox-warning'
			});
			$("#clave").focus();
			return;
		}
	}

	console.log("✅ Todas las validaciones pasaron correctamente");

	$("#btnGuardar").prop("disabled", true);
	var formData = new FormData($("#formulario")[0]);

	var _claveActual = ($("#clave").val() || "").trim();
	if (!_claveActual) {
		formData.delete('clave');
		formData.append('mantener_clave', '1');
	} else {
		formData.append('mantener_clave', '0');
	}

	// ✅ Enviar roles como JSON
	// Construir array de roles con el principal marcado
	var rolesData = [];
	roles.forEach(function (rId) {
		rolesData.push({
			id_rol: rId,
			es_principal: (principalRoleId && rId.toString() === principalRoleId.toString()) ? 1 : 0
		});
	});

	// Si no hay principal marcado pero hay roles, marcar el primero
	var tienePrincipal = rolesData.some(r => r.es_principal === 1);
	if (!tienePrincipal && rolesData.length > 0) {
		rolesData[0].es_principal = 1;
	}

	formData.append('roles_data', JSON.stringify(rolesData));

	// Enviar cargo principal como texto (para compatibilidad)
	var nombreRolPrincipal = $("#cargo option[value='" + roles[0] + "']").text();
	formData.set('cargo', nombreRolPrincipal);

	$.ajax({
		url: "../ajax/usuario.php?op=guardaryeditar",
		type: "POST",
		data: formData,
		contentType: false,
		processData: false,

		success: function (datos) {
			bootbox.alert(datos);
			mostrarform(false);
			tabla.ajax.reload();
		},
		error: function (xhr, status, error) {
			bootbox.alert("Error al guardar: " + error);
			$("#btnGuardar").prop("disabled", false);
		}

	});
	limpiar();
}

function mostrar(idusuario) {
	$.post("../ajax/usuario.php?op=mostrar", { idusuario: idusuario }, function (data, status) {
		data = JSON.parse(data);
		mostrarform(true);

		$("#tipo_documento").val(data.tipo_documento);
		$("#tipo_documento").selectpicker('refresh');
		$("#tipo_documento").trigger('change');

		$("#num_documento").val(data.num_documento);
		$("#nombre").val(data.nombre);
		$("#nombre").removeAttr('readonly');

		$("#direccion").val(data.direccion);
		$("#telefono").val(data.telefono);
		$("#email").val(data.email);

		// ✅ CARGAR ROLES MÚLTIPLES
		$.getJSON("../ajax/usuario.php?op=obtener_roles_usuario&idusuario=" + idusuario, function (roles) {
			var rolesIds = roles.map(r => r.id_rol);

			// Encontrar el rol principal
			var principal = roles.find(r => r.es_principal == 1);
			if (principal) {
				principalRoleId = principal.id_rol;
			} else if (roles.length > 0) {
				principalRoleId = roles[0].id_rol;
			}

			$("#cargo").selectpicker('val', rolesIds);
			lastSelectedRoles = rolesIds;
			renderRoleBadges(rolesIds);
			cargarPermisosAcumulativos(rolesIds);
		});

		$("#clave").val("");
		$("#clave").prop("required", false);
		$("#clave").attr("placeholder", "Dejar en blanco para mantener la contraseña");
		$("#toggleClave").text('👁️');
		$("#clave").attr('type', 'password');

		$("#imagenmuestra").show();
		$("#imagenmuestra").attr("src", "../files/usuarios/" + data.imagen);
		$("#imagenactual").val(data.imagen);
		$("#idusuario").val(data.idusuario);

	});
}

function desactivar(idusuario) {
	bootbox.confirm("¿Está Seguro de desactivar el usuario?", function (result) {
		if (result) {
			$.post("../ajax/usuario.php?op=desactivar", { idusuario: idusuario }, function (e) {
				bootbox.alert(e);
				tabla.ajax.reload();
			});
		}
	})
}

function activar(idusuario) {
	bootbox.confirm("¿Está Seguro de activar el Usuario?", function (result) {
		if (result) {
			$.post("../ajax/usuario.php?op=activar", { idusuario: idusuario }, function (e) {
				bootbox.alert(e);
				tabla.ajax.reload();
			});
		}
	})
}

function cargarRoles() {
	$.post("../ajax/usuario.php?op=selectRol", function (r) {
		$("#cargo").html(r);
		$("#cargo").selectpicker('refresh');
	}).fail(function (xhr, status, error) {
		console.error('❌ Error cargando roles:', error);
		bootbox.alert('Error al cargar los roles. Recarga la página.');
	});
}

/* ==================== NUEVAS FUNCIONES FILTROS ==================== */
function filtrarEstado(estado) {
	// Reset botones
	$('.status-btn').removeClass('active');

	// Columna 8 es Estado
	var colEstado = tabla.column(8);

	if (estado === 'todos') {
		$('#filter-todos').addClass('active');
		colEstado.search('').draw();
	} else if (estado === 'activos') {
		$('#filter-activos').addClass('active');
		// Buscamos "Activado" (clase .bg-green)
		colEstado.search('Activado', true, false).draw();
	} else if (estado === 'bloqueados') {
		$('#filter-bloqueados').addClass('active');
		// Buscamos "Desactivado" (clase .bg-red)
		colEstado.search('Desactivado', true, false).draw();
	}
}

function setupSearchInput() {
	$('#search-input').on('keyup', function () {
		tabla.search(this.value).draw();
	});
}

function cambiarLongitud(len) {
	tabla.page.len(len).draw();
}

function exportarTabla(type) {
	if (type === 'copy') tabla.button('.buttons-copy').trigger();
	if (type === 'excel') tabla.button('.buttons-excel').trigger();
	if (type === 'csv') tabla.button('.buttons-csv').trigger();
	if (type === 'pdf') tabla.button('.buttons-pdf').trigger();
}

/* ==================== LÓGICA DE ROLES MÚLTIPLES ==================== */
var lastSelectedRoles = [];
var principalRoleId = null;

function setupRoleLogic() {
	// Usar evento 'change' estándar que bootstrap-select también dispara
	$('#cargo').on('change', function () {
		var selectedIds = $(this).val();

		// Convertir a array si es null (cuando no hay selección)
		if (!selectedIds) selectedIds = [];

		// Asegurar que sea un array de strings
		if (!Array.isArray(selectedIds)) {
			selectedIds = [selectedIds];
		}

		console.log("Event: change detected. Selected IDs:", selectedIds);

		// Lógica de exclusividad de Admin
		var selectedOptions = $(this).find('option:selected');
		var isAdminSelected = false;
		var adminVal = "";

		selectedOptions.each(function () {
			var txt = $(this).text().trim().toLowerCase();
			if (txt === 'administrador' || txt === 'admin') {
				isAdminSelected = true;
				adminVal = $(this).val();
			}
		});

		// Si se selecciona Admin, limpiar otros (pero evitar bucle infinito)
		if (isAdminSelected && selectedIds.length > 1) {
			// Solo actualizar si realmente hay cambio para evitar bucles
			var currentVal = $(this).val();
			// Asegurar que currentVal sea array para la comparación
			if (!Array.isArray(currentVal)) currentVal = [currentVal];

			if (currentVal.length !== 1 || currentVal[0] !== adminVal) {
				$('#cargo').selectpicker('val', [adminVal]);
				// Forzar el evento change para que se re-rendericen los badges
				$('#cargo').trigger('change');
				return;
			}
		}

		lastSelectedRoles = selectedIds;
		renderRoleBadges(selectedIds);
		cargarPermisosAcumulativos(selectedIds);
	});
}

function renderRoleBadges(selectedIds) {
	console.log("renderRoleBadges called with:", selectedIds);
	var container = $("#roles-badges");

	// Ensure container exists
	if (container.length === 0) {
		console.error("❌ Error: #roles-badges container not found! Creating it.");
		container = $("<div id='roles-badges'></div>");
	}

	// Move container to the correct place: after the bootstrap-select div
	var bsContainer = $("#cargo").parent('.bootstrap-select');
	if (bsContainer.length > 0) {
		container.insertAfter(bsContainer);
	} else {
		container.insertAfter("#cargo");
	}

	// DEBUG: Añadir borde para verificar visibilidad (Temporal)
	// container.css('border', '2px dashed #ccc'); 
	// (User's image showed a dashed box, maybe they added it or it's from previous CSS? 
	//  I will force it to be visible with a background color to be sure)
	container.css('display', 'flex');

	container.empty();

	if (!selectedIds || selectedIds.length === 0) {
		console.log("No roles selected, hiding container");
		container.hide();
		return;
	}
	container.show();

	// Si no hay rol principal seleccionado o el seleccionado ya no está en la lista,
	// asignar el primero como principal por defecto.
	if (!principalRoleId || !selectedIds.includes(principalRoleId.toString())) {
		principalRoleId = selectedIds[0];
	}

	selectedIds.forEach(function (id) {
		// Intentar obtener el texto de la opción
		var option = $("#cargo option[value='" + id + "']");
		var text = option.text();

		// Fallback si el texto está vacío (no debería pasar si el select está cargado)
		if (!text) {
			console.warn("Texto no encontrado para ID:", id);
			text = "Rol " + id;
		}

		var isPrincipal = (id.toString() === principalRoleId.toString());

		var badgeClass = "role-badge";
		if (text.toLowerCase().includes("admin")) badgeClass += " admin";

		var starIcon = isPrincipal ? "fa-star principal-icon" : "fa-star-o";
		var starTitle = isPrincipal ? "Rol Principal" : "Hacer Principal";

		var badgeHtml = `
            <div class="${badgeClass}" data-role-id="${id}">
                <i class="fa ${starIcon} role-star" title="${starTitle}" style="cursor: pointer;"></i>
                <span>${text}</span>
                <span class="remove-role" title="Quitar rol" style="cursor: pointer;">
                    <i class="fa fa-times"></i>
                </span>
            </div>
        `;

		container.append(badgeHtml);
	});
}

function setPrincipalRole(roleId) {
	principalRoleId = roleId;
	var selectedRoles = $("#cargo").val();
	renderRoleBadges(selectedRoles);
}

function removeRole(id) {
	var current = $("#cargo").val() || [];
	var newRoles = current.filter(r => r != id);
	$("#cargo").selectpicker('val', newRoles);
	// Trigger change para actualizar todo
	$("#cargo").trigger('changed.bs.select');
}

function cargarPermisosAcumulativos(rolesIds) {
	// Primero desmarcar todos
	$("#permisos input[type='checkbox']").prop("checked", false);

	if (!rolesIds || rolesIds.length === 0) return;

	rolesIds.forEach(function (rolId) {
		$.getJSON("../ajax/usuario.php?op=permisos_por_rol&id_rol=" + rolId, function (permisos) {
			if (permisos && permisos.length > 0) {
				permisos.forEach(function (pId) {
					$("#permisos input[value='" + pId + "']").prop("checked", true);
				});
			}
		});
	});
}

function init() {
	mostrarform(false);
	listar();
	setupSearchInput();
	setupRoleLogic();

	// Event delegation for role badges
	$(document).on('click', '.role-star', function () {
		var roleId = $(this).closest('.role-badge').data('role-id');
		setPrincipalRole(roleId);
	});

	$(document).on('click', '.remove-role', function () {
		var roleId = $(this).closest('.role-badge').data('role-id');
		removeRole(roleId);
	});

	$("#formulario").on("submit", function (e) {
		guardaryeditar(e);
	});

	$("#imagenmuestra").hide();
	$("#pwd-strength").hide();

	// Campo oculto para modo permisos
	if (!document.getElementById('modo_permisos')) {
		$('<input>', { type: 'hidden', id: 'modo_permisos', name: 'modo_permisos', value: '' }).appendTo('#formulario');
	}

	// Cargar permisos iniciales (todos desmarcados)
	$.post("../ajax/usuario.php?op=permisos&id=0", function (r) {
		$("#permisos").html(r);
		// Deshabilitar checkboxes porque son automáticos por rol
		$("#permisos input[type='checkbox']")
			.prop("disabled", true)
			.css("cursor", "not-allowed");
	});

	cargarRoles();

	$('#mAcceso').addClass("treeview active");
	$('#lUsuarios').addClass("active");

	// Inicializar validaciones
	setTimeout(function () {
		setupDocumentValidation();
		setupPasswordValidation();
		setupEmailValidation();
		setupPhoneValidation();
		togglePasswordVisibility();
	}, 300);
}

init();
