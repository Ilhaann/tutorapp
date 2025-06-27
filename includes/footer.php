    </main>
    <footer class="footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-4">
                <!-- About Section -->
                <div class="col-lg-4">
                    <h5 class="mb-4">About Strathmore Peer Tutoring</h5>
                    <p class="text-muted">Connecting students with verified tutors for personalized learning experiences. Our platform ensures quality education through a rigorous tutor verification process.</p>
                    <div class="social-links mt-4">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="tutors.php" class="text-muted text-decoration-none">Find Tutors</a></li>
                        <li class="mb-2"><a href="subjects.php" class="text-muted text-decoration-none">Subjects</a></li>
                        <li class="mb-2"><a href="pricing.php" class="text-muted text-decoration-none">Pricing</a></li>
                        <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-4">Support</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="faq.php" class="text-muted text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li class="mb-2"><a href="help.php" class="text-muted text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-muted text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-4">
                    <h5 class="mb-4">Contact Us</h5>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-3">
                            <i class="bi bi-geo-alt me-2"></i>
                            Strathmore University, Ole Sangale Road, Nairobi
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-envelope me-2"></i>
                            <a href="mailto:support@strathmoretutoring.com" class="text-muted text-decoration-none">
                                support@strathmoretutoring.com
                            </a>
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-telephone me-2"></i>
                            <a href="tel:+254700000000" class="text-muted text-decoration-none">
                                +254 700 000 000
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <!-- Copyright -->
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Strathmore Peer Tutoring. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-muted">
                        <a href="privacy.php" class="text-muted text-decoration-none me-3">Privacy Policy</a>
                        <a href="terms.php" class="text-muted text-decoration-none">Terms of Service</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary back-to-top" title="Go to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Back to top button functionality
        const backToTopButton = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html> 