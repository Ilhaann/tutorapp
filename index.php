<?php
require_once 'config/config.php';

$page_title = 'Home';

// Get featured tutors
$db = new Database();
$featured_tutors_query = $db->executeQuery(
    "SELECT u.*, tp.rating, tp.hourly_rate 
     FROM users u 
     JOIN tutor_profiles tp ON u.id = tp.user_id 
     WHERE tp.is_approved = 1 
     ORDER BY tp.rating DESC 
     LIMIT 6"
);
$featured_tutors = $featured_tutors_query ? $featured_tutors_query->fetchAll(PDO::FETCH_ASSOC) : [];

// Get popular units
$popular_units_query = $db->executeQuery(
    "SELECT u.*, COUNT(tu.tutor_id) as tutor_count 
     FROM units u 
     JOIN tutor_units tu ON u.id = tu.unit_id 
     GROUP BY u.id 
     ORDER BY tutor_count DESC 
     LIMIT 6"
);
$popular_units = $popular_units_query ? $popular_units_query->fetchAll(PDO::FETCH_ASSOC) : [];

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1>Find Your Perfect Tutor</h1>
                <p class="lead">Connect with experienced tutors from Strathmore University for personalized learning experiences.</p>
                <div class="d-flex gap-3">
                    <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="#how-it-works" class="btn btn-outline-light btn-lg">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="assets/images/hero-placeholder.jpg" alt="Tutoring Session" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Our Platform</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-shield-check"></i>
                    <h3>Verified Tutors</h3>
                    <p>All tutors are verified Strathmore students with proven academic excellence.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-calendar-check"></i>
                    <h3>Flexible Scheduling</h3>
                    <p>Book sessions at your convenience with our easy-to-use scheduling system.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-star-fill"></i>
                    <h3>Quality Guaranteed</h3>
                    <p>Rate and review tutors to maintain high standards of teaching.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tutors Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Featured Tutors</h2>
        <div class="row">
            <?php foreach ($featured_tutors as $tutor): ?>
                <div class="col-md-4 mb-4">
                    <div class="card tutor-card">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="<?php echo $tutor['profile_picture'] ?? APP_URL . '/assets/images/default-avatar.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($tutor['full_name'] ?? 'Tutor Profile'); ?>" 
                                     class="profile-picture mb-2">
                                <h5 class="card-title"><?php echo htmlspecialchars($tutor['full_name'] ?? 'Tutor Profile'); ?></h5>
                                <div class="rating mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $tutor['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted">KES <?php echo number_format($tutor['hourly_rate'], 2); ?>/hour</p>
                            </div>
                            <a href="<?php echo APP_URL; ?>/tutor/profile.php?id=<?php echo $tutor['id']; ?>" 
                               class="btn btn-outline-primary w-100">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo APP_URL; ?>/tutors.php" class="btn btn-primary">View All Tutors</a>
        </div>
    </div>
</section>

<!-- Popular Subjects -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Popular Subjects</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="subject-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-calculator display-4 mb-3 text-primary"></i>
                        <h5 class="card-title">Mathematics</h5>
                        <p class="card-text">From basic algebra to advanced calculus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="subject-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-code-slash display-4 mb-3 text-primary"></i>
                        <h5 class="card-title">Programming</h5>
                        <p class="card-text">Learn various programming languages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="subject-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-graph-up display-4 mb-3 text-primary"></i>
                        <h5 class="card-title">Statistics</h5>
                        <p class="card-text">Master data analysis and probability</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="subject-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-cash-stack display-4 mb-3 text-primary"></i>
                        <h5 class="card-title">Finance</h5>
                        <p class="card-text">Understand financial concepts and analysis</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="step-number">1</div>
                    <h4>Create Account</h4>
                    <p>Sign up with your Strathmore email and student ID</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="step-number">2</div>
                    <h4>Find a Tutor</h4>
                    <p>Browse through verified tutors and their specialties</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="step-number">3</div>
                    <h4>Start Learning</h4>
                    <p>Book sessions and begin your learning journey</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">What Students Say</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial1.jpg" alt="Student" class="profile-picture me-3">
                            <div>
                                <h5 class="mb-0">John Doe</h5>
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"The platform helped me find an excellent tutor for my statistics course. The sessions were well-structured and very helpful."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial2.jpg" alt="Student" class="profile-picture me-3">
                            <div>
                                <h5 class="mb-0">Jane Smith</h5>
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"I was struggling with programming concepts, but my tutor made everything clear. The flexible scheduling was a huge plus!"</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial3.jpg" alt="Student" class="profile-picture me-3">
                            <div>
                                <h5 class="mb-0">Mike Johnson</h5>
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"The quality of tutors on this platform is exceptional. I improved my grades significantly after just a few sessions."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Start Learning?</h2>
        <p class="lead mb-4">Join our community of learners and tutors today.</p>
        <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started Now</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
