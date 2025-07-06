<?php
require_once 'config/config.php';

$page_title = 'Home';

// Get featured tutors
$db = new Database();
$featured_tutors_query = $db->executeQuery(
    "SELECT u.id, u.first_name, u.last_name, tp.rating, tp.hourly_rate, tp.profile_picture,
            CONCAT(u.first_name, ' ', u.last_name) as full_name
     FROM users u 
     JOIN tutor_profiles tp ON u.id = tp.user_id 
     WHERE tp.is_approved = 1 AND u.role = 'tutor'
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

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        background-image: 
            linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%),
            url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23764ba2' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        color: #333;
        line-height: 1.6;
        position: relative;
    }
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at top right, rgba(118, 75, 162, 0.03) 0%, transparent 60%),
                    radial-gradient(circle at bottom left, rgba(102, 126, 234, 0.03) 0%, transparent 60%);
        pointer-events: none;
        z-index: 0;
    }
    .container {
        position: relative;
        z-index: 1;
    }
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1000;
    }
    .navbar-brand {
        font-weight: 600;
        color: #764ba2;
    }
    .nav-link {
        color: #4a5568;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    .nav-link:hover {
        color: #764ba2;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .btn-outline-primary {
        color: #764ba2;
        border-color: #764ba2;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 120px 0;
        position: relative;
        overflow: hidden;
    }
    .hero-content {
        min-height: 600px;
        display: flex;
        align-items: center;
    }
    .hero-text h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    .hero-text .lead {
        font-size: 1.4rem;
        margin-bottom: 2rem;
        line-height: 1.6;
        opacity: 0.95;
    }
    .hero-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }
    .hero-buttons .btn {
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    .hero-buttons .btn-primary {
        background: #ffffff;
        color: #667eea;
        border: 2px solid #ffffff;
    }
    .hero-buttons .btn-primary:hover {
        background: transparent;
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .hero-buttons .btn-outline-light {
        border: 2px solid rgba(255,255,255,0.8);
        color: #ffffff;
        background: transparent;
    }
    .hero-buttons .btn-outline-light:hover {
        background: #ffffff;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .hero-stats {
        display: flex;
        gap: 2rem;
        margin-top: 2rem;
    }
    .hero-stat {
        text-align: center;
    }
    .hero-stat .number {
        font-size: 2.5rem;
        font-weight: 700;
        display: block;
        line-height: 1;
    }
    .hero-stat .label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.1;
    }
    .hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .hero .lead {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    .hero-image {
        position: relative;
        z-index: 1;
    }
    .hero-image img {
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .hero-carousel {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .carousel-item img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 20px;
    }
    .carousel-caption {
        background: rgba(0, 0, 0, 0.6);
        border-radius: 10px;
        padding: 15px;
        bottom: 20px;
        left: 20px;
        right: 20px;
        text-align: left;
    }
    .carousel-caption h5 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .carousel-caption p {
        font-size: 0.9rem;
        margin-bottom: 0;
        opacity: 0.9;
    }
    .carousel-indicators {
        bottom: 10px;
    }
    .carousel-indicators button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        border: 2px solid rgba(255, 255, 255, 0.8);
        margin: 0 5px;
    }
    .carousel-indicators button.active {
        background-color: #ffffff;
        border-color: #ffffff;
    }
    .carousel-control-prev,
    .carousel-control-next {
        width: 10%;
        opacity: 0.8;
    }
    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        opacity: 1;
    }
    .features-section {
        padding: 80px 0;
        position: relative;
        z-index: 1;
    }
    .features-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(to bottom, rgba(118, 75, 162, 0.02), transparent);
        pointer-events: none;
    }
    .feature-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(118, 75, 162, 0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(118, 75, 162, 0.1);
        position: relative;
        overflow: hidden;
    }
    .feature-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(118, 75, 162, 0.03) 0%, transparent 100%);
        pointer-events: none;
    }
    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(118, 75, 162, 0.15);
    }
    .feature-card h3 {
        color: #764ba2;
        font-weight: 600;
        margin-bottom: 15px;
        position: relative;
        padding-bottom: 15px;
    }
    .feature-card h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
    }
    .feature-card p {
        color: #4a5568;
        margin-bottom: 0;
    }
    .feature-icon {
        font-size: 2.5rem;
        color: #764ba2;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .cta-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border-radius: 20px;
        padding: 60px 40px;
        margin: 40px 0;
        text-align: center;
        box-shadow: 0 10px 30px rgba(118, 75, 162, 0.2);
        position: relative;
        overflow: hidden;
    }
    .cta-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgba(255,255,255,0.1)"/></svg>');
        opacity: 0.1;
    }
    .cta-section h2 {
        color: #ffffff;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    .cta-section .btn {
        padding: 12px 30px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    footer {
        background: #000000;
        color: #ffffff;
        padding: 60px 0 20px;
        margin-top: 40px;
        position: relative;
        z-index: 1;
    }
    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(to top, rgba(118, 75, 162, 0.1), transparent);
        pointer-events: none;
    }
    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }
    .footer-section h4 {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }
    .footer-section h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
    }
    .footer-section p {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-links li {
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.8);
    }
    .footer-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
        position: relative;
    }
    .footer-links a:hover {
        color: #764ba2;
        transform: translateX(5px);
    }
    .footer-links i {
        color: #764ba2;
        margin-right: 10px;
    }
    .footer-bottom {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.6);
    }
    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 15px;
    }
    .social-links a {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.2rem;
        transition: all 0.3s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
    .social-links a:hover {
        color: #ffffff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transform: translateY(-3px);
    }
    .tutor-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(118, 75, 162, 0.1);
        transition: all 0.3s ease;
    }
    .tutor-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(118, 75, 162, 0.15);
    }
    .profile-picture {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #764ba2;
        padding: 3px;
        background: white;
    }
    .rating {
        color: #764ba2;
    }
    .rating i {
        margin: 0 2px;
    }
    .subject-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(118, 75, 162, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .subject-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .subject-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(118, 75, 162, 0.15);
    }
    .subject-card i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .step-number {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 auto 20px;
        box-shadow: 0 10px 20px rgba(118, 75, 162, 0.2);
    }
    .testimonial-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(118, 75, 162, 0.1);
        transition: all 0.3s ease;
    }
    .testimonial-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(118, 75, 162, 0.15);
    }
    .testimonial-card .profile-picture {
        width: 60px;
        height: 60px;
    }
    .section-title {
        color: #764ba2;
        font-weight: 700;
        margin-bottom: 3rem;
        position: relative;
        padding-bottom: 15px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
    }
    .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
        position: relative;
    }
    .bg-light::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23764ba2' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    .how-it-works-section {
        padding: 100px 0;
        background: #f8f9fa;
    }
    .section-subtitle {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 3rem;
    }
    .step-card {
        background: white;
        padding: 2rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        height: 100%;
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .step-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    .step-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2rem;
    }
    .step-card h4 {
        color: #333;
        font-weight: 600;
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }
    .step-card p {
        color: #6c757d;
        line-height: 1.6;
        margin-bottom: 0;
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center hero-content">
            <div class="col-lg-6">
                <div class="hero-text">
                    <h1>Connect, Learn & Grow Together</h1>
                    <p class="lead">Strathmore's premier tutoring platform connecting students with experienced tutors and student tutors. Whether you're seeking academic support or want to share your knowledge, we've got you covered.</p>
                    
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-primary">Get Started Today</a>
                        <a href="#how-it-works" class="btn btn-outline-light">Learn More</a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="number">500+</span>
                            <span class="label">Students</span>
                        </div>
                        <div class="hero-stat">
                            <span class="number">100+</span>
                            <span class="label">Tutors</span>
                        </div>
                        <div class="hero-stat">
                            <span class="number">50+</span>
                            <span class="label">Subjects</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="4000">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?auto=format&fit=crop&w=1000&q=80" 
                                     alt="Black students in classroom">
                                <div class="carousel-caption">
                                    <h5>Peer-to-Peer Learning</h5>
                                    <p>Students and student tutors working together for academic success</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=1000&q=80" 
                                     alt="Black student writing in class">
                                <div class="carousel-caption">
                                    <h5>Expert & Student Tutors</h5>
                                    <p>Learn from experienced professionals and fellow students</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=1000&q=80" 
                                     alt="Black students in school hallway">
                                <div class="carousel-caption">
                                    <h5>Flexible Learning</h5>
                                    <p>Study at your own pace with modern technology</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1000&q=80" 
                                     alt="Teacher with Black students in classroom">
                                <div class="carousel-caption">
                                    <h5>Community Learning</h5>
                                    <p>Join study groups and collaborative sessions</p>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <h2 class="section-title text-center">Why Choose Our Platform</h2>
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

<!-- Payment & Security Section -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center">Secure & Convenient Payments</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="feature-card">
                    <i class="bi bi-credit-card feature-icon"></i>
                    <h3>M-Pesa Integration</h3>
                    <p>Secure payments through Kenya's most trusted mobile money platform. Instant payment confirmation and transaction tracking.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card">
                    <i class="bi bi-shield-lock feature-icon"></i>
                    <h3>Data Protection</h3>
                    <p>Your personal information and payment details are encrypted and protected with industry-standard security measures.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tutors Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">Featured Tutors</h2>
        <div class="row">
            <?php if (!empty($featured_tutors)): ?>
            <?php foreach ($featured_tutors as $tutor): ?>
                <div class="col-md-4 mb-4">
                    <div class="card tutor-card">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                    <?php if (!empty($tutor['profile_picture'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo htmlspecialchars($tutor['profile_picture']); ?>" 
                                             alt="<?php echo htmlspecialchars($tutor['full_name']); ?>" 
                                             class="profile-picture mb-2"
                                             onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'">
                                    <?php else: ?>
                                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $tutor['id']; ?>.jpg" 
                                             alt="<?php echo htmlspecialchars($tutor['full_name']); ?>" 
                                             class="profile-picture mb-2"
                                             onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'">
                                    <?php endif; ?>
                                    <h5 class="card-title"><?php echo htmlspecialchars($tutor['full_name']); ?></h5>
                                <div class="rating mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= ($tutor['rating'] ?? 0) ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                        <span class="ms-2 text-muted">(<?php echo number_format($tutor['rating'] ?? 0, 1); ?>)</span>
                                </div>
                                    <p class="text-muted">KES <?php echo number_format($tutor['hourly_rate'] ?? 0, 2); ?>/hour</p>
                            </div>
                                <a href="<?php echo APP_URL; ?>/tutee/tutors.php" 
                               class="btn btn-outline-primary w-100">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No featured tutors available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo APP_URL; ?>/auth/register.php" class="btn btn-primary">Join as Student</a>
            <a href="<?php echo APP_URL; ?>/auth/register.php?role=tutor" class="btn btn-outline-primary ms-2">Become a Tutor</a>
        </div>
    </div>
</section>

<!-- Popular Subjects -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">Popular Subjects</h2>
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
<section id="how-it-works" class="how-it-works-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Simple steps to connect students with tutors and student tutors</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="step-card text-center">
                    <div class="step-icon">
                        <i class="bi bi-person-plus"></i>
                </div>
                    <h4>1. Sign Up</h4>
                    <p>Register as a student seeking help or as a tutor/student tutor offering your expertise</p>
            </div>
                </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card text-center">
                    <div class="step-icon">
                        <i class="bi bi-search"></i>
            </div>
                    <h4>2. Find Your Match</h4>
                    <p>Browse tutors by subject, rating, and availability. Students can find both expert tutors and student tutors</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card text-center">
                    <div class="step-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h4>3. Book Sessions</h4>
                    <p>Schedule one-on-one or group sessions at convenient times for both parties</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card text-center">
                    <div class="step-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <h4>4. Learn & Grow</h4>
                    <p>Attend sessions, track progress, and leave reviews to help the community</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="auth/register.php" class="btn btn-primary btn-lg">Join Our Community Today</a>
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
                                <h5 class="mb-0">Alysa Gathoni</h5>
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
                                <h5 class="mb-0">Nicole Njeri</h5>
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
                                <h5 class="mb-0">Ilhan Hamud</h5>
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

<!-- Footer -->
<footer class="mt-5">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>About Us</h4>
                <p>Connecting students with qualified tutors for personalized learning experiences.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact Info</h4>
                <ul class="footer-links">
                    <li><i class="bi bi-envelope"></i> support@strathmore.edu</li>
                    <li><i class="bi bi-telephone"></i> +254 700 000 000</li>
                    <li><i class="bi bi-geo-alt"></i> Strathmore University, Nairobi</li>
                    <li><i class="bi bi-clock"></i> Mon-Fri: 8AM-6PM</li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
