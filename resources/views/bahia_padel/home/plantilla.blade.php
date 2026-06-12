<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="Rodrigo Banegas">

  <title>@yield('title_header', 'Bahía Pádel')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" />

<!-- Custom fonts for this template-->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="{{ asset('css/sb-admin-2.css') }}" rel="stylesheet">
  <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">

  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('layouts.bahiapadel_style')
</head>
<body>
  @include('modal.snackbar')
  <div id="snackbar"><p id="snackbar_text">Cambios guardados</p></div>
  <div class="wrapper">
    <nav class="navbar navbar-expand-md custom-header p-2 mb-4">
      <div class="container-fluid">
        <!-- Logo a la izquierda -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('index') }}">
          <img class="icono_header header_ic" src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}">
        </a>
        <!-- Botón hamburguesa a la derecha en mobile -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Menú colapsable -->
        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
          <ul class="navbar-nav ml-auto menu-blanco">
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" href="{{ route('index') }}">Home</a>
            </li>
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" href="{{ route('home.torneos') }}">Torneos</a>
            </li>
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" href="{{ route('home.ranking') }}">Ranking</a>
            </li>
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" href="{{ route('home.calendario') }}">Calendario</a>
            </li>        
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" href="{{ route('home.reglamento') }}">Reglamento</a>
            </li>
            <li class="nav-item mx-1">
              <a class="nav-link header_btn" onclick="toggleDarkMode()" title="Toggle Dark Mode" style="cursor: pointer;">
                <i class="fas fa-moon"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Todo el contenido de la página aquí -->    
    <main>
      <div class="container-fluid py-4">
        @yield('contenedor')
      </div>
    </main>

    <footer class="sticky-footer">
      {{-- Sponsors fijos (sin consulta a BD para evitar recargas) --}}
      <div class="footer-sponsors">
        <div class="footer-sponsors-track">
          @php
            $sponsorsFijos = ['bahiapadel.png', 'sancor.png', 'pampero.png', 'kalea.png', 'adn.png', 'drift.png', 'garage.png', 'latino.png', 'reims.png', 'sis.png', 'stork.png', 'iph.png', 'af.png'];
          @endphp
          @foreach($sponsorsFijos as $img)
            <div class="footer-sponsor-card">
              <img src="{{ asset('images/ads/' . $img) }}" alt="Sponsor" loading="lazy">
            </div>
          @endforeach
        </div>
      </div>
      <div class="copyright text-center my-auto">
        <span>Copyright &copy; BahiaPadel</span>
      </div>
    </footer>
  </div>
    
<!-- End of Footer -->

  <script type="text/javascript">
      
  function mostrarSnackbar(texto) {    
      var x = document.getElementById("snackbar");
      x.className = "show";
      document.getElementById("snackbar_text").innerHtml = texto;
      setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
  }

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
  }

  // Mantener el modo oscuro al recargar la página
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }

  </script>
  <!-- jQuery y Bootstrap (solo lo esencial para navbar y collapse) -->
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
