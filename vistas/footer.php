    <footer class="main-footer">
      <div class="pull-right hidden-xs">
        <b>Version</b> 3.0.0
      </div>
      <strong>Copyright &copy; 2025 <a href="www.FerreteriaNeko.com"></a>.</strong> All rights reserved.
    </footer>
    <!-- jQuery -->
    <script src="../public/js/jquery-3.1.1.min.js"></script>
    <!-- Bootstrap 3.3.5 -->
    <script src="../public/js/bootstrap.min.js"></script>
    <!-- AdminLTE App -->
    <script src="../public/js/app.min.js"></script>

    <!-- DATATABLES -->
    <script src="../public/datatables/jquery.dataTables.min.js"></script>
    <script src="../public/datatables/dataTables.buttons.min.js"></script>
    <script src="../public/datatables/buttons.html5.min.js"></script>
    <script src="../public/datatables/buttons.colVis.min.js"></script>
    <script src="../public/datatables/jszip.min.js"></script>
    <script src="../public/datatables/pdfmake.min.js"></script>
    <script src="../public/datatables/vfs_fonts.js"></script>

    <script src="../public/js/bootbox.min.js"></script>
    <script src="../public/js/bootstrap-select.min.js"></script>
    <!-- SweetAlert2 para modales interactivos de KPIs -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      $(document).ready(function() {
        var url = window.location.href;
        // Get the filename from the URL
        var page = url.substring(url.lastIndexOf('/') + 1);

        // Find the link that matches the current page
        $('.sidebar-menu a').each(function() {
          var link = $(this).attr('href');
          if (link == page) {
            $(this).parent('li').addClass('active');
            $(this).closest('.treeview').addClass('active');
          }
        });
      });
    </script>
    </body>

    </html>