<style type="text/css">    

@font-face {
  font-family: "Blender Pro";
  src: url("<?php echo e(asset('bahiapadel/fonts/BlenderPro-BoldItalic.ttf')); ?>") format("truetype");
  font-weight: normal;
  font-style: normal;
}
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

/* Texto oscuro solo en admin en tema claro; con body.dark-mode debe prevalecer dark-mode.css */
body.body_admin:not(.dark-mode) {
    color: rgb(0, 0, 0);
}

body {
    color: rgb(255, 255, 255);
    font-family: "Blender Pro", sans-serif;
    font-weight: 400;
    background-color: rgb(26, 26, 26);
    overflow-x: hidden;
}

.wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header_ic {
    width: auto;
    max-width: 180px;
    height: auto;
    max-height: 80px;
    margin-left: 200px;
    object-fit: contain;
}

.header_btn {
  background: transparent !important;
  color: #333 !important;
  border: none !important;
  transition: background 0.2s, color 0.2s;
  border-radius: 35px;
  font-size: 20px;  
}

.header_btn:hover, .header_btn:focus {
  background: #ff0264 !important;
  color: #fff !important;
}

main {
  flex: 1;
}

/* Fuente para títulos de las secciones (Home, Torneos, Ranking, etc.) */
main h1,
main .h1,
main h2,
main .h2,
main h3,
main .h3,
.page-content-home h1,
.page-content-home h2,
.page-content-home h3 {
  font-family: "Poppins", sans-serif;
  font-weight: 600;
  letter-spacing: 0.02em;
}

/* Título principal de la página (aprox. el doble de tamaño) */
main .container-fluid > section > h1,
main .container-fluid > section > .h3,
.page-content-home h1,
.page-content-home .h3 {
  font-size: 2.5rem;
}

/* Opcional: para que el footer no tenga margen arriba */
.sticky-footer {
  margin-top: 0;
}
.sticky-footer .copyright {
  padding: 1.25rem 1rem;
}

/* Sponsors en el footer del home (fijos, sin scroll ni animación) */
.footer-sponsors {
  background: rgba(5, 5, 5, 0.9);
  border-top: 1px solid rgba(148, 163, 184, 0.2);
  padding: 1rem 1.5rem;
}
.footer-sponsors-track {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.footer-sponsor-card {
  height: 48px;
  min-width: 80px;
  max-width: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  flex-shrink: 0;
}
.footer-sponsor-card img {
  max-height: 100%;
  max-width: 100%;
  object-fit: contain;
  filter: saturate(1.05);
}
.footer-sponsor-label {
  font-size: 0.75rem;
  color: rgba(226, 232, 240, 0.85);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Video a ancho completo en la Home (solo video, sin comentarios) */
.home-video-fullwidth {
  width: 100vw;
  max-width: 100vw;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  overflow: hidden;
  margin-top: -1rem;
  margin-bottom: 1rem;
}

.home-video-fullwidth .home-video {
  width: 100%;
  display: block;
  vertical-align: top;
  max-height: 70vh;
  object-fit: cover;
}

/* Imagen de cabecera (Reglamento y resto de secciones: Home, Torneos, Ranking, Calendario) */
.page-header-img,
.reglamento-header-img {
  width: 100vw;
  max-width: 100vw;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  margin-top: -1rem;
  overflow: hidden;
}

.page-header-img-inner,
.reglamento-header-img-inner {
  position: relative;
  display: block;
}

.page-header-img img,
.reglamento-header-img img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
  max-height: 40vh;
  opacity: 0.88;
  transition: opacity 0.4s ease;
}

.page-header-img:hover img,
.reglamento-header-img:hover img {
  opacity: 1;
}

.page-header-img-overlay,
.reglamento-header-img-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(26, 26, 26, 0.25) 0%, rgba(26, 26, 26, 0.5) 100%);
  pointer-events: none;
}

