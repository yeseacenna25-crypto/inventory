<div class="dashboard_topNav">
          <a href="" id="toggleBtn"><i class="fa fa-navicon"></i></a>
         <ul class="nav ms-auto">
          <li class="nav-item">
            <a href="#" id="logout-btn" class="nav-link">
            <i class="fas fa-sign-out-alt bi bi-box-arrow-left"></i> Logout
        </a>
        </li>
        </ul>
        </div>

        <script>
document.addEventListener('DOMContentLoaded', function() {
    // Find the logout button
    const logoutBtn = document.querySelector('#logout-btn');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Ready to leave?',
                text: 'You will be logged out of the system',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show a brief "logging out" message
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'You will be redirected shortly',
                        icon: 'info',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        // Redirect to logout script
                        window.location.href = 'distri_login.php';
                    });
                }
            });
        });
    }
});
</script>