document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signup-form');
    if (!form) return;

    // Input elements
    const firstName = document.getElementById('first-name');
    const lastName = document.getElementById('last-name');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const genderRadios = document.querySelectorAll('input[name="gender"]');
    const department = document.getElementById('department');
    const mobile = document.getElementById('mobile');
    const institute = document.getElementById('institute');
    const academicYear = document.getElementById('academic-year');
    const password = document.getElementById('password');

    // Error message elements
    const firstNameErr = document.getElementById('first-name-error');
    const lastNameErr = document.getElementById('last-name-error');
    const usernameErr = document.getElementById('username-error');
    const emailErr = document.getElementById('email-error');
    const genderErr = document.getElementById('gender-error');
    const departmentErr = document.getElementById('department-error');
    const mobileErr = document.getElementById('mobile-error');
    const instituteErr = document.getElementById('institute-error');
    const academicYearErr = document.getElementById('academic-year-error');
    const passwordErr = document.getElementById('password-error');

    // Helper functions to show/hide errors
    function showError(input, errorElement) {
        if (input) input.classList.add('input-error');
        if (errorElement) errorElement.classList.add('show');
    }

    function clearError(input, errorElement) {
        if (input) input.classList.remove('input-error');
        if (errorElement) errorElement.classList.remove('show');
    }

    // Auto-clear error when user types or changes an input
    const inputsWithErrors = [
        { input: firstName, error: firstNameErr },
        { input: lastName, error: lastNameErr },
        { input: username, error: usernameErr },
        { input: email, error: emailErr },
        { input: department, error: departmentErr },
        { input: mobile, error: mobileErr },
        { input: institute, error: instituteErr },
        { input: academicYear, error: academicYearErr },
        { input: password, error: passwordErr }
    ];

    inputsWithErrors.forEach(function (item) {
        if (item.input) {
            item.input.addEventListener('input', function () {
                clearError(item.input, item.error);
            });
            item.input.addEventListener('change', function () {
                clearError(item.input, item.error);
            });
        }
    });

    genderRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (genderErr) genderErr.classList.remove('show');
        });
    });

    // Form submission validation
    form.addEventListener('submit', function (e) {
        let isValid = true;
        let firstInvalidField = null;

        // 1. First Name Validation (letters and spaces only)
        const namePattern = /^[a-zA-Z\s]+$/;
        if (!firstName.value.trim() || !namePattern.test(firstName.value.trim())) {
            showError(firstName, firstNameErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = firstName;
        } else {
            clearError(firstName, firstNameErr);
        }

        // 2. Last Name Validation (letters and spaces only)
        if (!lastName.value.trim() || !namePattern.test(lastName.value.trim())) {
            showError(lastName, lastNameErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = lastName;
        } else {
            clearError(lastName, lastNameErr);
        }

        // 3. Username Validation (alphanumeric and underscore, 3-30 chars)
        const usernamePattern = /^[a-zA-Z0-9_]{3,30}$/;
        if (!username.value.trim() || !usernamePattern.test(username.value.trim())) {
            showError(username, usernameErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = username;
        } else {
            clearError(username, usernameErr);
        }

        // 4. Email Validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailPattern.test(email.value.trim())) {
            showError(email, emailErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = email;
        } else {
            clearError(email, emailErr);
        }

        // 5. Gender Validation
        let genderSelected = false;
        genderRadios.forEach(function (radio) {
            if (radio.checked) genderSelected = true;
        });
        if (!genderSelected) {
            if (genderErr) genderErr.classList.add('show');
            isValid = false;
            if (!firstInvalidField && genderRadios.length > 0) firstInvalidField = genderRadios[0];
        } else {
            if (genderErr) genderErr.classList.remove('show');
        }

        // 6. Department Validation
        if (!department.value) {
            showError(department, departmentErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = department;
        } else {
            clearError(department, departmentErr);
        }

        // 7. Mobile Number Validation (exactly 10 digits)
        const mobilePattern = /^\d{10}$/;
        if (!mobile.value.trim() || !mobilePattern.test(mobile.value.trim())) {
            showError(mobile, mobileErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = mobile;
        } else {
            clearError(mobile, mobileErr);
        }

        // 8. Institute Validation
        if (!institute.value) {
            showError(institute, instituteErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = institute;
        } else {
            clearError(institute, instituteErr);
        }

        // 9. Academic Year Validation
        if (!academicYear.value) {
            showError(academicYear, academicYearErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = academicYear;
        } else {
            clearError(academicYear, academicYearErr);
        }

        // 10. Password Validation (min 6 characters)
        if (!password.value || password.value.length < 6) {
            showError(password, passwordErr);
            isValid = false;
            if (!firstInvalidField) firstInvalidField = password;
        } else {
            clearError(password, passwordErr);
        }

        // If any validation failed, stop form submission and focus first invalid input
        if (!isValid) {
            e.preventDefault();
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }
        // If valid, the browser naturally submits the POST request to register.php!
    });
});
