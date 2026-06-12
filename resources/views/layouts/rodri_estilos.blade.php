<style type="text/css">

@import url("https://fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap");
@import url("https://fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap");
@import url('https://fonts.googleapis.com/css2?family=Lato&display=swap');

.tr_border_bottom {
  
}

.mi-boton {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin: 5px;
}

.flotante_modal_bottom_img{
  display:scroll;
    position:fixed;
    bottom:25px;
    right:100px;
}

.flotante_modal_top_img{
  display:scroll;
    position:fixed;
    top: 25px;
    right: 80px;
}

.input_width_50px{
  width: 50px;
}

.rodri_button_aceptar_volver{
  background: white;
  border-style: solid; 
  width: 35px;  
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid grey;
  height: 35px;
}

.tabla_fija {
  position: fixed;
}

.tabla_turnos_head{
  display: block;
  padding: 0px;
}

.margin_right_35px{
  margin-right: 35px;
}

.margin_right_20px{
  margin-right: 20px;
}

.margin_right_40px{
  margin-right: 40px;
}

.nav_bar_active {
  color: red;
}

.rodri_button_agenda_seleccionado {
  background: yellow;
  border: none;
  width: 120px;  
  height: 50px;
}

/* Las animaciones de entrada y salida pueden usar */
/* funciones de espera y duración diferentes.      */
.slide-fade-enter-active {
  transition: all .3s ease;
}
.slide-fade-leave-active {
  transition: all .8s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}
.slide-fade-enter, .slide-fade-leave-to
/* .slide-fade-leave-active below version 2.1.8 */ {
  transform: translateX(10px);
  opacity: 0;
}

.width_100px{
  width: 100px;
}

.width_200px{
  width: 200px;
}

.fondoNavActive{
  background: #474646;
  height: 20px;
  }

.fondoNav_active{
    background: #0080ae;
}

.width_400px{
  width: 400px;
}

.fondoListadoPacientePrimeraSesion{
  background: yellow;
}

.coleccionTurnosSeleccionado{
  background: yellow;
  border: none;
  width: 135px;  
  height: 70px;
}

.coleccionTurnosOcupado{
  background: red;
  color: white;
  border: none;
  width: 135px;  
  height: 70px;
}

.coleccionTurnosLibre{
  background: green;
  color: white;
  border: none;
  width: 135px;  
  height: 70px;
}

.coleccionTurnosWhite{
  background: white;
  color: black;
  border: none;
  width: 135px;  
  height: 70px;
}

.fondoNav_activebyboss{
    background: #ac1c1b;
}

.myModal_body_rodri{
  padding:30px 40px 30px 40px;
  background-color:#1E253F;
  border-left:1px solid #007DCF;
  border-right:1px solid #007DCF;
}

.contenedor_centrado {
  display: flex;
  justify-content: center;
  align-items: center;
}

.turnos_online_button{
  width: 150px;
}

.margin_left_50px{
  margin-left: 50px;
}

.margin_left_95px{
  margin-left: 95px;
}

.margin_left_90px{
  margin-left: 90px;
}

.margin_left_150px{
  margin-left: 150px;
}

.margin_top60px{
  margin-top: 60px;
}

.margin_top40px{
  margin-top: 30px;
  margin-bottom: 30px;
}

.padding_bottom40{
  padding-bottom: 40px;
}

.margin_bottom_50px{
  margin-bottom: 50px;
}

.active_services_size{
  height: 10%;
  width: 10%; 
}

.active_services_size_logo{
  height: 20%;
  width: 20%; 
}

.font_active_black{
  color: #000;
}

.font_active_label_size {
  font-size: 1.1rem;
}

.font_active_color{
  color: #0095db;
}

.font_active_title_size{
  font-size: 2.4rem;
}

.font_active_by_boss_color{
  color: #ac1c1b;
}

.margin_top_12px{
  margin-top: 90px;
}

.margin_top_10_px_n{
  margin-top: -10px;
}

.margin_top_50px{
  margin-top: 70px;
}

.margin_top_50px_cel{
  margin-top: 70px; 
}

.margin_left_5px{
  margin-left: 5px;
}

.margin_left_10px{
  margin-left: 10px;
}

.margin_left_15px{
  margin-left: 15px;
}

.margin_top_12px{
  margin-top: 12px;
}

.margin_top_5px{
  margin-top: 5px;
}

.margin_top_menos_5px{
  margin-top: -5px;
}

.margin_top_25px{
  margin-top: 25px;
}

.margin_top_7px{
  margin-top: 7px;
}

.active_color{
  background: #0095db;
}

.nav_seleccionado{
  color: #0095db;
  background: #FFF; 
}

.nav_no_seleccionado{
  color: #FFF;
  background: #0095db; 
}

.nav_no_seleccionado: hover{
  color: #000;
  background: #0095db; 
}

.img_size_small{
  height: 15px;
  width: 15px;
}

.active_by_boss_color{
  background: #ac1c1b;
}

.font_size_15px{
  font-size: 15px;
}

