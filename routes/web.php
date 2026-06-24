<?php

use App\Http\Controllers\Admin\CajaAdminController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\StockAdminController;
use App\Http\Controllers\Admin\TurnosFijosAdminController;
use App\Http\Controllers\CartaController;

Route::get('/index_new', function () {	
	return View('home/index');   
});

Route::get('/clear-cache', function() {
   $exitCode = Artisan::call('cache:clear');
   // return what you want
});

Route::get('/config-cache', function() {
   $exitCode = Artisan::call('config:cache');
   // return what you want
});
Route::get('/config-clear', function() {
   $exitCode = Artisan::call('config:clear');
   // return what you want
});
Route::get('/migrate', function() {
   $exitCode = Artisan::call('migrate');
   // return what you want
});

Route::get('/storage-link', function() {
   Artisan::call('storage:link');
   return response()->json(['ok' => true, 'message' => 'Enlace storage creado (public/storage → storage/app/public). Las fotos de jugadores ya se servirán correctamente.']);
})->name('storage.link');

Route::get("/tv_torneo_americano", "HomeController@tvTorneoAmericano")->name("tvtorneoamericano");
Route::get("/tv_torneo_americano_cruces", "HomeController@tvTorneoAmericanoCruces")->name("tvtorneoamericanocruces");
Route::get("/tv_torneo_americano_sorteo", "HomeController@tvTorneoAmericanoSorteo")->name("tvtorneoamericanosorteo");
Route::post("/tv_torneo_americano_actualizar", "HomeController@tvTorneoAmericanoActualizar")->name("tvtorneoamericanoactualizar");
Route::post("/tv_torneo_americano_cruces_actualizar", "HomeController@tvTorneoAmericanoCrucesActualizar")->name("tvtorneoamericanocrucesactualizar");
Route::post("/tv_torneo_americano_sorteo_actualizar", "HomeController@tvTorneoAmericanoSorteoActualizar")->name("tvtorneoamericanosorteoactualizar");
Route::get("/tv_torneo_version", "HomeController@tvTorneoVersion")->name("tvtorneoversion");
Route::get("/tv_torneos_rotacion", "HomeController@tvTorneosRotacion")->name("tvtorneosrotacion");
Route::get("/tv_torneos_puntuables_zonas", "HomeController@tvTorneosPuntuablesZonas")->name("tvtorneospuntuableszonas");
Route::get("/tv_torneos_versiones", "HomeController@tvTorneosVersiones")->name("tvtorneosversiones");
Route::get("/tv_torneos_hoy", "HomeController@tvTorneosHoy")->name("tvtorneoshoy");
Route::get("/tv_torneos_diagnostico", "HomeController@tvTorneosDiagnostico")->name("tvtorneosdiagnostico");

// Rutas de configuración TV
Route::get("/admin_tv_control", "TvConfiguracionController@panelControl")->name("admintvcontrol");
Route::post("/tv_config_guardar", "TvConfiguracionController@guardarConfiguracion")->name("tvconfig.guardar");
Route::get("/tv_config_obtener", "TvConfiguracionController@obtenerConfiguracion")->name("tvconfig.obtener");
Route::get("/tv_display", "TvConfiguracionController@display")->name("tvdisplay");

// ################################# estas rutas debo tocar para volver al mantenimiento ################
/*Route::get('/home_test', function () {	
	return View('home.index');    
}); */

Route::get('/', 'HomeFreeController@bahiaPadelHome')->name('index');	

Route::get('/bahia_padel', 'HomeFreeController@bahiaPadelHome')->name('bahiapadel');

Route::get('/bahia_padel_admin', 'HomeFreeController@bahiaPadelAdmin')->name('bahiapadeladmin');

