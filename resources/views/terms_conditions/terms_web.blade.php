<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - PadelBB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #1a1a1a;
            line-height: 1.7;
            padding: 20px;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 50px 60px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 30px;
            margin-bottom: 35px;
        }

        .header .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .header .logo span {
            color: #4CAF50;
        }

        .header .badge {
            display: inline-block;
            background: #4CAF50;
            color: white;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-top: 12px;
            color: #1a1a1a;
        }

        .header .last-updated {
            color: #888;
            font-size: 14px;
            margin-top: 6px;
        }

        /* Intro */
        .intro {
            background: #f0f7f0;
            border-left: 4px solid #4CAF50;
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 32px;
            font-size: 15px;
            color: #2d2d2d;
        }

        .intro strong {
            color: #1a1a1a;
        }

        /* Secciones */
        .section {
            margin-bottom: 32px;
        }

        .section-number {
            display: inline-block;
            background: #4CAF50;
            color: white;
            font-weight: 700;
            font-size: 14px;
            width: 32px;
            height: 32px;
            text-align: center;
            line-height: 32px;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 14px;
        }

        .section p {
            margin-bottom: 12px;
            color: #333;
            font-size: 15px;
        }

        .section ul {
            padding-left: 24px;
            margin-bottom: 12px;
        }

        .section ul li {
            margin-bottom: 8px;
            color: #333;
            font-size: 15px;
        }

        .section ul li strong {
            color: #1a1a1a;
        }

        .highlight-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 12px 0;
            border: 1px solid #e9ecef;
        }

        .highlight-box p {
            margin-bottom: 4px;
        }

        .highlight-box p:last-child {
            margin-bottom: 0;
        }

        /* Subsecciones */
        .sub-section {
            margin-top: 16px;
            padding-left: 6px;
        }

        .sub-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .sub-section p {
            margin-bottom: 8px;
        }

        /* Contacto */
        .contact-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: 8px;
            border: 1px solid #e9ecef;
        }

        .contact-card p {
            margin-bottom: 4px;
        }

        .contact-card p:last-child {
            margin-bottom: 0;
        }

        .contact-card a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }

        .contact-card a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 28px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 14px;
            color: #888;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .footer .separator {
            margin: 0 10px;
            color: #ddd;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .container {
                padding: 24px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .section-title {
                font-size: 18px;
            }

            .section-number {
                width: 28px;
                height: 28px;
                line-height: 28px;
                font-size: 12px;
                margin-right: 8px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 40px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="logo">Padel<span>BB</span></div>
        <div class="badge">Política de Privacidad</div>
        <h1>Política de Privacidad</h1>
        <p class="last-updated">📅 Última actualización: <strong>24 de junio de 2026</strong></p>
    </div>

    <!-- INTRO -->
    <div class="intro">
        <p>
            En <strong>PadelBB</strong> (en adelante, "nosotros", "nuestro" o "la App"), valoramos tu privacidad y estamos comprometidos con la protección de tus datos personales. Esta Política de Privacidad explica qué información recopilamos, cómo la usamos, con quién la compartimos y los derechos que tienes sobre tus datos cuando utilizas nuestra aplicación móvil y servicios asociados.
        </p>
        <p style="margin-top: 8px; font-size: 14px; color: #2d2d2d;">
            Al usar <strong>PadelBB</strong>, aceptas las prácticas descritas en esta política. Si no estás de acuerdo con esta política, por favor, no uses nuestra App.
        </p>
    </div>

    <!-- SECCIÓN 1 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">1</span>
            Información del Responsable del Tratamiento
        </div>
        <div class="highlight-box">
            <p><strong>Nombre de la App:</strong> PadelBB</p>
            <p><strong>Desarrollador / Entidad:</strong> TB Group</p>
            <p><strong>Sitio Web:</strong> <a href="https://padelbb.com" target="_blank">https://padelbb.com</a></p>
            <p><strong>Correo de Contacto de Privacidad:</strong> <a href="mailto:rebonlinebb@gmail.com">rebonlinebb@gmail.com</a></p>
        </div>
        <p>Para cualquier consulta sobre esta política o sobre tus datos personales, puedes contactarnos a través del correo electrónico proporcionado.</p>
    </div>

    <!-- SECCIÓN 2 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">2</span>
            Datos que Recopilamos y Finalidad
        </div>
        <p>Para ofrecerte una experiencia completa y conectar a jugadores de pádel, <strong>PadelBB</strong> recopila los siguientes tipos de datos:</p>

        <div class="sub-section">
            <h4>a. Datos que nos proporcionas directamente</h4>
            <ul>
                <li><strong>Información de Registro y Perfil:</strong> Cuando creas una cuenta, recopilamos tu nombre, dirección de correo electrónico, ciudad, posición de juego preferida, sexo, y la imagen de tu foto de perfil. También recopilamos los horarios en los que sueles estar disponible para jugar.</li>
                <li><strong>Interacciones en la App:</strong> Recopilamos la información que generas al crear partidos, unirte a ellos, publicar contenido (como anuncios de venta de paletas o servicios de reparación) y calificar a otros jugadores.</li>
                <li><strong>Mensajes del Chat:</strong> Los mensajes que intercambias con otros usuarios dentro de la App para organizar partidos o concretar ventas son procesados en tiempo real y almacenados de forma efímera. Estos chats se eliminan automáticamente un tiempo después de que el partido haya finalizado o se haya concretado la transacción.</li>
            </ul>
        </div>

        <div class="sub-section">
            <h4>b. Datos recopilados automáticamente o mediante tecnologías similares</h4>
            <ul>
                <li><strong>Datos de Uso y del Dispositivo:</strong> Recopilamos información sobre cómo usas la App, tu dirección IP, el tipo de dispositivo, el sistema operativo, el identificador único del dispositivo, y las páginas o secciones que visitas. Esto nos ayuda a mejorar la funcionalidad de la App y a analizar tendencias de uso.</li>
                <li><strong>Tokens de Notificaciones Push:</strong> Para enviarte notificaciones relevantes (como la creación de un nuevo partido o la recepción de un mensaje), recopilamos los tokens de tus dispositivos. Estos tokens son gestionados a través de nuestro proveedor de notificaciones (Firebase Cloud Messaging).</li>
            </ul>
        </div>

        <div class="sub-section">
            <h4>c. Datos de Localización</h4>
            <ul>
                <li><strong>Ubicación (Opcional):</strong> Con tu consentimiento, podemos recopilar tu ubicación precisa o aproximada para mostrarte complejos y partidos cerca de ti. Puedes desactivar esta función en cualquier momento desde la configuración de tu dispositivo.</li>
            </ul>
        </div>
    </div>

    <!-- SECCIÓN 3 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">3</span>
            Uso de tus Datos
        </div>
        <p>Utilizamos los datos recopilados para los siguientes fines, siempre con una base legal legítima:</p>
        <ul>
            <li><strong>Prestación del Servicio:</strong> Para crear tu cuenta, gestionar tu perfil, mostrar partidos y jugadores disponibles, permitirte unirte a partidos, publicar contenido (ventas, torneos), y facilitar la comunicación a través del chat.</li>
            <li><strong>Notificaciones:</strong> Para enviarte avisos sobre la creación de nuevos partidos, la confirmación de tu participación, la recepción de nuevos mensajes en el chat y otras actualizaciones relevantes de la App.</li>
            <li><strong>Mejora y Seguridad:</strong> Para analizar el uso de la App, solucionar problemas técnicos, prevenir fraudes y mejorar continuamente la experiencia del usuario.</li>
            <li><strong>Publicidad:</strong> Para mostrar anuncios (banners e intersticiales) dentro de la App, con el fin de mantener el servicio gratuito. La publicidad se gestiona a través de Google AdMob. El procesamiento de datos para publicidad se basa en tu consentimiento cuando lo otorgas.</li>
        </ul>
    </div>

    <!-- SECCIÓN 4 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">4</span>
            Compartición de Datos con Terceros
        </div>
        <p>No vendemos ni alquilamos tus datos personales a terceros. Sin embargo, para operar la App, compartimos información con los siguientes tipos de proveedores de servicios:</p>
        <ul>
            <li><strong>Proveedores de Infraestructura y Backend:</strong> Utilizamos proveedores de servicios en la nube y bases de datos para alojar y gestionar la información de la App. Estos proveedores actúan como procesadores de datos bajo nuestra instrucción.</li>
            <li><strong>Proveedores de Chat y Notificaciones:</strong> El chat en tiempo real y las notificaciones push se gestionan a través de proveedores externos como Firebase.</li>
            <li><strong>Proveedores de Publicidad:</strong> Para mostrar anuncios, utilizamos Google AdMob. Google puede recopilar ciertos datos a través de su SDK para personalizar los anuncios que ves.</li>
            <li><strong>Cumplimiento Legal:</strong> Podremos compartir tu información si la ley nos lo exige o si es necesario para proteger nuestros derechos, la seguridad de los usuarios o para cumplir con un proceso judicial.</li>
        </ul>
    </div>

    <!-- SECCIÓN 5 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">5</span>
            Seguridad de los Datos
        </div>
        <p>Implementamos medidas de seguridad técnicas y organizativas apropiadas para proteger tus datos personales contra el acceso no autorizado, la alteración, divulgación o destrucción. Esto incluye el cifrado de datos en tránsito y en reposo, y controles de acceso rigurosos a nuestros sistemas.</p>
    </div>

    <!-- SECCIÓN 6 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">6</span>
            Retención y Eliminación de Datos
        </div>
        <ul>
            <li><strong>Período de Retención:</strong> Conservamos tus datos personales mientras mantengas una cuenta activa en <strong>PadelBB</strong>. Si eliminas tu cuenta, eliminaremos o anonimizaremos tus datos de acuerdo con nuestros plazos de retención.</li>
            <li><strong>Datos de Chat:</strong> Los mensajes de los chats se conservan mientras el partido o la conversación esté activa. Una vez finalizado el partido o pasadas 24 horas, se eliminan automáticamente.</li>
            <li><strong>Solicitud de Eliminación:</strong> Puedes solicitar la eliminación de tu cuenta y de todos tus datos personales desde la sección de configuración de la App o contactándonos a nuestro correo de privacidad.</li>
        </ul>
    </div>

    <!-- SECCIÓN 7 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">7</span>
            Datos de Menores de Edad
        </div>
        <p><strong>PadelBB</strong> no está dirigida a niños menores de 13 años (o la edad mínima equivalente en tu jurisdicción) y no recopilamos conscientemente información personal de ellos. Si descubrimos que hemos recopilado información de un menor sin la verificación de consentimiento parental, eliminaremos dicha información de inmediato.</p>
    </div>

    <!-- SECCIÓN 8 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">8</span>
            Tus Derechos (RGPD / CCPA)
        </div>
        <p>Dependiendo de tu ubicación, puedes tener los siguientes derechos sobre tus datos personales:</p>
        <ul>
            <li><strong>Acceso:</strong> Solicitar una copia de los datos personales que tenemos sobre ti.</li>
            <li><strong>Rectificación:</strong> Corregir datos inexactos o incompletos.</li>
            <li><strong>Supresión:</strong> Solicitar la eliminación de tus datos personales ("derecho al olvido").</li>
            <li><strong>Oposición:</strong> Oponerte a cómo procesamos tus datos, especialmente para fines de marketing directo.</li>
            <li><strong>Portabilidad:</strong> Recibir tus datos en un formato estructurado y legible por máquina.</li>
        </ul>
        <p>Para ejercer cualquiera de estos derechos, contáctanos en <a href="mailto:rebonlinebb@gmail.com">rebonlinebb@gmail.com</a>. Atenderemos tu solicitud en el plazo máximo de un mes.</p>
    </div>

    <!-- SECCIÓN 9 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">9</span>
            Cambios en esta Política de Privacidad
        </div>
        <p>Podemos actualizar esta Política de Privacidad ocasionalmente para reflejar cambios en nuestras prácticas o por otros motivos operativos, legales o regulatorios. Te notificaremos cualquier cambio significativo publicando la nueva política en esta página y/o a través de un aviso en la App. Te recomendamos revisar esta página periódicamente.</p>
    </div>

    <!-- SECCIÓN 10 -->
    <div class="section">
        <div class="section-title">
            <span class="section-number">10</span>
            Contacto
        </div>
        <p>Si tienes preguntas, dudas o comentarios sobre esta Política de Privacidad o nuestras prácticas de datos, por favor, contáctanos:</p>
        <div class="contact-card">
            <p><strong>Correo Electrónico:</strong> <a href="mailto:rebonlinebb@gmail.com">rebonlinebb@gmail.com</a></p>
            <p><strong>Sitio Web:</strong> <a href="https://padelbb.com" target="_blank">https://padelbb.com</a></p>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>
            &copy; 2026 <strong>PadelBB - TB Group</strong> — Todos los derechos reservados.
            <span class="separator">|</span>
            <a href="https://padelbb.com" target="_blank">padelbb.com</a>
            <span class="separator">|</span>
            <a href="mailto:rebonlinebb@gmail.com">rebonlinebb@gmail.com</a>
        </p>
    </div>

</div>

</body>
</html>