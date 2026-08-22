<?php
require_once 'config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $login_role = sanitize_input($_POST['login_role']);
    
    if (empty($login_role)) {
        $login_error = "Please select a role";
    } elseif (login_user($username, $password, $login_role)) {
        redirect(get_role_home());
    } else {
        $login_error = "Invalid credentials or role mismatch";
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = sanitize_input($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];
    $full_name = sanitize_input($_POST['reg_full_name']);
    $account_type = sanitize_input($_POST['account_type']);
    
    if ($password !== $confirm_password) {
        $register_error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $register_error = "Password must be at least 6 characters";
    } else {
        if (register_user($email, $email, $password, $full_name, $account_type)) {
            $register_success = "Registration successful! Please login.";
        } else {
            $register_error = "Email already exists";
        }
    }
}

// Redirect if already logged in
if (is_logged_in()) {
    redirect(get_role_home());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .auth-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            color: #666;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .role-selection {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .role-btn {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .role-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .role-btn.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .role-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        .role-btn .role-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-flask"></i> MCPIL
                </div>
                <div class="subtitle">Pharmaceutical Laboratory Management System</div>
            </div>
            
            <div class="auth-body">
                <ul class="nav nav-tabs mb-4" id="authTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="authTabContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $login_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tag"></i> Select Role
                                </label>
                                <input type="hidden" id="login_role" name="login_role" required>
                                <div class="role-selection">
                                    <div class="role-btn" data-role="admin">
                                        <i class="fas fa-shield-alt"></i>
                                        <div class="role-title">Admin</div>
                                    </div>
                                    <div class="role-btn" data-role="employee">
                                        <i class="fas fa-user"></i>
                                        <div class="role-title">Employee</div>
                                    </div>
                                    <div class="role-btn" data-role="store">
                                        <i class="fas fa-store"></i>
                                        <div class="role-title">Store</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                    </div>
                    
                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <?php if (isset($register_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $register_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($register_success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $register_success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tag"></i> Select Role
                                </label>
                                <input type="hidden" id="account_type" name="account_type" required>
                                <div class="role-selection">
                                    <div class="role-btn" data-role="admin">
                                        <i class="fas fa-shield-alt"></i>
                                        <div class="role-title">Admin</div>
                                    </div>
                                    <div class="role-btn" data-role="employee">
                                        <i class="fas fa-user"></i>
                                        <div class="role-title">Employee</div>
                                    </div>
                                    <div class="role-btn" data-role="store">
                                        <i class="fas fa-store"></i>
                                        <div class="role-title">Store</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_full_name" class="form-label">
                                    <i class="fas fa-id-card"></i> Full Name
                                </label>
                                <input type="text" class="form-control" id="reg_full_name" name="reg_full_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="reg_email" name="reg_email" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reg_password" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="reg_confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> Confirm Password
                                    </label>
                                    <input type="password" class="form-control" id="reg_confirm_password" name="reg_confirm_password" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="register" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Role selection functionality
document.addEventListener('DOMContentLoaded', function() {
    const roleButtons = document.querySelectorAll('.role-btn');
    const accountTypeInput = document.getElementById('account_type');
    const loginRoleInput = document.getElementById('login_role');
    const emailInput = document.getElementById('reg_email');
    const loginEmailInput = document.getElementById('username');
    
    roleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the parent form to determine which role input to update
            const parentForm = this.closest('form');
            const isLoginForm = parentForm.querySelector('#login_role');
            
            // Remove selected class from all buttons in the same form
            const formRoleButtons = parentForm.querySelectorAll('.role-btn');
            formRoleButtons.forEach(btn => btn.classList.remove('selected'));
            
            // Add selected class to clicked button
            this.classList.add('selected');
            
            // Set the appropriate hidden input value
            const selectedRole = this.getAttribute('data-role');
            if (isLoginForm) {
                loginRoleInput.value = selectedRole;
                updateLoginEmailPlaceholder(selectedRole);
            } else {
                accountTypeInput.value = selectedRole;
                updateEmailPlaceholder(selectedRole);
            }
        });
    });
    
    function updateEmailPlaceholder(role) {
        const roleText = role.charAt(0).toUpperCase() + role.slice(1);
        emailInput.placeholder = `Enter your ${roleText.toLowerCase()} email address`;
    }
    
    function updateLoginEmailPlaceholder(role) {
        const roleText = role.charAt(0).toUpperCase() + role.slice(1);
        loginEmailInput.placeholder = `Enter your ${roleText.toLowerCase()} email address`;
    }
    
    // Form validation for both forms
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const roleInput = form.querySelector('#login_role, #account_type');
            if (roleInput && !roleInput.value) {
                e.preventDefault();
                alert('Please select a role');
                return false;
            }
        });
    });
});
</script>
</body>
</html>
