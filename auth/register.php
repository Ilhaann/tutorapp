<?php
// Start output buffering
ob_start();

require_once __DIR__ . '/../app/Config/SendMail.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use app\Config\SendMail;

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];
        $verification_code = bin2hex(random_bytes(16));
        $student_id = trim($_POST['student_id'] ?? '');
        $year_of_study = intval($_POST['year_of_study'] ?? 1);
        $course = trim($_POST['course'] ?? '');

        // Validate role
        if (!in_array($role, ['tutor', 'tutee'])) {
            throw new Exception("Invalid role selected");
        }

        // Validate email (strathmore.edu only)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        } elseif (!preg_match('/@strathmore\.edu$/', $email)) {
            throw new Exception("Only Strathmore University email addresses are allowed.");
        }

        // Validate password strength
        $password_strength = 0;
        if (strlen($password) >= 8) $password_strength++;
        if (preg_match('/[A-Z]/', $password)) $password_strength++;
        if (preg_match('/[a-z]/', $password)) $password_strength++;
        if (preg_match('/[0-9]/', $password)) $password_strength++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $password_strength++;

        if ($password_strength < 3) {
            throw new Exception("Password must be at least medium strength. Include uppercase, lowercase, numbers, and special characters.");
        }

        // Validate other fields
        if (empty($first_name)) throw new Exception("First name is required.");
        if (empty($last_name)) throw new Exception("Last name is required.");
        if (empty($student_id)) throw new Exception("Student ID is required.");
        if ($year_of_study < 1 || $year_of_study > 4) throw new Exception("Year of study must be between 1 and 4.");
        if (empty($course)) throw new Exception("Course selection is required.");
        if ($password !== $confirm_password) throw new Exception("Passwords do not match.");

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("This email is already registered.");
        }

        // Check if student ID already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("This student ID is already registered.");
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Insert new user
            if ($role === 'tutee') {
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, verification_token, student_id, year_of_study, course) VALUES (?, ?, ?, ?, 'tutee', ?, ?, ?, ?)");
                $stmt->execute([$first_name, $last_name, $email, password_hash($password, PASSWORD_DEFAULT), $verification_code, $student_id, $year_of_study, $course]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, verification_token) VALUES (?, ?, ?, ?, 'tutor', ?)");
                $stmt->execute([$first_name, $last_name, $email, password_hash($password, PASSWORD_DEFAULT), $verification_code]);
            }

            // Generate verification code
            $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Update user with verification code
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE email = ?");
            $stmt->execute([$verification_code, $email]);

            // Store email in session for verification
            $_SESSION['temp_email'] = $email;

            // Send verification email
            $verification_link = APP_URL . "/auth/verify.php";
            $email_subject = "Verify Your Account - Strathmore Peer Tutoring";
            $email_body = "Dear " . $first_name . ",\n\n";
            $email_body .= "Thank you for registering with Strathmore Peer Tutoring. Please use the following verification code to complete your registration:\n\n";
            $email_body .= "Verification Code: " . $verification_code . "\n\n";
            $email_body .= "Enter this code at: " . $verification_link . "\n\n";
            $email_body .= "If you did not request this verification, please ignore this email.\n\n";
            $email_body .= "Best regards,\nStrathmore Peer Tutoring Team";

            // Log email details
            error_log("=== Registration Email Debug ===");
            error_log("Email: " . $email);
            error_log("Verification code: " . $verification_code);
            error_log("Email subject: " . $email_subject);
            error_log("Email body: " . $email_body);
            error_log("Verification link: " . $verification_link);

            // Generate verification code and send email
            $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE email = ?");
            $stmt->execute([$verification_code, $email]);

            $_SESSION['temp_email'] = $email;

            // Send email
            $mailer = new SendMail();
            $mail_result = $mailer->send($email, $first_name, "Verification Code: $verification_code");

            if (!$mail_result) {
                throw new Exception("Failed to send verification email.");
            }

            error_log("Verification email sent successfully to: " . $email);
            error_log("=== End Registration Email Debug ===");

            // Commit transaction
            $pdo->commit();

            // Set success message
            $_SESSION['success_message'] = "Registration successful! Please check your email for verification.";
            
            // Clear output buffer
            ob_end_clean();
            
            // Redirect to verification page
            header("Location: " . APP_URL . "/auth/verify.php");
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Only include the header and start HTML output if we haven't redirected
if (!headers_sent()) {
    $page_title = "Register";
    require_once '../includes/header.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f6f8fc 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .register-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .register-header {
            background: #2c3e50;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .register-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .register-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        .register-form {
            padding: 2rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .form-floating input, .form-floating select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 1rem 0.75rem;
            transition: all 0.3s ease;
        }
        .form-floating input:focus, .form-floating select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.15);
        }
        .password-strength {
            margin-top: 0.5rem;
        }
        .progress {
            height: 5px;
            margin-bottom: 0.25rem;
        }
        .btn-register {
            background: #2c3e50;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.2);
        }
        .register-footer {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .role-selector {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .role-option:hover {
            border-color: #3498db;
            transform: translateY(-2px);
        }
        .role-option.selected {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.05);
        }
        .role-option i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .role-option h5 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .role-option p {
            color: #6c757d;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Create Your Account</h1>
                <p>Join our tutoring community today</p>
            </div>
            
            <div class="register-form">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                <label for="first_name">First Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                <label for="last_name">Last Name</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <label for="email">Strathmore Email</label>
                        <div class="form-text">Only @strathmore.edu emails are allowed</div>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                        <label for="student_id">Student ID</label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == $i) ? 'selected' : ''; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <label for="year_of_study">Year of Study</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="course" name="course" required>
                                    <option value="">Select Course</option>
                                    <option value="BTM" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BTM') ? 'selected' : ''; ?>>Bachelor of Science in Tourism Management (BTM)</option>
                                    <option value="BHM" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BHM') ? 'selected' : ''; ?>>Bachelor of Science in Hospitality Management (BHM)</option>
                                    <option value="BBSFENG" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BBSFENG') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Engineering (BBSFENG)</option>
                                    <option value="BBSFE" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BBSFE') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Economics (BBSFE)</option>
                                    <option value="BBSACT" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BBSACT') ? 'selected' : ''; ?>>Bachelor of Business Science: Actuarial Science (BBSACT)</option>
                                    <option value="BICS" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BICS') ? 'selected' : ''; ?>>Bachelor Of Science In Informatics And Computer Science (BICS)</option>
                                    <option value="BBIT" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BBIT') ? 'selected' : ''; ?>>Bachelor Of Business Information Technology (BBIT)</option>
                                    <option value="BCNS" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BCNS') ? 'selected' : ''; ?>>BSc. Computer Networks and Cyber Security (BCNS)</option>
                                    <option value="LLB" <?php echo (isset($_POST['course']) && $_POST['course'] == 'LLB') ? 'selected' : ''; ?>>Bachelor of Laws (LLB)</option>
                                    <option value="BAC" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BAC') ? 'selected' : ''; ?>>Bachelor of Arts in Communication (BAC)</option>
                                    <option value="BAIS" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BAIS') ? 'selected' : ''; ?>>Bachelor of Arts in International Studies</option>
                                    <option value="BDP" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BDP') ? 'selected' : ''; ?>>Bachelor of Arts in Development Studies and Philosophy (BDP)</option>
                                    <option value="BSCM" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BSCM') ? 'selected' : ''; ?>>Bachelor of Science in Supply Chain and Operations Management (BSCM)</option>
                                    <option value="BFS" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BFS') ? 'selected' : ''; ?>>Bachelor of Financial Services (BFS)</option>
                                    <option value="BSEEE" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BSEEE') ? 'selected' : ''; ?>>Bachelor Of Science In Electrical and Electronics Engineering (BSEEE)</option>
                                    <option value="BScSDS" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BScSDS') ? 'selected' : ''; ?>>BSc in Statistics and Data Science (BScSDS)</option>
                                    <option value="BCOM" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BCOM') ? 'selected' : ''; ?>>Bachelor of Commerce (BCOM)</option>
                                </select>
                                <label for="course">Course</label>
                            </div>
                        </div>
                    </div>

                    <div class="role-selector">
                        <div class="role-option" data-role="tutee">
                            <i class="bi bi-mortarboard"></i>
                            <h5>Student</h5>
                            <p>Looking for a tutor</p>
                        </div>
                        <div class="role-option" data-role="tutor">
                            <i class="bi bi-person-workspace"></i>
                            <h5>Tutor</h5>
                            <p>Want to teach</p>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="selected_role" value="<?php echo htmlspecialchars($_POST['role'] ?? ''); ?>">

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <label for="password">Password</label>
                        <div class="password-strength mt-2">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">Password strength: <span id="strength-text">None</span></small>
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <label for="confirm_password">Confirm Password</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-register">Create Account</button>
                    </div>
                </form>
            </div>

            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Role selector functionality
        const roleOptions = document.querySelectorAll('.role-option');
        const selectedRoleInput = document.getElementById('selected_role');
        
        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                roleOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedRoleInput.value = this.dataset.role;
            });
            
            // Set initial selected state
            if (option.dataset.role === selectedRoleInput.value) {
                option.classList.add('selected');
            }
        });

        // Password strength checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const progressBar = document.querySelector('.progress-bar');
        const strengthText = document.getElementById('strength-text');

        function checkPasswordStrength() {
            let strength = 0;
            const value = password.value;

            if (value.length >= 8) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[a-z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++;

            const width = (strength / 5) * 100;
            progressBar.style.width = width + '%';

            let color = 'bg-danger';
            let text = 'Weak';
            
            if (strength >= 4) {
                color = 'bg-success';
                text = 'Strong';
            } else if (strength >= 3) {
                color = 'bg-warning';
                text = 'Medium';
            }

            progressBar.className = 'progress-bar ' + color;
            strengthText.textContent = text;
        }

        password.addEventListener('input', checkPasswordStrength);

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>