Route::get('/torneos', 'HomeFreeController@torneos')->name('home.torneos');
Route::get('/torneos/listado', 'HomeFreeController@getTorneosPublic')->name('home.torneos.listado');
Route::get('/torneos/{id}', 'HomeFreeController@torneoDetalle')->name('home.torneo.detalle');
Route::get('/torneos/{id}/zonas', 'HomeFreeController@torneoZonasPublic')->name('home.torneo.zonas');
Route::get('/torneos/{id}/cruces', 'HomeFreeController@torneoCrucesPublic')->name('home.torneo.cruces');
Route::get('/ranking', 'HomeFreeController@ranking')->name('home.ranking');
Route::get('/calendario', 'HomeFreeController@calendario')->name('home.calendario');
Route::get('/calendario/{calendario}/inscribirme', 'HomeFreeController@calendarioInscribir')->name('home.calendario.inscribir');
Route::post('/calendario/{calendario}/inscribirme', 'HomeFreeController@calendarioInscribirGuardar')->name('home.calendario.inscribir.guardar');
Route::get('/calendario/buscar-jugadores', 'HomeFreeController@calendarioBuscarJugadores')->name('home.calendario.buscar.jugadores');
Route::post('/calendario/crear-jugador', 'HomeFreeController@calendarioCrearJugador')->name('home.calendario.crear.jugador');
Route::get('/reglamento', 'HomeFreeController@reglamento')->name('home.reglamento');	

Route::get('/index2', 'Auth\LoginController@showLoginForm')->name('index2');	

// #################################################################################################

Route::group(['middleware' => ['auth', 'usuarioAdmin']], function () {
	
	Route::get('home_admin_2','UserController@admin');			
	Route::get('nuevo_usuario','UserController@nuevoUsuario');		

	Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
	
	Route::post('/registrar', 'Auth\RegisterController@registrar')->name('registrar');	
		
});
Route::group(['middleware' => ['auth', 'usuarioPadel']], function () {
	Route::get('bp_admin','HomeController@adminHomeBp')->name('bp_admin');	

	Route::get('home_admin','HomeController@adminHome')->name('home_admin');
});

