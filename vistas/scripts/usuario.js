var tabla;

/* ==================== Avatar por rol (preview) ==================== */
function getDefaultAvatarForRole(roleText){
  const k = (roleText || '').toLowerCase().trim();
  if (k === 'administrador') return 'administrador.png';
  if (k === 'almacenero')   return 'almacenero.png';
  if (k === 'vendedor')     return 'vendedor.png';
  return 'usuario.png';
}
function actualizarPreviewAvatarPorRol(){
  var $sel = $("#cargo option:selected");
  var nombreRol = $.trim($sel.text() || "");
  var file = getDefaultAvatarForRole(nombreRol);
  var hasFileChosen = ($("#imagen").val() || "").length > 0;
  if (!hasFileChosen){
    $("#imagenmuestra").attr("src","../files/usuarios/" + file).show();
    $("#imagenactual").val(file);
  }
}

//Función que se ejecuta al inicio
function init(){
	mostrarform(false);
	listar();

	$("#formulario").on("submit",function(e)
	{
		guardaryeditar(e);	
	})

	$("#imagenmuestra").hide();
	$("#pwd-strength").hide();
	
	// Asegurar hidden para modo de permisos (rol | personalizado | "")
	if (!document.getElementById('modo_permisos')) {
		$('<input>', {type:'hidden', id:'modo_permisos', name:'modo_permisos', value:''}).appendTo('#formulario');
	}

	//Mostramos los permisos
	$.post("../ajax/usuario.php?op=permisos&id=",function(r){
	    $("#permisos").html(r);
		// Si toca manualmente un permiso => modo personalizado
		$("#permisos").off('change.modo').on('change.modo', "input[name='permiso[]']", function(){
			$("#modo_permisos").val('personalizado');
		});
	});
	
	// ✅ Cargar roles dinámicamente (sin selección inicial)
	cargarRoles();

	$('#mAcceso').addClass("treeview active");
    $('#lUsuarios').addClass("active");
    
    setTimeout(function() {
	    setupDocumentValidation();
	    setupPasswordValidation();
	    setupEmailValidation();
	    setupPhoneValidation();
	    togglePasswordVisibility();
    }, 300);
}

/* ============================================================
   ✅ Cargar roles desde la base de datos con selección
   - selectedId: (opcional) id_rol a seleccionar
   - selectedLabel: (opcional) nombre del rol a seleccionar si no hay id
   ============================================================ */
function cargarRoles(selectedId, selectedLabel) {
	$.post("../ajax/usuario.php?op=selectRol", function(r){
		$("#cargo").html(r);

		// Si recibimos un id_rol, intentamos seleccionarlo
		if (selectedId) {
			$("#cargo").val(String(selectedId));
		} else if (selectedLabel) {
			// Si no tenemos id, buscamos por el texto (nombre del rol)
			var found = false;
			$("#cargo option").each(function(){
				if ($.trim($(this).text()) === $.trim(selectedLabel)) {
					$("#cargo").val($(this).val());
					found = true;
					return false;
				}
			});
		}
		$("#cargo").selectpicker('refresh');

		/* ============================================================
		   ✅ Al CAMBIAR rol manualmente:
		      1) modo_permisos='rol'
		      2) pedir permisos del rol y tildar checkboxes
		      3) preview avatar por rol
		   ============================================================ */
		$("#cargo").off("change.autoPermisos").on("change.autoPermisos", function(){
			var idRolSel = $(this).val();
			if (idRolSel) {
				$("#modo_permisos").val('rol');
				cargarPermisosDeRol(idRolSel);
			}
			actualizarPreviewAvatarPorRol();
		});

		// Preview inicial
		setTimeout(actualizarPreviewAvatarPorRol, 150);

	}).fail(function(xhr, status, error) {
		console.error('❌ Error cargando roles:', error);
		bootbox.alert('Error al cargar los roles. Recarga la página.');
	});
}

/* ============================================================
   ✅ Pedir permisos del rol y tildar checkboxes (name="permiso[]")
   Endpoint: ../ajax/usuario.php?op=permisos_por_rol&id_rol=#
   Respuesta esperada: [1,2,3,...] (IDs de permisos)
   ============================================================ */