.page-header-title,
.reglamento-header-title {
  position: absolute;
  bottom: 1rem;
  left: 1.5rem;
  margin: 0;
  font-family: "Poppins", sans-serif;
  font-weight: 700;
  font-size: 2.5rem;
  color: #fff;
  -webkit-text-stroke: 2px #000;
  paint-order: stroke fill;
  text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000, 0 0 4px rgba(0,0,0,0.9);
}

.reglamento-content {
  max-width: 800px;
}

.reglamento-block {
  margin-bottom: 2rem;
}

.reglamento-section-title {
  font-family: "Poppins", sans-serif;
  font-weight: 600;
  font-size: 1.35rem;
  color: #fff;
  margin-bottom: 0.75rem;
  padding-bottom: 0.35rem;
  border-bottom: 2px solid rgba(255, 0, 102, 0.5);
}

.reglamento-list {
  list-style: none;
  padding-left: 0;
  margin: 0;
}

.reglamento-list li {
  position: relative;
  padding-left: 1.25rem;
  margin-bottom: 0.5rem;
  color: #e0e0e0;
}

.reglamento-list li::before {
  content: "–";
  position: absolute;
  left: 0;
  color: #ff0264;
  font-weight: bold;
}

.reglamento-intro {
  color: #b0b0b0;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.custom-header {
  background: transparent !important;
  box-shadow: none;
  border: none;
}

.menu-blanco {
  background: #fff;
  border-radius: 35px;
  border: 2px solid #ccc;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  padding: 0.3rem 1rem;
  display: flex;
  flex-wrap: wrap;
  margin-right:200px;
}


.torneo-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    margin: 15px;
    min-width: 300px;    
    max-width: 1100px; 
    color: #222;
}
.torneo-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
    border: 1px solid #ff0264;
    cursor: pointer;
}
.torneo-card .categoria {
    font-weight: bold;
    color: #ff0264;
}
.torneo-card .fechas {
    font-size: 0.95rem;
    color: #555;
}

/* ===== Torneos (mobile-first) ===== */
.torneos-filtros-inner {
  max-width: 480px;
  margin: 0 auto;
}

.torneos-label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #e0e0e0;
  margin-top: 1rem;
  margin-bottom: 0.4rem;
}

.torneos-select {
  display: block;
  width: 100%;
  min-height: 48px;
  padding: 0.6rem 1rem;
  font-size: 1rem;
  color: #fff;
  background-color: #2d2d2d;
  border: 1px solid #4d4d4d;
  border-radius: 12px;
  -webkit-appearance: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23fff' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  padding-right: 2.5rem;
}

