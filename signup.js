    // Your JavaScript with modifications
    const form = document.getElementById('signupForm');
    const errorDiv = document.getElementById('error');
    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        // Clear errors
        errorDiv.textContent = '';
        usernameError.textContent = '';
        emailError.textContent = '';
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';

        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        let hasError = false;

        // Validation
        if (!username) {
            usernameError.textContent = 'Please enter a username';
            hasError = true;
        }

        if (!email) {
            emailError.textContent = 'Please enter your email address';
            hasError = true;
        } else if (!validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email address';
            hasError = true;
        }

        if (!password) {
            passwordError.textContent = 'Please enter a password';
            hasError = true;
        } else if (password.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters';
            hasError = true;
        }

        if (!confirmPassword) {
            confirmPasswordError.textContent = 'Please confirm your password';
            hasError = true;
        } else if (password !== confirmPassword) {
            confirmPasswordError.textContent = 'Passwords do not match';
            hasError = true;
        }

        if (!hasError) {
            // Submit the form if validation passes
            form.submit();
        } else {
            errorDiv.classList.add('text-danger');
        }
    });