.hijo_centrado {
  position: absolute;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.circulo_rojo_receta {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  background: red;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin:0px auto;
  padding:3%;    
}

.circulo_rojo_receta > h2 {
  margin-top: 7px;
  font-family: "Lato","Poppins", sans-serif;
  color: white;
  font-size: 0.5rem;
  font-weight: bold;
}

.menu_active_nav {
  width: 100%;  
  float: right;
  text-align:left;  
  margin-bottom: -17px;
}

.menu_active_nav ul li {
  display:inline-block;
  list-style-type: none;
  text-align: right;
}

.menu_active_nav ul li a {
  padding: 10px;
}

.float_right{
  float: right;
}

.padding_5px{
  padding: 5px;
}

.float_left{
  float: left;
}

.inline_block{
  display: inline-block;
}

  .marginLeft5px{
    margin-left: 5px;
  }

.admin_videollamada_sobreturno{
  visibility: visible; 
}

.admin_videollamada_sobreturno_cel{
  visibility: hidden; 
}

.mercadopago_collapse_250px{
 width: 250px; 
}

.mercadopago_collapse_70px{
 width: 70px; 
}

.mercadopago_collapse_150px{
 width: 150px; 
}

.mercadopago_collapse_100px{
 width: 100px;  
}

.mercadopago_collapse_30px{
 width: 30px; 
}

.botonImage {
  border-radius: 5px;
  cursor: pointer;
  transition: 0.3s;
}

.botonImage:hover {opacity: 0.7;}

img.mediana{
  width: 100px; height: 100px;
}

.mercadoPagoHeader{
  height:35px
}

.mercadoPagoHeaderTO{
  margin-left: 50px;
  margin-top: 250px;
}

h4.rodri{
	size:1000px;
}

.centrarVerticalHorizontalCancelar {
    /**display: inline;   */
      display: inline-flex;   
      align-items: center;
}

.circulo {
	width: 10rem;
	height: 10rem;
	border-radius: 50%;
	background: white;
	display: flex;
	justify-content: center;
	align-items: center;
	text-align: center;
  margin:0px auto;
  padding:3%;
  border-style: solid;
  border-color: #91e842;
}

.circulo_ocupado{
/*background: gray;*/
border-color: red;
}

.circulo > h2 {
	font-family: "Lato","Poppins", sans-serif;
	color: black;
	font-size: 1.4rem;
	font-weight: bold;
}

.col-center{
  float: none;
  margin-left: auto;
  margin-right: auto;
}


.col-centrada{
    float: none;
    margin: 0 auto;
}

.rodri_button_agenda{
  background: white;
  border: none;
  width: 120px;  
  height: 25px;
}

.margin_top5m{  
  margin-bottom: 0px;
}

.rodri_button_agenda_coleccion{
  background: white;
  border: none;
  width: 120px;  
  height: 50px;
}

.rodri_input_error{
  background: transparent;
  border: none;
  color: red;  
}

.rodri_button_volver{
  background: white;
  border-style: solid; 
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #999999;
  height: 35px;
}

.rodri_button_volver:hover{
  background: #999999;
  border-style: solid;
  color: white; 
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #999999;
  height: 35px;
}

.button_blanco_azul{
  background: white;
  color: #0095db;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  height: 35px;
}

.button_blanco_azul:hover{
  background: #0095db;
  color: white;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  height: 35px;
}

.button_blanco_gris{
  background: white;
  color: grey;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid grey;
  height: 35px;
}

.button_blanco_gris:hover{
  background: grey;
  color: white;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid grey;
  height: 35px;
}

.rodri_button_cancelar_rojo{
  background: red;
  border-style: solid;  
  color: white;
  width: 180px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid red;
  height: 35px;
}

.rodri_button_azul{
  background: white;
  border-style: solid; 
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
}

.rodri_button_azul:hover{
  background: #0095db;
  color: white;
  border-style: solid;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
}

.rodri_button_aceptar{
  background: white;
  border-style: solid; 
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #03f237;
  height: 35px;
}

.rodri_button_aceptar:hover{
  background: green;
  color: white;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid green;
  height: 35px;
}

.rodri_button_cancelar{
  background: white;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid red;
  height: 35px;
}

.rodri_button_cancelar:hover{
  background: red;
  color: white;
  border-style: solid;  
  width: 120px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid red;
  height: 35px;
}

.rodri_button_aceptar_si{
  background: white;
  border-style: solid; 
  width: 35px;  
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid #03f237;
  height: 35px;
}

.rodri_button_aceptar_si_seleccionado{
  background: #03f237;
  color: #FFF;
  border-style: solid; 
  width: 35px;  
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid #03f237;
  height: 35px;
}

.rodri_button_cancelar_no{
  background: white;
  border-style: solid;  
  width: 35px;   
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid red;
  height: 35px;
}

.rodri_button_cancelar_no_seleccionado{
  background: red;
  color: #FFF;
  border-style: solid;  
  width: 35px;   
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid red;
  height: 35px;
}

.rodri_button_cancelar_a{
   background: white;
   border-style: solid;  
   border: 1px solid red;
}

.rodri_button_calendario{
  background: transparent;
  width: 35px;     
  height: 35px;
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
}

.rodri_button_receta{
  background: transparent;
  width: 40px;     
  height: 40px;
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
}

body {
  font-family: "Lato","Poppins", sans-serif;
  background-color: #f8f7fa;
  color: #333;
}

.divider_active{
  background: white;
  padding: 0px;
  width: 80px;
  margin-top: 10px;
  margin-bottom: -15px;
}

.addBorder{
  	border-style: solid;
	border-color: black;
}

.rpadding{
	padding: 25px;
}

.smalltextslogin{
	color:#FFF;
}

.centrarVerticalHorizontal {
   display: flex;   
   align-items: center;
}

.flotante {
    display:scroll;
    position:fixed;
    bottom:25px;
    right:25px;
}

.flotante_continuar{
  width: 160px;
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF;  

  display:scroll;
  position:fixed;
  bottom:80px;
  right:150px;
  height: 45px;
}

.boton_mis_turnos{
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF; 
  padding-top: 5px;
  padding-bottom: 5px;
  font-size: 10px;
  margin-left: 240px;  
  width: 90px;  
}

.boton_mis_turnos_nuevo_turno {
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF; 
  padding-top: 5px;
  padding-bottom: 5px;
  font-size: 15px;
  margin-top: 5px;
  margin-left: 320px;  
  height: 35px;
  width: 125px;  
}

.boton_active_cancelar_size_medium {
  width: 120px;
  background: grey;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid grey;
  color:#FFF;    
  height: 35px;
}

.div_profesionales{
  margin-bottom: -30px;
}

.interlineado_servicios{
  line-height: 150%;
}

.interletrado {
  letter-spacing: 1pt;
}

.interletrado_titulos {
  letter-spacing: 2pt; 
}

.boton_active_cancelar_size_medium:hover {
  width: 120px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid grey;
  color:grey;    
  height: 35px;
}

.boton_active_size_large {
  width: 210px;
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF;    
  height: 35px;
}

.boton_active_size_large:hover {
  width: 210px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#0095db;    
  height: 35px;
}

.boton_turnos_home {
  background: #00a5e7;
  padding: 6px 13px;
  font-size: 10px;
  font-weight: 300;
  border: 1px solid transparent;
  color: #fff;
  -webkit-border-radius: 4px;
  -moz-border-radius: 4px;
  border-radius: 4px;
}

.boton_active_size_medium {
  width: 120px;
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF;    
  height: 35px;
}

.boton_active_size_medium:hover {
  width: 120px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#0095db;    
  height: 35px;
}

.boton_active_white_size_medium {
  width: 120px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#0095db;    
  height: 35px;
}

.boton_active_white_size_medium:hover {
  width: 120px;
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color: #FFF;    
  height: 35px;
}

.boton_active {
  width: 160px;
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF;  

  bottom:150px;
  right:150px;
  height: 45px;
}

.boton_active:hover {
  width: 160px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#0095db;  
    
  bottom:150px;
  right:150px;
  height: 45px;
}

.flotante_continuar:hover{
  width: 160px;
  background: #FFF;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#0095db;  

  display:scroll;
  position:fixed;
  bottom:80px;
  right:150px;
  height: 45px;
}

.flotante_continuar_disabled{
  width: 160px;
  background: grey;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF;  

  display:scroll;
  position:fixed;
  bottom:80px;
  right:150px;
  height: 45px;
}

.margin_top150px{
  margin-top: 150px;
}

.margin_top90px{
  margin-top: 90px;
}

.margin_top_20px{
  margin-top: 20px;
}

.seleccionar_sede_logo{
  height: 50%;
  width: 50%;
}

.table_head{
  background: grey;
}

.table_body{
  background: #bfbfbf;
}

.fondo_continuar_active{
  border:1px solid #0095db;
  background: #0095db;
}

.fondo_continuar_active:hover{
  border:1px solid #0095db;
  color: #0095db;
  background: #FFF;
}

.font_size_continuar{
  font-size: 1.3rem;
}

.centrar_vertical_padre {
    position: relative;
}

.centrar_vertical_hijo {
   position: absolute;
   top: 0;
   bottom: 0;
   left: 0;
   right: 0;
   width: 50%;
   height: 50%;
   margin: auto;
}

.footer_flotante {      
    font-size: 10px;
    background: #474646;    
    display:scroll;
    position:fixed;
    height: 50px;
    width: 100%;
    bottom: 0px;    
}

.margin_bottom40px{
  padding-bottom: 40px;
}

.margin_bottom_10px{
  margin-bottom: 10px;
}

.widthImageCarousel{
    width: 100%;
  }

.flotante_secretaria {
    display:scroll;
    position:fixed;
    bottom:50px;
    right:25px;
}

.pie_pagina {
    position: relative;
    bottom: 0;
    width: 100%;
    height: 300px;
    background-color: black;
}

.sinBackground{
  background-color: transparent;
  border: none;
}

.sinBackgroundAncho{
  background-color: transparent;
  border: none;
  width: 480px;
}

.domicilio{
  margin-left: 5px;
  width: 300px;
}

.sinBackgroundMedioAncho{
  background-color: transparent;
  border: none;
  width: 200px;
}

.sinBackgroundMp{
  background-color: transparent;
  border: none;
  width: 92px;
}

.sinBackgroundAnchoFechaDNI{
  background-color: transparent;
  border: none;
  width: 120px;
}

.tr_amarillo {
  background-color: #F8DF73;
}

.sinBackgroundAzul{
  background-color: transparent;
  border: none;
  color:blue;
  text-decoration: underline;
}

.terminosCondicionesSize{
  margin-left: 5%;
  margin-right: 5%;
  color: white; 
  font-size: x-small;
}

.input_snackbar{
  border: none;
  font-weight:bold; 
  color: #FFF;
  width: 42px;  
  background: #333;
}

.letraAzul {
   color:#303F9F;
}

.input_snackbar_220{
  border: none;
  font-weight:bold; 
  text-align: center;
  color: #FFF;
  width: 220px;  
  background: #333;
}

.modal_input_horario{
  border: none;
  font-weight:bold; 
  width: 50px;  
}

.input_configuracion_horario{ 
  text-align: center;
  width: 60px;  
}

.rodri_button_a{
  background: red;  
  border-radius: 120px 120px 120px 120px;  
  border: 1px solid #03f237; 
  
}

.rodri_button{
  width: 160px;
  background: linear-gradient(45deg, rgba(44,123,144,1) 0%, rgba(44,123,144,1) 16%, rgba(42,108,126,1) 36%, rgba(0,77,69,1) 100%);
  border-radius: 120px 120px 120px 120px;
-moz-border-radius: 120px 120px 120px 120px;
-webkit-border-radius: 120px 120px 120px 120px;
border: 1px solid #03f237;
color:#FFF;
height: 35px;
}

.rodri_button_active{
  width: 160px;
  background: #0095db;
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid #0095db;
  color:#FFF;
  height: 35px;
}

.rodri_button_active_by_boss{
  width: 160px;
  background: #ac1c1b;
  border-radius: 120px 120px 120px 120px;
  -moz-border-radius: 120px 120px 120px 120px;
  -webkit-border-radius: 120px 120px 120px 120px;
  border: 1px solid #ac1c1b;
  color:#FFF;
  height: 35px;
}

.rodri_button_login{
  width: 160px;
  background: linear-gradient(45deg, rgba(44,123,144,1) 0%, rgba(44,123,144,1) 16%, rgba(42,108,126,1) 36%, rgba(0,77,69,1) 100%);
  border-radius: 120px 120px 120px 120px;
-moz-border-radius: 120px 120px 120px 120px;
-webkit-border-radius: 120px 120px 120px 120px;
border: 1px solid #03f237;
color:#FFF;
height: 35px;
}

.loginHeader{
    font-size: 1.0rem;
    margin-left: 0px;
    margin-top: 0px; 
  }

  .loginHeaderSize{
    height: 20px;
  }

.rodri_button_active_large{
  width: 260px;
  background: #0095db;
  border-radius: 120px 120px 120px 120px;
-moz-border-radius: 120px 120px 120px 120px;
-webkit-border-radius: 120px 120px 120px 120px;
border: 1px solid #0095db;
color:#FFF;
height: 35px;
}

.rodri_button_disabled{
  width: 160px;
  background: grey;
  border-radius: 120px 120px 120px 120px;
-moz-border-radius: 120px 120px 120px 120px;
-webkit-border-radius: 120px 120px 120px 120px;
border: 1px solid grey;
color:#FFF;
height: 35px;
}

.fondoHeader{
  /*background: linear-gradient(to right, rgba(210,255,82,1) 0%, rgba(145,232,66,1) 100%);*/
 background: linear-gradient(45deg, rgba(147,206,222,1) 0%, rgba(147,206,222,1) 16%, rgba(117,189,209,1) 36%, rgba(0,150,136,1) 82%, rgba(0,150,136,1) 100%);
}

hr.style4 {
  border-top: 1px dotted #8c8b8b;
}

.fontColorHeader{
  /*color:#388E3C;*/
  color:#303F9F;
}

.fontTitulo{
  font-size: 3.4rem;
  color:#303F9F;
}

.fontHomeTitulo{
  font-size: 2.4rem;
  color:#303F9F;
}

.fontVideollamadaTitulo{
  font-size: 2.4rem;
  color:#303F9F;
}

.fontHomeSubtitulo{
  font-size: 1.9rem;
  color:#303F9F; 
}

.fontHomeBody{
  font-size: 1.5rem;
  color:#303F9F;
}

.fontColorHeaderParrafo{
 color:#FFF;
 opacity: 10; 
}

.footerBackground{
  background: #474646;
  /*background: linear-gradient(45deg, rgba(44,123,144,1) 0%, rgba(44,123,144,1) 16%, rgba(42,108,126,1) 36%, rgba(0,77,69,1) 100%);*/
}

.fondoTablaTurnos{
  background: grey;
}

.fondoNav{
  /*background: #388E3C;*/
  background: #474646;/*linear-gradient(45deg, rgba(44,123,144,1) 0%, rgba(44,123,144,1) 16%, rgba(42,108,126,1) 36%, rgba(0,77,69,1) 100%);*/
}

.fontNav{
  color:#FFF; 
}

.fontContainer{
  background: #C8E6C9;
}

.contenedor3 {
        display: flex;
        align-items: center;
}
.contenido3 {
        margin: 0 auto; /* requerido para alineación horizontal */

}

.alinearHorizontal{
  display: inline-block;
}

.letrasrojo{
  color:red;
}

.letrasVerde{
  color:green;
}

.letrasblancas{
  color:white;
}

.img-responsive{
    width:100%;
    height:auto;
}

.r_inline{
  display: inline-block;
}

.img_home_2{
  width: 250px;
  height: 250px;
  margin-right: 20px;
  margin-left: 20px;
}

.img_obra_social{
  width: 250px;
  height: 100px;
}

.img_home{
  width: 149px;
  height: 149px;
  margin-right: 20px;
  margin-left: 20px;
} 
  .margin5{
    margin-left: 5px;
  }

.celular_codigo_area{  
  margin-left: 10px;
  width: 100px;
}

.celular_numero{
  margin-left: 10px;
  width: 200px;
}

 .fechaNacEditText{
    margin-left: 10px;
    width: 50px;
  }
  .fechaNacAnioEditText{
    margin-left: 10px;
    width: 100px;
  }

  .img_turno_apl{
    width: 100px;
    height: 100px;
    font-size: 0.5rem;  
  }

  .turno_text_size_apl{
    font-size: 1.0rem;
  }

  .visibleLoginCel{
    visibility: hidden;
  }

 .botonReceta{
  margin-left: 25px;  
 }
 .textoReceta{
  margin-top: 4px;
  margin-left: 35px;
 }

 .seleccionar_especialista_img{
    width: 150px; 
    height: 200px;
 }

 .footer_img{
    height: 15%;
    width: 15%;
 }

.footer_redes_img{
  height: 3%;
  width: 3%;
}

.servicios_active_padding_cel{
  padding-right: 10px;
  padding-left: 10px;
}

.logo_nav_cel{
  margin-left: 95px;
  text-align: center;  
}

.fondoNavTurnosCel{
    height: 15px; 
    background: #000;
  }

.hiddenWeb {
  visibility: hidden; 
}

.fondoNavNovedad{
  background:#0095db;  
}

.novedades_redes_img{
  height: 36px;
  width: 36px;
}

.margin_left_15_novedades_px_novedades{
  margin-left: 160px;
}

.margin_top_novedad_detalle{
  margin-top: 80px;
}

.margin_left_novedad_redes_px{
  margin-left: 800px;
}

.margin_left_20px{
  margin-left: 20px;
}

.novedades_titulo_margin_top{
  margin-top: 40px;
}

.novedades_subtitulo_font_size{
  font-size: 20px;
}

.seleccionar_turno_active_font_size{
  font-size: 25px;
}

.margin_left_logo_turnos{
  margin-left: 450px;
}

.nuevo_turno_style_mis_turnos{
  margin-top: 0px;
}

@media (max-width:360px){

.nuevo_turno_style_mis_turnos{
  margin-top: -30px;
}

.margin_left_logo_turnos{
  margin-left: 5px;
}

.seleccionar_turno_active_font_size{
  font-size: 18px;
}

.novedades_subtitulo_font_size{
  font-size: 15px;
}

.novedades_titulo_margin_top{
  margin-top: 15px;
}

.margin_left_novedad_redes_px{
  margin-left: 80px;
}

.margin_top_novedad_detalle{
  margin-top: 30px;
}

.novedades_redes_img{
  height: 25px;
  width: 25px;
}

.margin_left_15_novedades_px_novedades{
  margin-left: 260px;
}

.margin_left_10_n{
  margin-left: -10px;
}

.hiddenWeb {
  visibility: visible; 
}

.margin_top_30_n{
  margin-top: -30px;
}

.logo_nav_cel{
  margin-left: 5px;
  text-align: left;  
}

.margin_right_35px{
  margin-right: 15px;
}

.margin_left_150px{
  margin-left: 1px;
  padding-right: 20px;
  padding-left: 20px;
}

.turnos_h2_cel{
  font-size: 25px;
}

.turnos_input_font_size_cel{
  font-size: 20px;
}

.margin_left_95px{
  margin-left: 10px;
}

.active_services_size_logo{
  height: 30%;
  width: 30%; 
}

.footer_redes_img{
  height: 10%;
  width: 10%;
}

.footer_img{
    height: 40%;
    width: 40%;
 }

.profesionales_margin_cel{
  margin-bottom: -100px;
}

.active_services_size{
  height: 40%;
  width: 40%; 
}


.margin_top_50px_cel{
  margin-top: 0px; 
}

.margin_top_20px_cel{
  margin-top: 20px;
}

.turnos_telefonicos_mobile_2{
  font-size: 10px;
}
.turnos_telefonicos_mobile{
  font-size: 0.5sp;
}

.tabla_sin_bordes{
  border-collapse:collapse;
}

.coleccionTurnosSeleccionado{
  background: yellow;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosOcupado{
  background: red;
  color: white;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosWhite{
  background: white;
  color: black;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosLibre{
  background: green;
  color: white;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

  .hiddenWebShowCel{
      display: contents;
  }

  .hiddenCel{
    visibility: hidden;
  }

  .seleccionar_especialista_img{  
    width: 100px; 
    height: 150px;
  }

  .seleccionar_especialista_card{
    width: 350px;    
  }

  .seleccionar_especialista_card_margin_left{
    margin-left: 1px;
  }

  .widthImageCarousel{
    width: 450px;
  }

  .rodri_button_aceptar_si{
    background: white;
    border-style: solid; 
    height: 35px;
    width: 35px;  
    border-radius: 120px 120px 120px 120px;
    -moz-border-radius: 120px 120px 120px 120px;
    -webkit-border-radius: 120px 120px 120px 120px;
    border: 1px solid #03f237;
    margin-top: 2px;
    margin-bottom: 2px;
  }

  .widthImageCarousel{
    width: 360px;
  }

  .width200px{
    width: 300px;
  }

  .botonReceta{
    margin-left: 10px;  
  }
  
  .textoReceta{
    margin-top: 15px;
    margin-left: 35px;
  }

  .buttonMenuSize{
    border-color: #FFF;
    color: #FFF;
    height: 10px;
    width: 10px;
    position: absolute;
    top: 0;
    right: 0;
    margin-right: 5px;
    margin-top: 2.5px;
  }

  .buttonMenuSizeMarco{
    border-color: #FFF;
    height: 15px;
    width: 12px;
    position: absolute;
    top: 0;
    right: 0;
    margin-right: 5px;
    margin-top: 2.5px;
  }

  .sizeHeader{
    height: 390px;
  }
  .img_turno{
    width: 100px;
    height: 100px;
    font-size: 0.5rem;   
  }
  .turno_text_size{
    font-size: 1.5rem;
  }

  .fontHomeSubtitulo{
    font-size: 1.1rem;    
  }

  .textheader
{
    position: absolute;
    top: 0;
    font-size: 0.5rem;     
  }

  .textheaderMenu{
    /*position: absolute;
    top: 0;*/
    font-size: 0.5rem;     
  }  

  .textheaderLogin{
    position: absolute;
    top: 2;
    right: 0;
    font-size: 0.5rem;     
  }

  .fondo_blanco{
    background: #FFF;
  }

  .fondoNav{
    height: 20px;
  }



  .fondoNavMenu{     
     background: #474646;
  }

  .menuAlineacion{
    float: right;     
  }

  .fontImage{
     font-size: 0.7rem;
  }

  .fontHomeBody2{
    color:#303F9F;
    margin-top: 25px;
    font-size: 0.7rem;
    width: 250px;
    float: right;
  }  

  .fontHomeBody{
    margin-top: 25px;
    font-size: 1.0rem;
    width: 250px;
    float: right;
  }
  
  .img_size_carrousel{
    width: 15px;
    height: 15px;  
  }

  .img_home{
    margin-left: 25px;
    width: 100px;
    height: 100px;
  } 
  .createdby{
   font-size: 0.7rem; 
  }

  .rodri_button{
    font-size: 0.7rem;
    width: 80px;
    height: 25px;
  }

  .rodri_button_login{
    font-size: 0.5rem;
    width: 80px;
    height: 25px;
  }

  .contenedor3_telefono {
        display: flex;
        align-items: center;
  }
  .contenido3_telefono {
        margin: 0 auto; /* requerido para alineación horizontal */
  }

  .buttonpreviousnext_telefono{
    width: 50px;
    height: 50px;
  }

  .centrarVerticalHorizontal {
    /**display: inline;   */
      display: inline-flex;   
      align-items: center;
  }

  h4.fontColorHeader{
    font-size: 1.3rem;
  }

  h1.fontColorHeader{
    font-size: 1.2rem;
  }

  p.fontColorHeader{
   font-size: 0.9rem; 
  }

  li.fontColorHeader{
   font-size: 0.9rem;  
  }

  .fontHomeTitulo{
    font-size: 1.0rem;
    color:#303F9F;
  }

  .editText{
    font-size: 0.9rem; 
  }

  .fechaNacEditText{
    margin-left: 15px;
    width: 80px;
  }
  .fechaNacAnioEditText{
    margin-left: 15px;
    width: 120px;
  }
  .img_medico_center{
    width: 100px;
    height: 100px;
  }

  .fontMedicoNombre{
    font-size: 1.2rem;
    color:#303F9F;
  }

  .divLogin{
    height: 200px;
    width: 300px;
  }

  .marginLeft10px{
    margin-left: 10px;
  }

  .marginTop20px{
    margin-top: 20px;
  }

  .divMarginCel{
    margin-top: 10px;
    margin-bottom: 10px;
  }

  .loginHeader{
    font-size: 0.3rem;
    margin-left: -5px;
    margin-top: -5px; 
  }

  .inputTextLogin{
    font-size: 0.6rem;
    height: 28px;
    width: 200px;
  }

  .loginText{
    font-size: 0.6rem;
  }

  .medicoTituloTextoCel{
   font-size: 1.0rem;
   margin-top: 5px;
   margin-left: 20px; 
  }

  .textAlignCenterCel{
    text-align: center;    
  }

.rodri_button_agenda{
  background: transparent;
  border: none;
  width: 300px;  
  height: 25px;
}

.rodri_button_agenda:hover{
  background: #0095db;
  color: #FFF;
  border: none;
  width: 300px;  
  height: 25px;
}

.marginTop50px{
  margin-top: 50px;
}

.hiddenLoginCel{
  visibility: hidden;
}
.visibleLoginCel{
  visibility: visible;
}

.img_home_2{
  width: 150px;
  height: 150px;
  margin-top: 20px;
  margin-left: 30%;
}

.mercadoPagoHeaderTO{
  margin-left: 5px;  
  top:0;
}
.marginTopCel_25px{
  margin-top: 25px;    
}
.marginTopCel_15px{
  margin-top: 15px;    
}

.sinBackgroundMp{
  background-color: transparent;
  border: none;
  width: 92px;
}

.mercadopago_collapse_250px{
 width: 300px; 
}

.mercadopago_collapse_70px{
 width: 70px; 
}

.mercadopago_collapse_150px{
 width: 130px; 
}

.mercadopago_collapse_100px{
 width: 100px;  
}

.mercadopago_collapse_30px{
 width: 10px; 
}

.fontVideollamadaTitulo{
  font-size: 1.4rem;
  color:#303F9F;
}

.videollamada_margin_left_50px{  
  text-align: center;
}
.admin_videollamada_sobreturno_cel{
  visibility: visible;
  margin-left:10px; 
}

.admin_videollamada_sobreturno{
  visibility: hidden; 

}

}
  
.hiddenWebShowCel{
    display: none;
}

.hiddenCelShowWeb{  
  display: contents;
}

.add_scroll {
  height:400px;
  overflow-y: scroll;
}


@media (max-width:435px){

.boton_mis_turnos_nuevo_turno {
  background: #0095db;
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  color:#FFF; 
  padding-top: 5px;
  padding-bottom: 5px;
  font-size: 10px;  
  margin-left: 240px;    
  width: 90px;
  height: 25px;  
}

.nuevo_turno_style_mis_turnos{
  margin-top: -30px;
}

.margin_left_logo_turnos{
  margin-top: -4px;
  margin-left: 5px;
}

.seleccionar_turno_active_font_size{
  font-size: 18px;
}

.novedades_subtitulo_font_size{
  font-size: 15px;
}

.novedades_titulo_margin_top{
  margin-top: 15px;
}

.novedad_detalle_imagen{
  width: 350px;
  height: 200px;
}

.margin_left_novedad_redes_px{
  margin-left: 80px;
}

.margin_top_novedad_detalle{
  margin-top: 30px;
}

.novedades_redes_img{
  height: 25px;
  width: 25px;
}

.margin_left_15_novedades_px_novedades{
  margin-left: 260px;
}

.margin_left_10_n{
  margin-left: -10px;
}

.hiddenWeb {
  visibility: visible; 
}

.margin_top_30_n{
  margin-top: -30px;
}

.logo_nav_cel{
  margin-left: 5px;
  text-align: left;  
}

.margin_right_35px{
  margin-right: 15px;
}

.margin_left_150px{
  margin-left: 1px;
  padding-right: 20px;
  padding-left: 20px;
}

.margin_datepicker_cel{
  margin-top: 20px;
  margin-left: 50px;
  margin-bottom: 20px;
}

.turnos_input_font_size_cel{
  font-size: 20px;
}

.turnos_h2_cel{
  font-size: 25px;
}

.margin_left_95px{
  margin-left: 10px;
}

.active_services_size_logo{
  height: 30%;
  width: 30%; 
}

.footer_redes_img{
  height: 10%;
  width: 10%;
}

.footer_img{
    height: 40%;
    width: 40%;
 }

.profesionales_margin_cel{
  margin-bottom: -100px;
}

.active_services_size{
  height: 40%;
  width: 40%; 
}

.margin_top_50px_cel{
  margin-top: 0px; 
}

.margin_top_20px_cel{
  margin-top: 20px;
}

.turnos_telefonicos_mobile{
  font-size: 18px;
}

.turnos_telefonicos_mobile_2{
  font-size: 10px;
}

.tabla_sin_bordes{
  border-collapse:collapse;
}

.coleccionTurnosSeleccionado{
  background: yellow;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosOcupado{
  background: red;
  color: white;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosWhite{
  background: white;
  color: black;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}

.coleccionTurnosLibre{
  background: green;
  color: white;
  border: none;
  width: 250px;  
  height: 70px;
  margin-left: 35px;
}
  
  .hiddenCelShowWeb{
    display: none;
  }

  .hiddenWebShowCel{
      display: contents;
  }

  .hiddenCel{
      display: none;
  }

  .seleccionar_especialista_img{    
    width: 150px; 
    height: 200px;
  }

  .seleccionar_especialista_card{
    width: 500px;
  }

  .seleccionar_especialista_card_margin_left{
    margin-left: 15px;
  }

  .widthImageCarousel{
    width: 450px;
  }

  .width200px{
    width: 300px;
  }

  .botonReceta{
    margin-left: 10px;  
  }
  
  .textoReceta{
    margin-top: 15px;
    margin-left: 35px;
  }

  .buttonMenuSize{
    border-color: #FFF;
    color: #FFF;
    height: 10px;
    width: 10px;
    position: absolute;
    top: 0;
    right: 0;
    margin-right: 5px;
    margin-top: 2.5px;
  }

  .buttonMenuSizeMarco{
    border-color: #FFF;
    height: 15px;
    width: 12px;
    position: absolute;
    top: 0;
    right: 0;
    margin-right: 5px;
    margin-top: 2.5px;
  }

  .sizeHeader{
    height: 390px;
  }
  .img_turno{
    width: 100px;
    height: 100px;
    font-size: 0.5rem;   
  }
  .turno_text_size{
    font-size: 1.5rem;
  }

  .fontHomeSubtitulo{
    font-size: 1.1rem;    
  }

  .textheader{
    position: absolute;
    top: 0;
    font-size: 0.5rem;     
  }

  .textheaderMenu{
    /*position: absolute;
    top: 0;*/
    font-size: 0.5rem;     
  }  

  .textheaderLogin{
    position: absolute;
    top: 2;
    right: 0;
    font-size: 0.5rem;     
  }

  .fondoNav{
  /*background: #388E3C;*/
  height: 50px;
  }

  .fondoNavMenu{     
     background: #474646;
  }

  .menuAlineacion{
    color: #FFF;
    float: right;     
  }

  .fontImage{
     font-size: 0.7rem;
  }

  .fontHomeBody2{
    color:#303F9F;
    margin-top: 25px;
    font-size: 0.7rem;
    width: 250px;
    float: right;
  }  

  .fontHomeBody{
    margin-top: 25px;
    font-size: 1.0rem;
    width: 250px;
    float: right;
  }
  
  .img_size_carrousel{
    width: 15px;
    height: 15px;  
  }

  .img_home{
    margin-left: 25px;
    width: 100px;
    height: 100px;
  } 
  .createdby{
   font-size: 0.7rem; 
  }

  .rodri_button{
    font-size: 0.7rem;
    width: 80px;
    height: 25px;
  }

  .rodri_button_login{
    font-size: 0.5rem;
    width: 80px;
    height: 25px;
  }

  .contenedor3_telefono {
        display: flex;
        align-items: center;
  }
  .contenido3_telefono {
        margin: 0 auto; /* requerido para alineación horizontal */
  }

  .buttonpreviousnext_telefono{
    width: 50px;
    height: 50px;
  }

  .centrarVerticalHorizontal {
    /**display: inline;   */
      display: inline-flex;   
      align-items: center;
  }

  h4.fontColorHeader{
    font-size: 1.3rem;
  }

  h1.fontColorHeader{
    font-size: 1.2rem;
  }

  p.fontColorHeader{
   font-size: 0.9rem; 
  }

  li.fontColorHeader{
   font-size: 0.9rem;  
  }

  .fontHomeTitulo{
    font-size: 1.0rem;
    color:#303F9F;
  }

  .editText{
    font-size: 0.9rem; 
  }

  .fechaNacEditText{
    margin-left: 15px;
    width: 90px;
  }
  .fechaNacAnioEditText{
    margin-left: 15px;
    width: 120px;
  }
  .img_medico_center{
    width: 100px;
    height: 100px;
  }

  .fontMedicoNombre{
    font-size: 1.2rem;
    color:#303F9F;
  }

  .divLogin{
    height: 200px;
    width: 300px;
  }

  .marginLeft10px{
    margin-left: 10px;
  }

  .marginTop20px{
    margin-top: 20px;
  }

  .marginTop50px{
    margin-top: 90px;
  }

  .divMarginCel{
    margin-top: 10px;
    margin-bottom: 10px;
  }

  .loginHeader{
    font-size: 0.6rem;
    margin-left: -5px;
    margin-top: -5px; 
  }

  .inputTextLogin{
    font-size: 0.6rem;
    height: 28px;
    width: 200px;
  }

  .loginText{
    font-size: 0.6rem;
  }

  .medicoTituloTextoCel{
   font-size: 1.0rem;
   margin-top: 5px;
   margin-left: 20px; 
  }

  .textAlignCenterCel{
    text-align: center;    
  }

.rodri_button_agenda{
  background: transparent;
  border: none;
  width: 300px;  
  height: 25px;
}

.marginTop50px{
  margin-top: 50px;
}


.hiddenLoginCel{
  visibility: hidden;
}
.visibleLoginCel{
  visibility: visible;
}

.img_home_2{
  width: 150px;
  height: 150px;
  margin-top: 20px;
  margin-left: 30%;
}

.mercadoPagoHeaderTO{
  margin-left: 5px;  
  top:0;
}
.marginTopCel_25px{
  margin-top: 25px;    
}
.marginTopCel_15px{
  margin-top: 15px;    
}

.sinBackgroundMp{
  background-color: transparent;
  border: none;
  width: 92px;
}

.mercadopago_collapse_250px{
 width: 300px; 
}

.mercadopago_collapse_70px{
 width: 70px; 
}

.mercadopago_collapse_150px{
 width: 130px; 
}

.mercadopago_collapse_100px{
 width: 100px;  
}

.mercadopago_collapse_30px{
 width: 10px; 
}

.fontVideollamadaTitulo{
  font-size: 1.4rem;
  color:#303F9F;
}

.videollamada_margin_left_50px{  
  text-align: center;
}
.admin_videollamada_sobreturno_cel{
  visibility: visible;
  margin-left:10px; 
}

.admin_videollamada_sobreturno{
  visibility: hidden; 

}

}
</style>