Route::group(['middleware' => ['auth', 'usuarioAdminPadel']], function () {

	Route::get('bp_admin','HomeController@adminHomeBp')->name('bp_admin');	

	Route::get('home_admin','HomeController@adminHome')->name('home_admin');
	Route::resource('admin/menu', MenuItemController::class)->names('admin.menu')->parameters(['menu' => 'menuItem']);
	Route::get('admin_jugadores','HomeController@adminJugadores')->name('adminjugadores');
	Route::get('admin_stock', [StockAdminController::class, 'index'])->name('adminstock');
	Route::get('admin_stock/movimientos-data', [StockAdminController::class, 'movimientosData'])->name('adminstock.movimientos.data');
	Route::post('admin_stock/categoria', [StockAdminController::class, 'storeCategoria'])->name('adminstock.categoria.store');
	Route::put('admin_stock/categoria/{categoria}', [StockAdminController::class, 'updateCategoria'])->name('adminstock.categoria.update');
	Route::post('admin_stock/producto', [StockAdminController::class, 'storeProducto'])->name('adminstock.producto.store');
	Route::post('admin_stock/actualizar', [StockAdminController::class, 'storeActualizacionMasiva'])->name('adminstock.actualizar.store');
	Route::post('admin_stock/productos-tabla', [StockAdminController::class, 'storeProductosTabla'])->name('adminstock.productos.tabla.store');

	Route::get('admin_caja', [CajaAdminController::class, 'index'])->name('admincaja');
	Route::post('admin_caja/apertura-json', [CajaAdminController::class, 'storeApertura'])->name('admincaja.apertura.store');
	Route::get('admin_caja/resumen', [CajaAdminController::class, 'resumenJson'])->name('admincaja.resumen');
	Route::post('admin_caja/venta', [CajaAdminController::class, 'storeVenta'])->name('admincaja.venta.store');
	Route::post('admin_caja/venta/borrador', [CajaAdminController::class, 'storeBorrador'])->name('admincaja.venta.borrador');
	Route::post('admin_caja/venta/{venta}/continuar', [CajaAdminController::class, 'continuarVenta'])->name('admincaja.venta.continuar');
	Route::get('admin_caja/jugadores', [CajaAdminController::class, 'jugadoresCajaJson'])->name('admincaja.jugadores');
	Route::patch('admin_caja/venta/{venta}/participante/{participante}', [CajaAdminController::class, 'updateParticipante'])->name('admincaja.venta.participante.update');
	Route::post('admin_caja/venta/{venta}/participante/{participante}/pago', [CajaAdminController::class, 'pagoParticipante'])->name('admincaja.venta.participante.pago');
	Route::post('admin_caja/venta/{venta}/participante/{participante}/pago-mixto', [CajaAdminController::class, 'pagoMixtoParticipante'])->name('admincaja.venta.participante.pago-mixto');
	Route::post('admin_caja/apertura', [CajaAdminController::class, 'abrirCaja'])->name('admincaja.apertura');
	Route::post('admin_caja/cierre', [CajaAdminController::class, 'cerrarCaja'])->name('admincaja.cierre');
	Route::post('admin_caja/venta/{venta}/linea/{detalle}/pago', [CajaAdminController::class, 'pagoLinea'])->name('admincaja.venta.linea.pago');
	Route::post('admin_caja/venta/{venta}/participante', [CajaAdminController::class, 'storeParticipanteTorneo'])->name('admincaja.venta.participante.store');
	Route::post('admin_caja/venta/{venta}/inscripcion-todos', [CajaAdminController::class, 'inscripcionTodosTorneo'])->name('admincaja.venta.inscripcion.todos');
	Route::post('admin_caja/venta/{venta}/linea', [CajaAdminController::class, 'storeLinea'])->name('admincaja.venta.linea');
	Route::post('admin_caja/venta/{venta}/linea/{detalle}/dividir', [CajaAdminController::class, 'dividirLinea'])->name('admincaja.venta.linea.dividir');
	Route::post('admin_caja/venta/{venta}/linea/{detalle}/pago', [CajaAdminController::class, 'pagoLinea'])->name('admincaja.venta.linea.pago');
	Route::delete('admin_caja/venta/{venta}/linea/{detalle}', [CajaAdminController::class, 'destroyLinea'])->name('admincaja.venta.linea.destroy');
	Route::delete('admin_caja/venta/{venta}', [CajaAdminController::class, 'destroyVenta'])->name('admincaja.venta.destroy');
	Route::patch('admin_caja/venta/{venta}', [CajaAdminController::class, 'updateBorrador'])->name('admincaja.venta.update');
	Route::get('admin_caja/venta/{venta}/ticket-modal', [CajaAdminController::class, 'ventaTicketModal'])->name('admincaja.venta.ticket-modal');
	Route::get('admin_caja/venta/{venta}', [CajaAdminController::class, 'showVenta'])->name('admincaja.venta.show');
	Route::get('admin_caja/torneo/{venta}', [CajaAdminController::class, 'showTorneo'])->name('admincaja.torneo.show');
	Route::post('admin_caja/venta/{venta}/pago', [CajaAdminController::class, 'registrarPago'])->name('admincaja.venta.pago');

	Route::get('admin_turnos_fijos', [TurnosFijosAdminController::class, 'index'])->name('adminturnosfijos');
	Route::post('admin_turnos_fijos', [TurnosFijosAdminController::class, 'store'])->name('adminturnosfijos.store');
	Route::put('admin_turnos_fijos/{turno}', [TurnosFijosAdminController::class, 'update'])->name('adminturnosfijos.update');
	Route::delete('admin_turnos_fijos/{turno}', [TurnosFijosAdminController::class, 'destroy'])->name('adminturnosfijos.destroy');

	Route::get('admin_torneos','HomeController@adminTorneos')->name('admintorneos');
	Route::get('admin_cargar_resultados','HomeController@adminCargarResultados')->name('admincargarresultados');
	Route::get('admin_fotos','HomeController@adminFotos')->name('adminfotos');
	Route::get('admin_ranking','HomeController@adminRanking')->name('adminranking');
	Route::post('admin_ranking_mover','HomeController@adminRankingMoverCategoria')->name('adminrankingmover');
	Route::post('guardar_referencias_puntuacion','HomeController@guardarReferenciasPuntuacion')->name('guardarreferenciaspuntuacion');
	// Entradas manuales de ranking
	Route::post('admin_ranking_entrada_crear','HomeController@crearEntradaRanking')->name('adminrankingentradacrear');
	Route::post('admin_ranking_entrada_eliminar','HomeController@eliminarEntradaRanking')->name('adminrankingentradaeliminar');
	Route::get('admin_ranking_entrada_jugadores','HomeController@obtenerJugadoresEntrada')->name('adminrankingentradajugadores');
	Route::post('admin_ranking_entrada_jugadores_guardar','HomeController@guardarJugadoresEntrada')->name('adminrankingentradajugadoresguardar');
	Route::get('admin_ranking_jugadores_lista','HomeController@getJugadoresParaRanking')->name('adminrankingjugadoreslista');
	Route::post('generar_datos_prueba_torneo','HomeController@generarDatosPruebaTorneo')->name('generardatospruebatorneo');
	Route::get('admin_calendario','HomeController@adminCalendario')->name('admincalendario');
	Route::get('admin_calendario_inscripciones_json/{calendario}','HomeController@adminCalendarioInscripcionesJson')->name('admincalendarioinscripcionesjson');
	Route::post('admin_calendario_guardar','HomeController@guardarCalendario')->name('admincalendarioguardar');
	Route::post('admin_calendario_eliminar','HomeController@eliminarCalendario')->name('admincalendarioeliminar');
	Route::get('admin_config','HomeController@adminConfig')->name('adminconfig');
	Route::post('admin_config_guardar','HomeController@guardarConfigCruces')->name('adminconfigguardar');
	Route::get('admin_config_americano','HomeController@adminConfigAmericano')->name('adminconfigamericano');
	Route::post('admin_config_americano_guardar','HomeController@guardarConfigCrucesAmericano')->name('adminconfigamericanoguardar');
	Route::post('admin_config_americano_eliminar','HomeController@eliminarConfigCrucesAmericano')->name('adminconfigamericanoeliminar');
	Route::post('admin_config_americano_get','HomeController@getConfigCrucesAmericano')->name('adminconfigamericanoget');
	Route::post('/registrar_torneo_admin', 'HomeController@registrarTorneo')->name('registrartorneoadmin');
	Route::post('/get_torneos', 'HomeController@getTorneos')->name('gettorneos');
	Route::post('/admin_torneo_selected', 'HomeController@adminTorneoSelected')->name('admintorneoselected');	
	Route::post('/admin_crear_jugador', 'HomeController@adminCrearJugador')->name('admincrearjugador');

	// Rutas para Sponsors
	Route::resource('admin_sponsors', 'SponsorController', ['names' => 'sponsors']);

	Route::post('/admin_editar_jugador', 'HomeController@adminEditarJugador')->name('admineditarjugador');
	Route::get('/get_jugadores_home', 'HomeController@getJugadores')->name('getjugadoreshome');
	Route::post('/admin_eliminar_jugador', 'HomeController@adminEliminarJugador')->name('admineliminarjugador');	
	Route::post('/guardar_fecha_admin_torneo', 'HomeController@guardarFechaAdminTorneo')->name('guardarfechaadmintorneo');
	Route::post('/obtener_datos_zona', 'HomeController@obtenerDatosZona')->name('obtenerdatoszona');
	Route::post('/verificar_numero_parejas_zona', 'HomeController@verificarNumeroParejasZona')->name('verificarnumeroparejaszona');
	Route::post('/obtener_todas_las_zonas', 'HomeController@obtenerTodasLasZonas')->name('obtenertodaslaszonas');
	Route::post('/guardar_torneo_americano', 'HomeController@guardarTorneoAmericano')->name('guardartorneoamericano');
	Route::post('/crear_partidos_americano', 'HomeController@crearPartidosAmericano')->name('crearpartidosamericano');
	Route::get('/admin_torneo_americano_partidos', 'HomeController@adminTorneoAmericanoPartidos')->name('admintorneoamericanopartidos');
	Route::post('/guardar_resultado_americano', 'HomeController@guardarResultadoAmericano')->name('guardarresultadoamericano');
	Route::post('/calcular_posiciones_americano', 'HomeController@calcularPosicionesAmericano')->name('calcularposicionesamericano');
	Route::get('/admin_torneo_americano_cruces', 'HomeController@admintorneoamericanocruces')->name('admintorneoamericanocruces');
	Route::get('/admin_torneo_validar_cruces', 'HomeController@adminTorneoValidarCruces')->name('admintorneovalidarcruces');
	Route::post('/guardar_cruces_editados', 'HomeController@guardarCrucesEditados')->name('guardarcruceseditados');
	Route::post('/guardar_resultado_cruce_americano', 'HomeController@guardarResultadoCruceAmericano')->name('guardarresultadocruceamericano');
	Route::get('/admin_torneo_resultados', 'HomeController@adminTorneoResultados')->name('admintorneoresultados');
	Route::get('/obtener_horarios_cruces', 'HomeController@obtenerHorariosCruces')->name('obtenerhorarioscruces');
	Route::post('/guardar_horarios_cruces', 'HomeController@guardarHorariosCruces')->name('guardarhorarioscruces');
	Route::post('/guardar_resultado_partido', 'HomeController@guardarResultadoPartido')->name('guardarresultadopartido');
	Route::post('/calcular_posiciones_zona', 'HomeController@calcularPosicionesZona')->name('calcularposicioneszona');
	Route::post('/verificar_partidos_completos', 'HomeController@verificarPartidosCompletos')->name('verificarpartidoscompletos');
	Route::get('/admin_torneo_validar_cruces', 'HomeController@adminTorneoValidarCruces')->name('admintorneovalidarcruces');
	Route::post('/confirmar_cruces', 'HomeController@confirmarCruces')->name('confirmarcruces');
	Route::post('/crear_cuartos_desde_octavos', 'HomeController@crearCuartosDesdeOctavos')->name('crearcuartosdesdeoctavos');
	Route::get('/admin_torneo_puntuable_cruces', 'PuntuableController@adminTorneoPuntuableCruces')->name('admintorneopuntuablecruces');
	Route::get('/admin_torneo_puntuable_cruces_v2', 'PuntuableController@adminTorneoPuntuableCrucesV2')->name('admintorneopuntuablecrucesv2');
	Route::post('/guardar_resultado_cruce_puntuable', 'PuntuableController@guardarResultadoCrucePuntuable')->name('guardarresultadocrucepuntuable');
	Route::post('/guardar_resultado_partido_puntuable', 'PuntuableController@guardarResultadoPartidoPuntuable')->name('guardarresultadopartidopuntuable');
	Route::post('/comenzar_torneo_puntuable', 'PuntuableController@comenzarTorneoPuntuable')->name('comenzartorneopuntuable');
	Route::post('/crear_cuartos_desde_octavos', 'PuntuableController@crearCuartosDesdeOctavosEndpoint')->name('crearcuartosdesdeoctavos');
	Route::get('/obtener_participantes_torneo_puntuable', 'PuntuableController@obtenerParticipantesTorneoPuntuable')->name('obtenerparticipantestorneopuntuable');
	Route::post('/guardar_puntos_ranking_torneo', 'PuntuableController@guardarPuntosRankingTorneo')->name('guardarpuntosrankingtorneo');	
	



	Route::get('/home_admin_padel', 'AdminController@adminHome')->name('homeadminpadel');

	Route::get('/admin_jugador', 'AdminController@adminJugador')->name('adminjugador');

	Route::get('/admin_fecha', 'AdminController@adminFecha')->name('adminfecha');

	Route::get('/admin_torneo', 'AdminController@adminTorneo')->name('admintorneo');
	
	Route::get('/admin_tabla_general', 'AdminController@adminTablaGeneral')->name('admintablageneral');
	
	Route::post('/get_tabla_general', 'AdminController@getTablaGeneral')->name('gettablageneral');
	
	Route::post('/registrar_jugador', 'AdminController@registrarJugador')->name('registrarjugador');
	Route::post('/cargar_imagen_jugador', 'AdminController@cargarImagenJugador')->name('cargarimagenjugador');

	Route::post('/registrar_torneo', 'AdminController@registrarTorneo')->name('registrartorneo');
	
	Route::get('/modal_buscar_jugador_list', 'AdminController@modalBuscarJugadorList')->name('modalbuscarjugadorlist');
	
	Route::post('/get_jugador', 'AdminController@getJugador')->name('getjugador');
	Route::post('/get_jugadores', 'AdminController@getJugadores')->name('getjugadores');

	Route::post('/generar_fecha', 'AdminController@generarFecha')->name('generarfecha');

	Route::post('/comenzar_fecha', 'AdminController@comenzarFecha')->name('comenzarfecha');			
	
	Route::post('/get_partido_fecha', 'AdminController@getPartidoFecha')->name('getpartidofecha');			
	
	Route::post('/guardar_puntos', 'AdminController@guardarPuntos')->name('guardarpuntos');
				
	Route::post('/calcular_posiciones', 'AdminController@calcularPosiciones')->name('calcularposiciones');
	
	Route::post('/get_libres', 'AdminController@getLibres')->name('getlibres');	
	
	Route::post('/on_change_torneo', 'AdminController@onChangeTorneo')->name('onchangetorneo');	
	
	Route::post('/get_listado_fechas_previas', 'AdminController@getListadoFechasPrevias')->name('getlistadofechasprevias');	

	Route::get('/torneo/{torneo_id}/fecha/{fecha_id}', 'AdminController@getFecha')->name('ruta.fecha');
	
	Route::post('/guardar_puntos_fecha', 'AdminController@guardarPuntosFecha')->name('guardarpuntosfecha');	

	Route::post('/get_fechas_previas_jugadores', 'AdminController@getFechasPreviasJugadores')->name('getfechaspreviasjugadores');	
});


