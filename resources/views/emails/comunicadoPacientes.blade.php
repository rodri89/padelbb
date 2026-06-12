<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <title>Confirmación Turno</title>
</head>
<style type="text/css">

@import url("https://fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap");
@import url("https://fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap");
@import url('https://fonts.googleapis.com/css2?family=Lato&display=swap');

body {
        font-family: "Lato","Poppins", sans-serif;
}

h2 {
        font-family: "Lato","Poppins", sans-serif;
        color: #0095db;
}

.info_paciente {
        color: white;
        font-size: 25px;
}

li {
        list-style:none;
}

p {
        color: white;
}

.button_blanco_azul{
  background: white;
  color: #0095db;
  border-style: solid;  
  width: 160px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  height: 55px;
  padding: 5px;
}

.button_blanco_azul:hover{
  background: #0095db;
  color: white;
  border-style: solid;  
  width: 160px;  
  border-radius: 8px 8px 8px 8px;
  -moz-border-radius: 8px 8px 8px 8px;
  -webkit-border-radius: 8px 8px 8px 8px;
  border: 1px solid #0095db;
  height: 55px;
  padding: 5px;
}

.image_fondo {
    background-image: url("https://drive.google.com/file/d/1htHdwW3Q044krDPMt2ozH6FMZcV0Hjee/view?usp=sharing");
    background-repeat: no-repeat;
}

</style>

<body style="background-image:url(https://cdn.pixabay.com/photo/2016/04/15/04/02/water-1330252__340.jpg); background-repeat: no-repeat;">                
        <div>
                <h2 style="margin-top:150px;margin-left: 270px;"><strong>Tu turno fue confirmado</strong></h2>
                
                <div class="info_paciente">
                        <ul style="width: 662px;height: 150px;margin-left: 20px;margin-top: 55px;text-align: center;">
                                <li>Paciente: <b>{{$data['paciente']}}</b></li>
                                <li>Profesional: <b>{{$data['profesional']}}</b></li>
                                <li>Día: <b>{{$data['fecha']}}</b> Hora: <b>{{$data['horario']}} hs.</b></li>
                                <li>Sede: <b>{{$data['sede']}}</b></li>
                        </ul>
                </div>

                <p style="margin-left: 195px;">Podes cancelar el turno con una anticipación mayor a 24 hs.</p>
                <p style="margin-left: 260px;margin-top: -10px;">para que la sesión no sea contabilizada.</p>
                <br>
                
                <a style="margin-left: 350px;margin-top: 25px;cursor:pointer;" type="button" class="button_blanco_azul" href="www.activekinesio.com/turnos" target="_blank">Cancelar Turno</a>
                <br><br>
                <small style="color: white;margin-left: 220px;">Haciendo clic acá o en activekinesio.com en la sección "Mis Turnos"</small>

                <p style="color:black; margin-left:190px; margin-top:15px">Por favor consultá con tu Obra Social/Prepaga si la orden médica</p>
                <p style="color:black; margin-top: -15px;margin-left: 240px;">debe ser autorizada previo a concurrir a la sesión.</p>

                <p style="color:#0095db;margin-left: 280px;">MUCHAS GRACIAS, ¡TE ESPERAMOS!</p>

                <a style="margin-left:330px; font-size:15px">www.activekinesio.com</a>
        </div>
</body>
</html>