.torneos-meses {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.torneos-mes-chip {
  min-width: 48px;
  min-height: 44px;
  padding: 0.5rem 0.6rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #e0e0e0;
  background: #2d2d2d;
  border: 2px solid #4d4d4d;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.torneos-mes-chip:hover,
.torneos-mes-chip.active {
  background: #ff0264;
  border-color: #ff0264;
  color: #fff;
}

.torneos-meses-leyenda {
  font-size: 0.8rem;
}

.torneos-lista {
  display: flex;
  flex-direction: column;
  max-width: 520px;
  margin: 0 auto;
}

.torneo-card-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1rem;
  margin-bottom: 1.5rem;
  background: rgba(45, 45, 45, 0.9);
  border: 1px solid #4d4d4d;
  border-radius: 14px;
  color: #e0e0e0;
}
.torneo-card-item:last-child {
  margin-bottom: 0;
}

.torneo-card-item-content {
  flex: 1;
  min-width: 0;
}

.torneo-card-item-tipo {
  font-size: 0.8rem;
  font-weight: 600;
  color: #ff0264;
  margin-bottom: 0.35rem;
}

.torneo-card-item-nombre {
  font-size: 1.15rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.35rem;
}

.torneo-card-item-fechas {
  font-size: 0.9rem;
  color: #b0b0b0;
}

.torneo-card-item-ganador {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
}
.torneo-ganador-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.torneo-ganador-fotos {
  display: flex;
  align-items: center;
}
.torneo-ganador-foto {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #ff0264;
}
.torneo-ganador-foto-2 {
  margin-left: -14px;
}

.torneo-card-item-clickable {
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  text-decoration: none;
  color: inherit;
}
.torneo-card-item-clickable:hover {
  border-color: #ff0264;
  background: rgba(55, 55, 55, 0.95);
  color: inherit;
}

/* Página detalle torneo: Zonas / Cruces */
.torneo-detalle-acciones {
  max-width: 520px;
  margin: 0 auto;
}
.torneo-detalle-inner {
  padding: 0 0.5rem;
}
.torneo-detalle-leyenda {
  font-size: 0.95rem;
}
.torneo-detalle-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.torneo-detalle-btn {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
}
.torneo-detalle-btn-zonas {
  background: #4d4d4d;
  color: #fff;
}
.torneo-detalle-btn-zonas:hover {
  background: #5d5d5d;
  color: #fff;
}
.torneo-detalle-btn-cruces {
  background: #ff0264;
  color: #fff;
}
.torneo-detalle-btn-cruces:hover {
  background: #e00258;
  color: #fff;
}
.torneo-detalle-volver {
  text-decoration: none;
}
.torneo-detalle-volver:hover {
  color: #ff0264 !important;
}

.torneo-detalle-btn-active {
  box-shadow: 0 0 0 2px rgba(255, 2, 100, 0.4);
}

.torneo-detalle-section {
  margin-top: 0.75rem;
}

.torneo-zona-bloque {
  margin-bottom: 1.5rem;
  padding: 0.75rem 0.5rem;
  border-top: 1px solid rgba(255,255,255,0.12);
}
.torneo-zona-titulo {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.5rem;
}
.torneo-zona-subtitulo {
  font-size: 0.9rem;
  font-weight: 600;
  color: #e0e0e0;
  margin-bottom: 0.35rem;
}
.torneo-zona-partido-card {
  background: rgba(45, 45, 45, 0.9);
  border: 1px solid #4d4d4d;
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  margin-bottom: 0.5rem;
}
.torneo-zona-partido-header {
  font-size: 0.8rem;
  font-weight: 600;
  color: #ff0264;
  margin-bottom: 0.35rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.torneo-zona-partido-icon {
  font-size: 0.9rem;
}
.torneo-zona-partido-body {
  font-size: 0.85rem;
  color: #e0e0e0;
}
.torneo-zona-partido-linea {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}
.torneo-zona-pareja-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex: 1;
}
.torneo-zona-jugadores {
  display: flex;
  align-items: center;
  gap: 0.15rem;
}
.torneo-zona-player-img {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #4d4d4d;
}
.torneo-zona-pareja-label {
  flex: 1;
}
.torneo-zona-resultado {
  font-weight: 600;
}
.torneo-zona-clasificacion {
  font-size: 0.85rem;
  color: #e0e0e0;
  padding-left: 1.2rem;
  margin-bottom: 0;
}

.torneo-card-item-categoria {
  font-size: 0.85rem;
  color: #b0b0b0;
  margin-top: 0.25rem;
}

@media (min-width: 768px) {
  .torneos-filtros-inner { max-width: 560px; }
  .torneos-lista { max-width: 640px; }
  .torneo-card-item { padding: 1.35rem 1.25rem; }
}

@media (max-width: 767.98px) {
    .menu-blanco {
    max-width: 80vw;
    margin-left: auto;
    margin-right: 10px;
  }
  .header_ic {
    width: auto;
    max-width: 120px;
    height: auto;
    max-height: 60px;
    margin-left: 20px;
    object-fit: contain;
}
}

/* Hamburguesa blanca */
.navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
}

</style>