function cargarPermisosDeRol(idRol){
	// Si la lista de permisos aún no está pintada, reintenta
	if ($("#permisos input[name='permiso[]']").length === 0) {
		setTimeout(function(){ cargarPermisosDeRol(idRol); }, 150);
		return;
	}
	$.getJSON("../ajax/usuario.php?op=permisos_por_rol&id_rol="+encodeURIComponent(idRol))
	 .done(function(ids){
		if (!Array.isArray(ids)) {
			console.warn("⚠️ permisos_por_rol no devolvió un array. Respuesta:", ids);
			return;
		}
		// Desmarcar todo y marcar solo los del rol
		$("#permisos input[name='permiso[]']").prop("checked", false);
		ids.forEach(function(pid){
			$("#permisos input[name='permiso[]'][value='"+pid+"']").prop("checked", true);
		});
		console.log("✓ Permisos del rol aplicados:", ids);
	 })
	 .fail(function(xhr, status, error){
		console.error("❌ No se pudieron cargar permisos del rol:", error);
	});
}

// ========== VALIDACIÓN DE TELÉFONO ==========
function setupPhoneValidation() {
	const telefonoInput = document.getElementById('telefono');
	
	if (telefonoInput) {
		$(telefonoInput).off('input keypress');
		
		$(telefonoInput).on('input', function() {
			this.value = this.value.replace(/[^0-9\s\-+]/g, '');
		});
		
		$(telefonoInput).on('keypress', function(e) {
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
	let lastValidationCheck = '';

	console.log('✓ Validación de documentos iniciada');

	$(num_documento).off('input keypress blur');
	$(tipo_documento).off('change');

	$(num_documento).on('input', function(e) {
		const tipoDoc = $(tipo_documento).val();
		
		if (tipoDoc === 'DNI' || tipoDoc === 'RUC') {
			this.value = this.value.replace(/\D/g, '');
		}
		
		debounceConsulta();
		debounceValidacionDocumento();
	});

	$(num_documento).on('keypress', function(e) {
		const tipoDoc = $(tipo_documento).val();
		
		if (tipoDoc === 'DNI' || tipoDoc === 'RUC') {
			const charCode = (e.which) ? e.which : e.keyCode;
			if (charCode > 31 && (charCode < 48 || charCode > 57)) {
				e.preventDefault();
				return false;
			}
		}
	});

	$(tipo_documento).on('change', function(){
		$(num_documento).val('');
		$(nombre).val('');
		$('#direccion').val('');
		$(nombre).attr('readonly', 'readonly');
		lastQueried = '';
		lastValidationCheck = '';
		
		const tipoSeleccionado = $(this).val();
		
		if(tipoSeleccionado == "DNI"){
			$(num_documento).attr("maxlength", "8");
			$(num_documento).attr("pattern", "[0-9]{8}");
			$(hint_numero).text("DNI: 8 dígitos").removeClass().addClass("text-muted");
			$(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará RENIEC automáticamente');
			console.log('✓ Modo DNI activado');
		}
		else if(tipoSeleccionado == "RUC"){
			$(num_documento).attr("maxlength", "11");
			$(num_documento).attr("pattern", "[0-9]{11}");
			$(hint_numero).text("RUC: 11 dígitos").removeClass().addClass("text-muted");
			$(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará SUNAT automáticamente');
			console.log('✓ Modo RUC activado');
		}
		else if(tipoSeleccionado == "Carnet de Extranjería"){
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

	// ========== NUEVA FUNCIÓN: VALIDAR SI EL DOCUMENTO YA EXISTE ==========
	function validarDocumentoExistente() {
		const tipoDoc = $(tipo_documento).val();
		const numDoc = $(num_documento).val().trim();
		const idusuario = $("#idusuario").val() || 0;
		
		// Solo validar si hay tipo y número de documento
		if (!tipoDoc || !numDoc) {
			return;
		}
		
		// Validar formato primero
		if (tipoDoc === 'DNI' && numDoc.length !== 8) return;
		if (tipoDoc === 'RUC' && numDoc.length !== 11) return;
		if (tipoDoc === 'Carnet de Extranjería' && numDoc.length < 9) return;
		
		// No validar si es el mismo documento que se consultó antes
		const checkKey = tipoDoc + '|' + numDoc;
		if (checkKey === lastValidationCheck) {
			return;
		}
		
		console.log('🔍 Verificando si el documento ya existe:', tipoDoc, numDoc);
		
		$.ajax({
			url: '../ajax/validate_documento.php',
			type: 'GET',
			data: { 
				tipo_documento: tipoDoc,
				num_documento: numDoc,
				idusuario: idusuario
			},
			dataType: 'json',
			timeout: 8000,
			success: function(data) {
				console.log('✓ Respuesta validación documento:', data);
				
				if (data.success && data.exists) {
					// El documento ya está registrado
					$(hint_numero).html(
						'<i class="fa fa-times text-danger"></i> ' + 
						(data.message || 'Este documento ya está registrado')
					).removeClass().addClass('text-danger');
					
					// Marcar el input como inválido
					num_documento.setCustomValidity(data.message || 'Documento ya registrado');
					
					// Opcional: Mostrar alerta
					bootbox.alert({
						message: '⚠️ ' + data.message + 
								 (data.usuario_existente && data.usuario_existente.email 
								  ? '<br><small>Email: ' + data.usuario_existente.email + '</small>' 
								  : ''),
						backdrop: true
					});
					
					lastValidationCheck = checkKey;
				} else if (data.success && data.valid) {
					// El documento está disponible
					console.log('✅ Documento disponible');
					num_documento.setCustomValidity('');
					lastValidationCheck = checkKey;
				}
			},
			error: function(xhr, status, error) {
				console.warn('⚠️ Error validación documento:', error);
				num_documento.setCustomValidity('');
			}
		});
	}

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
			success: function(data) {
				console.log('✓ Respuesta RENIEC completa:', data);
				
				if (data.success === true) {
					// Asignar nombre
					const nombreCompleto = ((data.nombres || '') + ' ' + (data.apellidos || '')).trim();
					$(nombre).val(nombreCompleto).css('background', '#d4edda');
					
					// 🔥 Asignar dirección si viene en la respuesta
					if (data.direccion && data.direccion.trim() !== '') {
						$('#direccion').val(data.direccion).css('background', '#d4edda');
						console.log('✅ Dirección obtenida:', data.direccion);
						
						setTimeout(function() { 
							$('#direccion').css('background', '');
						}, 3000);
					} else {
						$('#direccion').val('').css('background', '');
						console.warn('⚠️ No se obtuvo dirección desde RENIEC');
					}
					
					$(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por RENIEC').removeClass().addClass('text-success');
					lastQueried = numDoc;
					
					setTimeout(function() { 
						$(nombre).css('background', '');
						$(hint_numero).removeClass().addClass('text-muted');
					}, 3000);
				} else {
					throw new Error(data.message || 'Error al consultar RENIEC');
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ Error RENIEC:', error, xhr.responseText);
				$(nombre).val(prevNombre).css('background', '#f8d7da');
				$('#direccion').val(prevDireccion).css('background', '');
				$(hint_numero).html('<i class="fa fa-times text-danger"></i> ' + (error || 'Error al consultar RENIEC')).removeClass().addClass('text-danger');
				
				setTimeout(function() { 
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
			success: function(data) {
				console.log('✓ Respuesta SUNAT completa:', data);
				
				if (data.success === true) {
					// Asignar razón social
					$(nombre).val(data.razon_social || '').css('background', '#d4edda');
					
					// 🔥 Asignar dirección si viene en la respuesta
					if (data.direccion && data.direccion.trim() !== '') {
						$('#direccion').val(data.direccion).css('background', '#d4edda');
						console.log('✅ Dirección obtenida:', data.direccion);
						
						setTimeout(function() { 
							$('#direccion').css('background', '');
						}, 3000);
					} else {
						$('#direccion').val('').css('background', '');
						console.warn('⚠️ No se obtuvo dirección desde SUNAT');
					}
					
					$(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por SUNAT').removeClass().addClass('text-success');
					lastQueried = numDoc;
					
					setTimeout(function() { 
						$(nombre).css('background', '');
						$(hint_numero).removeClass().addClass('text-muted');
					}, 3000);
				} else {
					throw new Error(data.message || 'Error al consultar SUNAT');
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ Error SUNAT:', error, xhr.responseText);
				$(nombre).val(prevNombre).css('background', '#f8d7da');
				$('#direccion').val(prevDireccion).css('background', '');
				$(hint_numero).html('<i class="fa fa-times text-danger"></i> ' + (error || 'Error al consultar SUNAT')).removeClass().addClass('text-danger');
				
				setTimeout(function() { 
					$(nombre).css('background', '');
					$(hint_numero).text('RUC: 11 dígitos').removeClass().addClass('text-muted');
				}, 4000);
			}
		});
	}

	function debounceConsulta() {
		clearTimeout(timer);
		timer = setTimeout(function() {
			const tipoDoc = $(tipo_documento).val();
			if (tipoDoc === 'DNI') {
				consultarRENIEC();
			} else if (tipoDoc === 'RUC') {
				consultarSUNAT();
			}
		}, 1000);
	}

	// ========== NUEVA FUNCIÓN: DEBOUNCE PARA VALIDACIÓN DE DOCUMENTO ==========
	let validationTimer;
	function debounceValidacionDocumento() {
		clearTimeout(validationTimer);
		validationTimer = setTimeout(validarDocumentoExistente, 1500);
	}

	$(num_documento).on('blur', function() {
		const tipoDoc = $(tipo_documento).val();
		if (tipoDoc === 'DNI') {
			consultarRENIEC();
		} else if (tipoDoc === 'RUC') {
			consultarSUNAT();
		}
		// También validar si existe al perder foco
		validarDocumentoExistente();
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
			success: function(data) {
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
			error: function(xhr, status, error) {
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

	$(pwd).on('input', function() {
		checkStrength($(this).val());
	});

	$(pwd).on('focus', function() {
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
	
	$(toggleBtn).on('click', function() {
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

function limpiar()
{
	$("#nombre").val("");
	$("#tipo_documento").val("");
	$("#num_documento").val("");
	$("#direccion").val("");
	$("#telefono").val("");
	$("#email").val("");
	$("#cargo").val("");
	$("#clave").val("");
	$("#imagenmuestra").attr("src","");
	$("#imagenactual").val("");
	$("#idusuario").val("");
	$("#imagenmuestra").hide();
	$("#pwd-strength").hide();
	$("#email-status").text("");
	$("#email-hint").text("Se usará como usuario de acceso al sistema").removeClass().addClass("text-muted");
	$("#hint_numero").text("Ingresa el número de documento").removeClass().addClass("text-muted");
	$("#hint_tipo").text("Selecciona el tipo de documento").removeClass().addClass("text-muted");
	
	if(document.getElementById('email')) {
		document.getElementById('email').setCustomValidity('');
	}
	$("#nombre").attr('readonly', 'readonly');

	// ✅ Contraseña requerida solo al crear (placeholder informativo)
	$("#clave").prop("required", true);
	$("#clave").attr("placeholder","Mínimo 10 caracteres");

	// Reset de modo permisos (nuevo registro)
	$("#modo_permisos").val('');
	
	// ✅ Recargar roles al limpiar (sin selección)
	cargarRoles();
	setTimeout(actualizarPreviewAvatarPorRol, 200);

	// 🔄 Quitar banner de pendiente si existiera
	$("#pendiente-msg").remove();
}

function mostrarform(flag)
{
	limpiar();
	if (flag)
	{
		$("#listadoregistros").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
	}
	else
	{
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
	}
}

function cancelarform()
{
	limpiar();
	mostrarform(false);
}

function listar()
{
	tabla=$('#tbllistado').dataTable(
	{
		"lengthMenu": [ 5, 10, 25, 75, 100],
		"aProcessing": true,
	    "aServerSide": true,
	    dom: '<Bl<f>rtip>',
	    buttons: [		          
		            'copyHtml5',
		            'excelHtml5',
		            'csvHtml5',
		            'pdf'
		        ],
		"ajax":
				{
					url: '../ajax/usuario.php?op=listar',
					type : "get",
					dataType : "json",						
					error: function(e){
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
		"iDisplayLength": 5,
	    "order": [[ 0, "desc" ]]
	}).DataTable();
}

function guardaryeditar(e)
{
	e.preventDefault();

	// ========== VALIDACIONES OBLIGATORIAS ==========
	
	// 1️⃣ Tipo de Documento
	var tipoDocumento = $("#tipo_documento").val();
	if (!tipoDocumento || tipoDocumento === '') {
		bootbox.alert("⚠️ Debes seleccionar un TIPO DE DOCUMENTO.");
		$("#tipo_documento").focus();
		return;
	}

	// 2️⃣ Número de Documento
	var numDocumento = $("#num_documento").val().trim();
	if (!numDocumento || numDocumento === '') {
		bootbox.alert("⚠️ Debes ingresar el NÚMERO DE DOCUMENTO.");
		$("#num_documento").focus();
		return;
	}

	// Validar formato según tipo de documento
	if (tipoDocumento === 'DNI' && !/^\d{8}$/.test(numDocumento)) {
		bootbox.alert("⚠️ El DNI debe tener exactamente 8 dígitos numéricos.");
		$("#num_documento").focus();
		return;
	}

	if (tipoDocumento === 'RUC' && !/^\d{11}$/.test(numDocumento)) {
		bootbox.alert("⚠️ El RUC debe tener exactamente 11 dígitos numéricos.");
		$("#num_documento").focus();
		return;
	}

	// 3️⃣ Nombre (debe haberse obtenido de RENIEC/SUNAT)
	var nombre = $("#nombre").val().trim();
	if (!nombre || nombre === '') {
		bootbox.alert("⚠️ El NOMBRE es obligatorio. Debe autocompletarse al validar el documento.");
		$("#nombre").focus();
		return;
	}

	// 4️⃣ Email
	var email = $("#email").val().trim();
	if (!email || email === '') {
		bootbox.alert("⚠️ Debes ingresar un EMAIL válido.");
		$("#email").focus();
		return;
	}

	// Validar formato de email
	if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
		bootbox.alert("⚠️ El formato del EMAIL no es válido.");
		$("#email").focus();
		return;
	}

	// 5️⃣ Teléfono (OPCIONAL - no validar)
	// No se valida porque es opcional según tu requerimiento

	// 6️⃣ Rol
	var rolSeleccionado = $("#cargo").val();
	if (!rolSeleccionado || rolSeleccionado === '' || rolSeleccionado === '0') {
		bootbox.alert("⚠️ Debes seleccionar un ROL antes de guardar el usuario.");
		$("#cargo").focus();
		return;
	}

	// 7️⃣ Contraseña (solo obligatoria al crear nuevo usuario)
	var idusuario = $("#idusuario").val();
	var clave = $("#clave").val().trim();
	
	if (!idusuario || idusuario === '') { // Es nuevo usuario
		if (!clave || clave === '') {
			bootbox.alert("⚠️ Debes ingresar una CONTRASEÑA para el nuevo usuario.");
			$("#clave").focus();
			return;
		}

		// Validar requisitos de contraseña
		if (clave.length < 10 || clave.length > 64) {
			bootbox.alert("⚠️ La contraseña debe tener entre 10 y 64 caracteres.");
			$("#clave").focus();
			return;
		}

		if (!/[A-Z]/.test(clave)) {
			bootbox.alert("⚠️ La contraseña debe contener al menos una letra MAYÚSCULA.");
			$("#clave").focus();
			return;
		}

		if (!/[a-z]/.test(clave)) {
			bootbox.alert("⚠️ La contraseña debe contener al menos una letra minúscula.");
			$("#clave").focus();
			return;
		}

		if (!/[0-9]/.test(clave)) {
			bootbox.alert("⚠️ La contraseña debe contener al menos un NÚMERO.");
			$("#clave").focus();
			return;
		}

		if (!/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(clave)) {
			bootbox.alert("⚠️ La contraseña debe contener al menos un carácter ESPECIAL (!@#$%^&*...).");
			$("#clave").focus();
			return;
		}
	}

	// 8️⃣ Permisos
	var modo = ($("#modo_permisos").val() || "").trim();
	var permisosChecked = $("input[name='permiso[]']:checked").length;

	if (modo !== 'rol' && permisosChecked === 0) {
		bootbox.alert("⚠️ Debes seleccionar al menos un PERMISO para el usuario.");
		return;
	}

	// ========== SI TODAS LAS VALIDACIONES PASAN, PROCEDER ==========
	
	$("#btnGuardar").prop("disabled",true);
	var formData = new FormData($("#formulario")[0]);

	// ✅ Lógica clave: si la contraseña está vacía, mantén la actual
	var _claveActual = ($("#clave").val() || "").trim();
	if(!_claveActual){
		formData.delete('clave');
		formData.append('mantener_clave','1');
	}else{
		formData.append('mantener_clave','0');
	}

	var $sel = $("#cargo option:selected");
	var rolId = $sel.val() || "";
	var rolNombre = $.trim($sel.text() || "");

	if (rolNombre) {
		formData.set('cargo', rolNombre);
	}
	formData.set('id_rol', rolId);

	$.ajax({
		url: "../ajax/usuario.php?op=guardaryeditar",
	    type: "POST",
	    data: formData,
	    contentType: false,
	    processData: false,

	    success: function(datos)
	    {                    
	          bootbox.alert(datos);	          
	          mostrarform(false);
	          tabla.ajax.reload();
	    },
	    error: function(xhr, status, error) {
	    	bootbox.alert("Error al guardar: " + error);
	    	$("#btnGuardar").prop("disabled",false);
	    }

	});
	limpiar();
}

function mostrar(idusuario)
{
	$.post("../ajax/usuario.php?op=mostrar",{idusuario : idusuario}, function(data, status)
	{
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

		var idRolDelUsuario = (typeof data.id_rol !== "undefined" && data.id_rol !== null) ? String(data.id_rol) : "";
		var nombreRolDelUsuario = data.cargo || "";

		// Cargar roles y seleccionar el que corresponda
		cargarRoles(idRolDelUsuario, nombreRolDelUsuario);

		$("#cargo").selectpicker('refresh');

		// Contraseña
		$("#clave").val("");
		$("#clave").prop("required", false);
		$("#clave").attr("placeholder","Dejar en blanco para mantener la contraseña");
		$("#toggleClave").text('👁️');
		$("#clave").attr('type','password');
		
		$("#imagenmuestra").show();
		$("#imagenmuestra").attr("src","../files/usuarios/"+data.imagen);
		$("#imagenactual").val(data.imagen);
		$("#idusuario").val(data.idusuario);

		// Si avatar es default, ajusta preview al rol
		var defaults = ['administrador.png','almacenero.png','vendedor.png','usuario.png'];
		if (!data.imagen || defaults.indexOf(String(data.imagen)) >= 0){
			setTimeout(actualizarPreviewAvatarPorRol, 250);
		}

 	});
 	$.post("../ajax/usuario.php?op=permisos&id="+idusuario,function(r){
	    $("#permisos").html(r);
		$("#permisos").off('change.modo').on('change.modo', "input[name='permiso[]']", function(){
			$("#modo_permisos").val('personalizado');
		});
	});
}

function desactivar(idusuario)
{
	bootbox.confirm("¿Está Seguro de desactivar el usuario?", function(result){
		if(result)
        {
        	$.post("../ajax/usuario.php?op=desactivar", {idusuario : idusuario}, function(e){
        		bootbox.alert(e);
	            tabla.ajax.reload();
        	});	
        }
	})
}

function activar(idusuario)
{
	bootbox.confirm("¿Está Seguro de activar el Usuario?", function(result){
		if(result)
        {
        	$.post("../ajax/usuario.php?op=activar", {idusuario : idusuario}, function(e){
        		bootbox.alert(e);
	            tabla.ajax.reload();
        	});	
        }
	})
}

init();