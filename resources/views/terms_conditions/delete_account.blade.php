<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Cuenta - Padel Match</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .header .logo span {
            color: #4CAF50;
        }

        .header h1 {
            font-size: 24px;
            margin-top: 8px;
            color: #1a1a1a;
        }

        .header p {
            color: #666;
            font-size: 14px;
            margin-top: 6px;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .warning-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }

        .warning-box strong {
            color: #664d03;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 6px;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group .hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 18px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            flex-shrink: 0;
            accent-color: #dc3545;
        }

        .checkbox-group label {
            font-size: 14px;
            color: #555;
            cursor: pointer;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .btn-danger:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .success-message {
            display: none;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .success-message .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .success-message h3 {
            color: #155724;
            margin-bottom: 8px;
        }

        .success-message p {
            color: #155724;
            font-size: 14px;
        }

        .error-message {
            display: none;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
            color: #721c24;
            font-size: 14px;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 13px;
            color: #888;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s ease-in-out infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .container {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Formulario -->
    <div id="formContainer">
        <div class="header">
            <div class="logo">Padel<span>BB</span></div>
            <h1>Eliminar Cuenta</h1>
            <p>Solicita la eliminación de tu cuenta y datos personales</p>
        </div>

        <div class="warning-box">
            <p>
                <strong>⚠️ Esta acción es irreversible.</strong><br>
                Todos tus datos (perfil, partidos, historial, etc.) serán eliminados permanentemente.
            </p>
        </div>

        <div id="errorMessage" class="error-message"></div>

        <form id="deleteForm">
            <div class="form-group">
                <label for="email">Correo Electrónico <span class="required">*</span></label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="tu@email.com" 
                    required
                    autocomplete="email"
                >
                <div class="hint">El correo asociado a tu cuenta de PadelBB</div>
            </div>

            <div class="form-group">
                <label for="reason">Motivo de la eliminación</label>
                <select id="reason" name="reason">
                    <option value="">Selecciona un motivo...</option>
                    <option value="no_uso">Ya no uso la app</option>
                    <option value="privacidad">Preocupaciones de privacidad</option>
                    <option value="spam">Demasiadas notificaciones</option>
                    <option value="otra_app">Encontré otra alternativa</option>
                    <option value="otro">Otro motivo</option>
                </select>
            </div>

            <div class="form-group">
                <label for="comments">Comentarios adicionales (opcional)</label>
                <textarea 
                    id="comments" 
                    name="comments" 
                    placeholder="¿Algo que quieras contarnos?"
                ></textarea>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="confirm" name="confirm" required>
                <label for="confirm">
                    <strong>Confirmo</strong> que quiero eliminar mi cuenta de PadelBB y entiendo que esta acción es <strong>permanente e irreversible</strong>.
                </label>
            </div>

            <button type="submit" class="btn btn-danger" id="submitBtn">
                <span id="btnText">Solicitar Eliminación</span>
                <div class="spinner" id="spinner"></div>
            </button>

            <a href="https://padelbb.com" class="btn btn-secondary" style="display:block; text-decoration:none;">
                Cancelar y volver
            </a>
        </form>
    </div>

    <!-- Mensaje de Éxito -->
    <div id="successMessage" class="success-message">
        <div class="icon">✅</div>
        <h3>¡Solicitud Enviada!</h3>
        <p>
            Hemos recibido tu solicitud de eliminación de cuenta.<br>
            Procesaremos tu petición y te confirmaremos por email en un plazo máximo de <strong>48 horas</strong>.
        </p>
        <br>
        <a href="https://padelbb.com" class="btn btn-secondary" style="display:inline-block; text-decoration:none; padding:12px 40px; width:auto;">
            Volver al inicio
        </a>
    </div>

    <div class="footer">
        <p>
            <a href="https://padelbb.com/privacy">Política de Privacidad</a>
            &nbsp;·&nbsp;
            <a href="https://padelbb.com/terms">Términos y Condiciones</a>
        </p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('deleteForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('spinner');
        const errorMessage = document.getElementById('errorMessage');
        const formContainer = document.getElementById('formContainer');
        const successContainer = document.getElementById('successMessage');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Limpiar errores
            errorMessage.style.display = 'none';
            errorMessage.textContent = '';

            // Validar checkbox
            const confirm = document.getElementById('confirm');
            if (!confirm.checked) {
                showError('Debes confirmar que quieres eliminar tu cuenta.');
                return;
            }

            // Validar email
            const email = document.getElementById('email').value.trim();
            if (!email) {
                showError('Por favor, ingresa tu correo electrónico.');
                return;
            }

            if (!isValidEmail(email)) {
                showError('Por favor, ingresa un correo electrónico válido.');
                return;
            }

            // Deshabilitar botón y mostrar spinner
            submitBtn.disabled = true;
            btnText.textContent = 'Enviando...';
            spinner.style.display = 'block';

            // Datos del formulario
            const formData = {
                email: email,
                reason: document.getElementById('reason').value,
                comments: document.getElementById('comments').value,
                timestamp: new Date().toISOString()
            };

            try {
                // Enviar datos a tu backend
                const response = await fetch('/api/delete-account-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    // Mostrar mensaje de éxito
                    formContainer.style.display = 'none';
                    successContainer.style.display = 'block';
                } else {
                    showError(result.message || 'Error al enviar la solicitud. Por favor, intenta de nuevo.');
                    resetButton();
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión. Por favor, verifica tu internet e intenta de nuevo.');
                resetButton();
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            // Scroll al error
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function resetButton() {
            submitBtn.disabled = false;
            btnText.textContent = 'Solicitar Eliminación';
            spinner.style.display = 'none';
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    });
</script>

</body>
</html>