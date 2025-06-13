<!-- Footer -->
<footer class="bg-primary bg-opacity-10 text-dark pt-5 pb-4 border-top">
    <div class="container">
        <!-- Top Row -->
        <div class="row gy-4">
            <!-- About -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <img src="assets/images/trust-logo.png" alt="Al-Hijrah Trust" height="50" class="me-3">
                    <h5 class="fw-bold mb-0">Al-Hijrah Trust</h5>
                </div>
                <p class="small">
                    A non-profit educational organization committed to nurturing future generations through academic excellence, 
                    Islamic values, and patriotic spirit. Building compassionate leaders who embody <strong>Islamiat</strong>, 
                    uphold <strong>Insaniat</strong>, and contribute to <strong>Pakistaniat</strong>.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="nav flex-column small">
                    <li class="nav-item mb-2">
                        <a href="index.php" class="nav-link p-0 text-dark">
                            <i class="fas fa-chevron-right me-2 text-primary"></i> Home
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#admission" class="nav-link p-0 text-dark">
                            <i class="fas fa-chevron-right me-2 text-primary"></i> Admission
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#campuses" class="nav-link p-0 text-dark">
                            <i class="fas fa-chevron-right me-2 text-primary"></i> Campuses
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#contact" class="nav-link p-0 text-dark">
                            <i class="fas fa-chevron-right me-2 text-primary"></i> Contact
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Campuses -->
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Our Campuses</h5>
                <div class="d-flex mb-3">
                    <div class="me-3 text-primary">
                        <i class="fas fa-map-marker-alt fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Ziarat Campus</h6>
                        <p class="small mb-0">Balochistan, Pakistan</p>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="me-3 text-primary">
                        <i class="fas fa-map-marker-alt fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Dera Ghazi Khan Campus</h6>
                        <p class="small mb-0">Punjab, Pakistan</p>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Contact Us</h5>
                <ul class="list-unstyled small">
                    <li class="mb-3 d-flex">
                        <div class="me-3 text-primary">
                            <i class="fas fa-phone fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Phone</h6>
                            <p class="mb-0">+92 123 4567890</p>
                        </div>
                    </li>
                    <li class="mb-3 d-flex">
                        <div class="me-3 text-primary">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Email</h6>
                            <p class="mb-0">info@alhijrahtrust.edu.pk</p>
                        </div>
                    </li>
                    <li class="d-flex">
                        <div class="me-3 text-primary">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Hours</h6>
                            <p class="mb-0">Mon - Fri: 9:00 AM - 5:00 PM</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="my-4 border-primary border-opacity-25">

        <!-- Bottom Row -->
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start small mb-3 mb-md-0">
                &copy; <?php echo date('Y'); ?> Al-Hijrah Trust. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="social-icons d-inline-flex gap-3">
                    <a href="#" class="text-dark text-opacity-75 hover-primary">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-dark text-opacity-75 hover-primary">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-dark text-opacity-75 hover-primary">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-dark text-opacity-75 hover-primary">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Custom Footer Styles */
    .hover-primary:hover {
        color: var(--primary-color) !important;
        transform: translateY(-2px);
    }
    
    .border-primary {
        border-color: rgba(44, 62, 80, 0.1) !important;
    }
    
    @media (max-width: 767.98px) {
        .social-icons {
            justify-content: center;
            width: 100%;
        }
    }
</style>