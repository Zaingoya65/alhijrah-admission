                </div> <!-- End of main content -->
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
 <!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    

    
    <script>
        // // Enable DataTables
        // (document).ready(function() {
        //     $('#applicationsTable').DataTable({
        //         "order": [[0, "desc"]],
        //         "responsive": true
        //     });
            
        //     // Enable tooltips
        //     $('[data-bs-toggle="tooltip"]').tooltip();
        // });
    </script>

     <script>
    // const toggleBtn = document.getElementById('toggleSidebar');
    // const closeBtn = document.getElementById('closeSidebar');
    // const sidebar = document.getElementById('sidebar');

    // toggleBtn.addEventListener('click', () => {
    //     sidebar.classList.add('active');
    // });

    // closeBtn.addEventListener('click', () => {
    //     sidebar.classList.remove('active');
    // });
</script>


<script>
// Initialize Bootstrap tooltips and dropdowns
// document.addEventListener('DOMContentLoaded', function() {
//     var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
//     var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
//         return new bootstrap.Dropdown(dropdownToggleEl)
//     });
    
//     // Initialize tooltips if you have them
//     var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
//     var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
//         return new bootstrap.Tooltip(tooltipTriggerEl)
//     });
// });
</script>

<script>
//   var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
//   var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
//     return new bootstrap.Tooltip(tooltipTriggerEl)
//   })
</script>


<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons (Export Features) -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#applicationsTable')) {
        $('#applicationsTable').DataTable().clear().destroy();
    }

    $('#applicationsTable').DataTable({
        dom: 'lBfrtip', // 'l' is for the length (Show entries) menu
        buttons: ['copy', 'excel', 'pdf', 'print'],
        order: [[7, 'desc']],
        pageLength: 10,
        lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]
    });
});
</script>




<!-- DataTables JS for registered users -->
<!-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script> -->
<script>
    $(document).ready(function () {
        $('#usersTable').DataTable();
    });
</script>


</body>
</html>