Route::group(['middleware' => ['auth', 'usuarioPadel']], function () {
	Route::get('/home_padel', 'HomeController@adminHome')->name('homepadel');
	
});

Route::get('terms_conditions_mobile', function () {
    return view('terms_conditions.terms_mobile');
})->name('terms.conditions.mobile');

Route::get('terms_conditions_web', function () {
    return view('terms_conditions.terms_web');
})->name('terms.conditions.web');

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Ruta pública para subir fotos de jugadores (mobile, sin autenticación)
Route::get('/subir-foto-jugador', [App\Http\Controllers\HomeController::class, 'mostrarSubirFotoJugador'])->name('subir.foto.jugador');
Route::post('/buscar-jugadores-publico', [App\Http\Controllers\HomeController::class, 'buscarJugadoresPublico'])->name('buscar.jugadores.publico');
Route::post('/subir-foto-jugador-publico', [App\Http\Controllers\HomeController::class, 'subirFotoJugadorPublico'])->name('subir.foto.jugador.publico');

Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

// Webhook para despliegue automático desde GitHub
Route::post('/deploy-webhook', [\App\Http\Controllers\DeployWebhookController::class, 'handle'])->name('deploy.webhook');

// Webhook configurado y funcionando - Test de despliegue automático
Route::get('/carta', [CartaController::class, 'index'])->name('carta');
