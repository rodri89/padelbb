<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="Rodrigo Banegas">

  <title>Padel</title>
  <link rel="icon" type="image/x-icon" href="images/iconos/logo_serrucho.png" />

<!-- Custom fonts for this template-->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Para que funcione el datatable-->
  <link rel="stylesheet" type="text/css" href="{{asset('datatable/jquery.dataTables.min.css')}}">
  <!-- Custom styles for this template-->
  <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('css/rodri_style.css') }}" rel="stylesheet">
  <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">

  <!-- Para que funcione ajax-->
  <script src = "https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="{{asset('js/jquery.min.js')}}"> </script>
  <!-- Para que funcione ajax fin-->  
  <link rel="stylesheet" href="{{ asset('css/serrucho_style.css') }}">

</head>

@include('layouts.serrucho_style')

@include('modal.snackbar')
<div id="snackbar"><p id="snackbar_text">Cambios guardados</p></div>

<body id="page-top">

  <input type="hidden" id="paciente_seleccionado_id" value="0">

  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav lumen_color sidebar sidebar-dark accordion fondoNav" id="accordionSidebar">

      <!-- Sidebar - Brand -->
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">                
        <img class="icono_header" style="width: 60px; height: 60px" src="images/iconos/logo_serrucho.png" >
        
      </a>

      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li hidden class="nav-item active">
        <a class="nav-link" href="index">
          <i class="fas fa-fw fa-tachometer-alt"></i>
          <span>Mi Panel</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <div class="sidebar-heading">
        Cargar Datos
      </div>

      <li class="nav-item">
        <a class="nav-link" href="admin_torneo">
          <i class="fas fa-fw fa-folder-open"></i>
          <span>Torneo</span></a>
      </li>

      <li class="nav-item">
          <a class="nav-link" href="admin_fecha">
            <i class="fas fa-fw fa-address-card"></i>
            <span>Fecha</span></a>
        </li> 

      <li class="nav-item">
          <a class="nav-link" href="admin_jugador">
            <i class="fas fa-fw fa-address-card"></i>
            <span>Jugador</span></a>
      </li>    

      <li class="nav-item">
          <a class="nav-link" href="admin_tabla_general">
            <i class="fas fa-fw fa-address-card"></i>
            <span>Tabla General</span></a>
      </li>    

      <li class="nav-item">
          <a class="nav-link" href="{{ route('admin.menu.index') }}">
            <i class="fas fa-fw fa-utensils"></i>
            <span>Menú</span></a>
      </li>

      <hr class="sidebar-divider my-0"><br>

      <!-- Heading -->
      <div hidden class="sidebar-heading">
        Productos
      </div>      
      <!-- Nav Item - Pages Collapse Menu -->
      <li hidden class="nav-item">
        <!-- Nav Item - Charts -->
               
        
        <!-- Nav Item - Charts -->
        <li hidden class="nav-item">
          <a class="nav-link" href="buscar_producto">
            <i class="fas fa-fw fa-address-card"></i>
            <span >Buscar</span></a>
        </li>
      </li>

      <!-- Divider -->
      <hr hidden class="sidebar-divider">

      

      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>
    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

          <!-- Sidebar Toggle (Topbar) -->
          <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>

          <h1 id="title_header_secretaria" class="h3 mb-0 text-gray-800">@yield('title_header','Admin')</h1>            
          <!-- Topbar Navbar -->
          <ul class="navbar-nav ml-auto">

            <!-- Nav Item - Search Dropdown (Visible Only XS) -->
            <li class="nav-item dropdown no-arrow d-sm-none">
              <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
              </a>
              <!-- Dropdown - Messages -->
              <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                <form class="form-inline mr-auto w-100 navbar-search">
                  <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="button">
                        <i class="fas fa-search fa-sm"></i>
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </li>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto fondoNavMenu">
              <li class="nav-item">
                <a class="nav-link" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                  <i class="fas fa-moon"></i>
                  <span class="sr-only">Dark Mode</span>
                </a>
              </li>
              <li class="nav-item active">
                <a class="nav-link " onclick="showLogout()">Logout!ss
                </a>
              </li>
            </ul>
          </div>
  
            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
            

          </ul>

        </nav>
        <!-- End of Topbar -->

        <!-- Begin Page Content -->
        <div class="container-fluid">
            @yield('contenedor')
        </div>
        <!-- /.container-fluid -->

      </div>
      <!-- End of Main Content -->

      <!-- Footer -->
      <footer class="sticky-footer bg-white">
        <div class="container my-auto">
          <div class="copyright text-center my-auto">
            <span>Copyright &copy; Padel - REB @nline</span>
          </div>
        </div>
      </footer>
      <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Esta Seguro?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Click en "Cerrar Sesión" para dejar el sitio.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
            <a class="btn btn-primary" type="button" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Cerrar Sesión') }}
            </a>
             <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
      </div>
    </div>
  </div>
  <script type="text/javascript">

    function toggleDarkMode() {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    }

    // Mantener el modo oscuro al recargar la página
    if (localStorage.getItem('darkMode') === 'true') {
      document.body.classList.add('dark-mode');
    }

    function showLogout() {
      $("#logoutModal").modal();        
    }
    
  function mostrarSnackbar(texto) {    
      var x = document.getElementById("snackbar");
      x.className = "show";
      document.getElementById("snackbar_text").innerHtml = texto;
      setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
  }

  </script>
  <!-- Bootstrap core JavaScript-->
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

  <!-- Core plugin JavaScript-->
  <script src="{{ asset('js/jquery.easing.min.js') }}"></script>

  <!-- Custom scripts for all pages-->
  <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

  <!-- Page level plugins 
  <script src="vendor/chart.js/Chart.min.js"></script>

   Page level custom scripts
  <script src="js/demo/chart-area-demo.js"></script>
  <script src="js/demo/chart-pie-demo.js"></script>
   -->
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="{{asset('datatable/jquery.dataTables.min.js')}}"></script>
</body>

</html>
