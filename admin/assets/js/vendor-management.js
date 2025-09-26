document.addEventListener('DOMContentLoaded', function () {
    // --- Universal Snackbox Function (Vanilla JS) ---
    function showSnackbox(message, type) {
        const snackbox = document.getElementById("snackbox");
        if (!snackbox) {
            console.error('Snackbox element not found!');
            return;
        }
        snackbox.textContent = message;
        snackbox.className = "show " + type;
        setTimeout(function () {
            snackbox.className = snackbox.className.replace("show", "");
        }, 3000);
    }

    // --- Debounce function to limit API calls on blur ---
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    // --- jQuery-dependent logic (Stock Update) ---
    $(document).ready(function () {
        $('#product-select').change(function () {
            var selectedOption = $(this).find(':selected');
            var currentStock = selectedOption.data('stock');
            if (currentStock !== undefined) {
                $('#stock-count').val(currentStock);
            } else {
                $('#stock-count').val('');
            }
        });
        $('#save-changes-btn').click(function (e) {
            e.preventDefault();
            var product_id = $('#product-select').val();
            var new_stock = $('#stock-count').val();
            if (product_id === null || new_stock === '' || isNaN(new_stock)) {
                showSnackbox('Please select a product and enter a valid stock count.', 'error');
                return;
            }
            $.ajax({
                url: updateStockUrl,
                type: "POST",
                data: { product_id: product_id, new_stock: new_stock },
                dataType: "json",
                success: function (response) {
                    if (response.status === 'success') {
                        showSnackbox(response.message, 'success');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        showSnackbox('Error: ' + response.message, 'error');
                    }
                },
                error: function () {
                    showSnackbox('An error occurred. Please try again.', 'error');
                }
            });
        });
    });

    // --- Vanilla JS-dependent logic (Vendor Form) ---
    const form = document.getElementById('addVendorForm');
    const requiredInputs = form.querySelectorAll('input[required]');

    const validationRules = {
        'phone': /^\d{10}$/,
        'pincode': /^\d{6}$/,
        'email': /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    };

    const validationMessages = {
        'name': 'Please enter vendor name.',
        'contact_person': 'Please enter contact person name.',
        'phone': 'Please enter a valid 10-digit phone number.',
        'email': 'Please enter a valid email address.',
        'address_line1': 'Address line 1 is required.',
        'city': 'Please enter city.',
        'state': 'Please enter state.',
        'country': 'Please enter country.',
        'pincode': 'Please enter a valid 6-digit pincode.'
    };

    const duplicateCheckFields = ['name', 'email', 'phone'];

    duplicateCheckFields.forEach(fieldName => {
        const input = form.querySelector(`input[name="${fieldName}"]`);
        if (input) {
            input.addEventListener('blur', debounce(function () {
                const value = this.value.trim();
                if (value.length > 0) {
                    checkIfVendorExists(fieldName, value, this);
                }
            }, 500));
        }
    });

    // This function now returns a Promise
    function checkIfVendorExists(field, value, inputElement) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('field', field);
            formData.append('value', value);

            fetch(checkVendorExistsUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const feedbackDiv = inputElement.nextElementSibling;
                    if (data.exists) {
                        const message = `This ${field.replace('_', ' ')} is already registered.`;
                        inputElement.classList.remove('is-valid');
                        inputElement.classList.add('is-invalid');
                        if (feedbackDiv) feedbackDiv.textContent = message;
                        resolve(false); // Resolve with false if duplicate exists
                    } else {
                        validateInput(inputElement);
                        resolve(true); // Resolve with true if no duplicate
                    }
                })
                .catch(error => {
                    console.error('Error during vendor existence check:', error);
                    reject(error);
                });
        });
    }

    function validateInput(input) {
        const fieldName = input.getAttribute('name');
        const feedbackDiv = input.nextElementSibling;
        let isValid = true;
        let message = '';

        if (input.hasAttribute('required') && input.value.trim() === '') {
            isValid = false;
            message = validationMessages[fieldName];
        } else if (validationRules[fieldName] && !validationRules[fieldName].test(input.value.trim())) {
            isValid = false;
            message = validationMessages[fieldName];
        }

        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (feedbackDiv) feedbackDiv.textContent = '';
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedbackDiv) feedbackDiv.textContent = message;
        }
        return isValid;
    }

    requiredInputs.forEach(input => {
        input.addEventListener('blur', () => validateInput(input));
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            input.classList.remove('is-valid');
        });
    });

    function resetFormValidation() {
        const allInputs = form.querySelectorAll('input');
        allInputs.forEach(input => {
            input.classList.remove('is-valid');
            input.classList.remove('is-invalid');
        });
    }

    // This is the new, crucial change
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Step 1: Run all synchronous validations
        let formIsValid = true;
        requiredInputs.forEach(input => {
            if (!validateInput(input)) {
                formIsValid = false;
            }
        });

        // Step 2: Run all asynchronous validations
        const asyncChecks = duplicateCheckFields.map(fieldName => {
            const input = form.querySelector(`input[name="${fieldName}"]`);
            if (input && input.value.trim().length > 0) {
                return checkIfVendorExists(fieldName, input.value.trim(), input);
            }
            return Promise.resolve(true);
        });

        // Wait for all async checks to complete
        const asyncResults = await Promise.all(asyncChecks);
        const asyncValidationPassed = asyncResults.every(result => result === true);

        // Step 3: Check overall form validity
        if (!formIsValid || !asyncValidationPassed) {
            showSnackbox('Please correct the validation errors.', 'error');
            return;
        }

        // If all validations pass, proceed with submission
        const formData = new FormData(form);
        fetch(addVendorUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSnackbox(data.message, 'success');
                    form.reset();
                    resetFormValidation();

                    const modal = bootstrap.Modal.getInstance(document.getElementById('addVendorModal'));
                    modal.hide();

                    const tableBody = document.querySelector('.table tbody');
                    if (data.new_row) {
                        tableBody.insertAdjacentHTML('beforeend', data.new_row);
                        const newToggleBtn = tableBody.lastElementChild.previousElementSibling.querySelector('.toggle-details-btn');
                        if (newToggleBtn) {
                            attachToggleListener(newToggleBtn);
                        }
                    }
                } else {
                    const errorMsg = data.errors ? data.errors.replace(/<p>/g, '').replace(/<\/p>/g, '<br>') : 'An error occurred.';
                    showSnackbox(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showSnackbox('An unexpected error occurred.', 'error');
            });
    });

    // --- Table Row Details Toggle ---
    function attachToggleListener(button) {
        button.addEventListener('click', function () {
            const parentRow = this.closest('.main-row');
            const detailsRow = parentRow.nextElementSibling;
            if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                detailsRow.style.display = 'table-row';
                this.innerHTML = '<i class="fa-solid fa-angles-up text-primary"></i>';
            } else {
                detailsRow.style.display = 'none';
                this.innerHTML = '<i class="fa-solid fa-angles-down text-primary"></i>';
            }
        });
    }

    document.querySelectorAll('.toggle-details-btn').forEach(attachToggleListener);
});