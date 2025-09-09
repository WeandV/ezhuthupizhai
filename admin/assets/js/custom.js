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
//first part of the code



document.addEventListener('DOMContentLoaded', function () {
    // A reusable debounce function to limit API calls
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    let itemCounter = 0;
    let booksData = []; // This will now hold product data

    const invoiceForm = document.getElementById('invoiceForm');
    const invoiceForSelect = document.getElementById('invoiceFor');
    const customerNameInput = document.getElementById('customerName');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const recipientSuggestions = document.getElementById('recipient-suggestions');
    const billingAddress1 = document.getElementById('billingAddress1');
    const billingAddress2 = document.getElementById('billingAddress2');
    const cityInput = document.getElementById('city');
    const stateInput = document.getElementById('state');
    const pincodeInput = document.getElementById('pincode');
    const customerEmailInput = document.getElementById('customerEmail');
    const paymentStatusSelect = document.getElementById('paymentStatus');
    const paymentModeSelect = document.getElementById('paymentMode');
    // FIXED: Changed the selector to '.col-md-6' to match the HTML structure
    const paymentModeGroup = paymentModeSelect.closest('.col-md-6');

    const invoiceItemsContainer = document.getElementById('invoice-items-container');
    const addItemBtn = document.getElementById('addItemBtn');
    const discountInput = document.getElementById('discount');

    // Helper function to show a custom toast message
    function showToast(message, type = 'error') {
        const snackbar = document.getElementById('snackbox');
        snackbar.className = `show ${type}`;
        snackbar.textContent = message;
        setTimeout(function () { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }

    // Helper function to create a validation message element
    function createValidationMessage(message) {
        const span = document.createElement('span');
        span.className = 'text-danger validation-message';
        span.textContent = message;
        return span;
    }

    // Helper function to validate a single field
    function validateField(element, message, isSelect = false) {
        let isValid = true;
        let parent = element.parentElement;

        // Remove existing error messages
        const existingMessage = parent.querySelector('.validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (isSelect) {
            if (element.selectedIndex <= 0) {
                isValid = false;
            }
        } else {
            if (element.value.trim() === '') {
                isValid = false;
            }
        }

        if (!isValid) {
            parent.appendChild(createValidationMessage(message));
        }
        return isValid;
    }

    // Helper function to check if at least one item row is valid
    function validateInvoiceItems() {
        const rows = document.querySelectorAll('.invoice-item-row');
        let isValid = true;
        let hasOneValidItem = false;

        if (rows.length === 0) {
            showToast('Please add at least one item.', 'error');
            return false;
        }

        rows.forEach((row, index) => {
            const selectElement = row.querySelector('.item-select');
            const quantityElement = row.querySelector('.item-quantity');

            // Validate select field using the updated logic
            const isSelectValid = validateField(selectElement, 'Please select a book.', true);

            // Validate quantity field
            const isQuantityValid = validateField(quantityElement, 'Quantity is required.');

            if (isSelectValid && isQuantityValid) {
                hasOneValidItem = true;
            } else {
                isValid = false;
            }
        });

        if (!hasOneValidItem) {
            showToast('All invoice items must be completed.', 'error');
            return false;
        }

        return isValid;
    }

    // New: Helper function to populate a single book dropdown
    function populateBookDropdown(dropdownElement) {
        if (!booksData || booksData.length === 0) {
            setTimeout(() => populateBookDropdown(dropdownElement), 100);
            return;
        }

        // Clear and re-populate only the specified dropdown
        dropdownElement.innerHTML = '<option selected disabled>Select a book</option>';
        booksData.forEach(book => {
            const option = document.createElement('option');
            option.value = book.id;
            option.textContent = book.title;
            option.dataset.price = book.price;
            dropdownElement.appendChild(option);
        });
    }

    // Fetch all products on page load
    fetch(`${baseUrl}dashboard/get_products`)
        .then(response => response.json())
        .then(data => {
            booksData = data;
            const initialDropdowns = document.querySelectorAll('.item-select');
            initialDropdowns.forEach(dropdown => populateBookDropdown(dropdown));
            calculateTotals();
        });


    // Function to add a new invoice item row
    function addItem() {
        const newItemHtml = `
            <div class="row g-3 mb-3 invoice-item-row">
                <div class="col-md-3">
                    <label for="item-${itemCounter}" class="form-label">Book</label>
                    <select class="form-select item-select" id="item-${itemCounter}" data-index="${itemCounter}">
                        <option selected disabled>Select a book</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="price-${itemCounter}" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control item-price" id="price-${itemCounter}" value="0.00" readonly>
                </div>
                <div class="col-md-3">
                    <label for="quantity-${itemCounter}" class="form-label">Quantity</label>
                    <input type="number" class="form-control item-quantity" id="quantity-${itemCounter}" value="1" min="1">
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div style="flex-grow: 1;">
                        <label for="total-${itemCounter}" class="form-label">Total</label>
                        <input type="text" class="form-control item-total" id="total-${itemCounter}" value="0.00" readonly>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm ms-2 remove-item-btn" style="align-self: flex-end; margin-bottom: 3px;">&times;</button>
                </div>
            </div>
        `;
        invoiceItemsContainer.insertAdjacentHTML('beforeend', newItemHtml);

        const newRow = invoiceItemsContainer.lastElementChild;
        const newSelect = newRow.querySelector('.item-select');

        // Populate only the new dropdown
        populateBookDropdown(newSelect);

        itemCounter++;
        addEventListeners();
        calculateTotals();
    }

    function removeItem(event) {
        const row = event.target.closest('.invoice-item-row');
        if (row) {
            row.remove();
            calculateTotals();
        }
    }

    function calculateItemTotal(event) {
        const row = event.target.closest('.invoice-item-row');
        const priceInput = row.querySelector('.item-price');
        const quantityInput = row.querySelector('.item-quantity');
        const totalInput = row.querySelector('.item-total');

        const price = parseFloat(priceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = price * quantity;
        totalInput.value = total.toFixed(2);

        calculateTotals();
    }

    function calculateTotals() {
        let totalAmount = 0;
        document.querySelectorAll('.invoice-item-row').forEach(row => {
            const total = parseFloat(row.querySelector('.item-total').value);
            if (!isNaN(total)) {
                totalAmount += total;
            }
        });

        const discountPercentage = parseFloat(discountInput.value) || 0;
        const discountAmount = totalAmount * (discountPercentage / 100);
        const subTotal = totalAmount - discountAmount;

        // Rounding off all the final total values
        const finalTotalAmount = Math.round(totalAmount);
        const finalDiscountAmount = Math.round(discountAmount);
        const finalSubTotal = Math.round(subTotal);

        document.getElementById('totalAmount').textContent = `₹ ${finalTotalAmount.toFixed(2)}`;
        document.getElementById('discountAmount').textContent = `₹ ${finalDiscountAmount.toFixed(2)}`;
        document.getElementById('subTotal').textContent = `₹ ${finalSubTotal.toFixed(2)}`;
    }


    function addEventListeners() {
        // Event listeners for item-level inputs
        document.querySelectorAll('.item-select').forEach(select => {
            select.addEventListener('change', function () {
                const selectedBookId = this.value;
                const book = booksData.find(b => b.id == selectedBookId);
                const priceInput = this.closest('.invoice-item-row').querySelector('.item-price');
                if (book) {
                    priceInput.value = book.price;
                }
                calculateItemTotal({ target: priceInput });
            });
            // Add validation on blur
            select.addEventListener('blur', () => validateField(select, 'Please select a book.', true));
        });

        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('input', calculateItemTotal);
            // Add validation on blur
            input.addEventListener('blur', () => validateField(input, 'Quantity is required.'));
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', removeItem);
        });
    }

    // Function to enable manual input for recipient fields
    function enableManualEntry() {
        const fields = [
            customerEmailInput, billingAddress1, billingAddress2,
            cityInput, stateInput, pincodeInput
        ];
        fields.forEach(field => field.removeAttribute('readonly'));
    }

    // Recipient autofill and autocomplete logic
    function clearRecipientFields() {
        customerNameInput.value = '';
        phoneNumberInput.value = '';
        customerEmailInput.value = '';
        billingAddress1.value = '';
        billingAddress2.value = '';
        cityInput.value = '';
        stateInput.value = '';
        pincodeInput.value = '';
        // Add readonly back after clearing fields
        const fields = [
            customerEmailInput, billingAddress1, billingAddress2,
            cityInput, stateInput, pincodeInput
        ];
        fields.forEach(field => field.setAttribute('readonly', 'readonly'));

        // Clear all validation messages for these fields
        const recipientFields = [customerNameInput, phoneNumberInput, customerEmailInput, billingAddress1, billingAddress2, cityInput, stateInput, pincodeInput];
        recipientFields.forEach(field => {
            const existingMessage = field.parentElement.querySelector('.validation-message');
            if (existingMessage) {
                existingMessage.remove();
            }
        });
    }

    function handleRecipientInput() {
        const type = invoiceForSelect.value;
        const query = this.value.trim();

        // If the user clears the input or types a new value, enable manual entry
        if (query.length < 3) {
            recipientSuggestions.innerHTML = '';
            // If the input is empty, disable manual entry for new search.
            if (query === '') {
                const fields = [customerEmailInput, billingAddress1, billingAddress2, cityInput, stateInput, pincodeInput];
                fields.forEach(field => field.setAttribute('readonly', 'readonly'));
            } else {
                // If the user starts typing, enable manual entry
                enableManualEntry();
            }
            return;
        }

        fetch(`${baseUrl}dashboard/get_recipients?type=${type}&query=${query}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                recipientSuggestions.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        // Check if name or first_name exists
                        const name = item.name || (item.first_name ? `${item.first_name} ${item.last_name || ''}`.trim() : '');
                        option.value = name;
                        option.dataset.id = item.id;
                        option.dataset.phone = item.phone;
                        option.dataset.email = item.email;
                        recipientSuggestions.appendChild(option);
                    });
                    // If suggestions are shown, disable manual entry until a suggestion is selected
                    const fields = [customerEmailInput, billingAddress1, billingAddress2, cityInput, stateInput, pincodeInput];
                    fields.forEach(field => field.setAttribute('readonly', 'readonly'));
                } else {
                    // If no suggestions are found, enable manual entry
                    enableManualEntry();
                }
            })
            .catch(error => {
                console.error('Error fetching recipients:', error);
                showToast('Failed to fetch recipient suggestions.', 'error');
                enableManualEntry(); // Fallback to manual entry on error
            });
    }

    function handleRecipientSelect() {
        const type = invoiceForSelect.value;
        const selectedOption = recipientSuggestions.querySelector(`option[value="${this.value}"]`);

        if (selectedOption) {
            // Re-apply readonly to fields when a valid suggestion is selected
            const fields = [
                customerEmailInput, billingAddress1, billingAddress2,
                cityInput, stateInput, pincodeInput
            ];
            fields.forEach(field => field.setAttribute('readonly', 'readonly'));

            const recipientId = selectedOption.dataset.id;
            const recipientPhone = selectedOption.dataset.phone;
            const recipientEmail = selectedOption.dataset.email;

            phoneNumberInput.value = recipientPhone;
            customerEmailInput.value = recipientEmail;

            // Clear any previous validation messages
            validateField(customerNameInput, '');
            validateField(phoneNumberInput, '');

            fetch(`${baseUrl}dashboard/get_recipient_details?type=${type}&id=${recipientId}`)
                .then(response => response.json())
                .then(details => {
                    if (details) {
                        billingAddress1.value = details.address_line1 || details.address1 || '';
                        billingAddress2.value = details.address_line2 || details.address2 || '';
                        cityInput.value = details.city || '';
                        stateInput.value = details.state || '';
                        pincodeInput.value = details.pincode || details.zip_code || '';

                        // Clear any previous validation messages for address fields
                        validateField(billingAddress1, '');
                        validateField(cityInput, '');
                        validateField(stateInput, '');
                        validateField(pincodeInput, '');
                    }
                });
        } else {
            // If the user types a new value that's not in the datalist, enable manual entry
            enableManualEntry();
            // Clear the address fields to avoid confusion
            customerEmailInput.value = '';
            billingAddress1.value = '';
            billingAddress2.value = '';
            cityInput.value = '';
            stateInput.value = '';
            pincodeInput.value = '';
        }
    }

    // Initial setup
    addItemBtn.addEventListener('click', addItem);
    discountInput.addEventListener('input', calculateTotals);

    // Attach validation to mandatory fields on blur
    document.getElementById('invoiceDate').addEventListener('blur', (e) => validateField(e.target, 'Invoice date is required.'));
    customerNameInput.addEventListener('blur', (e) => validateField(e.target, 'Name is required.'));
    phoneNumberInput.addEventListener('blur', (e) => validateField(e.target, 'Phone number is required.'));
    customerEmailInput.addEventListener('blur', (e) => validateField(e.target, 'Email is required.'));
    billingAddress1.addEventListener('blur', (e) => validateField(e.target, 'Billing address is required.'));
    cityInput.addEventListener('blur', (e) => validateField(e.target, 'City is required.'));
    stateInput.addEventListener('blur', (e) => validateField(e.target, 'State is required.'));
    pincodeInput.addEventListener('blur', (e) => validateField(e.target, 'Pincode is required.'));
    paymentStatusSelect.addEventListener('blur', (e) => validateField(e.target, 'Please select a payment status.', true));
    paymentModeSelect.addEventListener('blur', (e) => validateField(e.target, 'Please select a payment mode.', true));

    // New: Logic to hide/show Payment Mode based on Payment Status
    paymentStatusSelect.addEventListener('change', function () {
        if (this.value === 'Unpaid') {
            paymentModeGroup.style.display = 'none';
            paymentModeSelect.selectedIndex = 0; // Reset the payment mode
            validateField(paymentModeSelect, ''); // Clear any validation messages
        } else {
            paymentModeGroup.style.display = 'block';
        }
    });

    invoiceForSelect.addEventListener('change', function () {
        clearRecipientFields();
        customerNameInput.placeholder = this.value === 'vendor' ? 'Vendor Name' : 'Customer Name';
        customerNameInput.value = '';
        phoneNumberInput.value = '';
        recipientSuggestions.innerHTML = ''; // Clear suggestions
    });

    customerNameInput.addEventListener('input', debounce(handleRecipientInput, 300));
    phoneNumberInput.addEventListener('input', debounce(handleRecipientInput, 300));

    // Listen for changes on the name and phone input fields to handle datalist selection
    customerNameInput.addEventListener('change', handleRecipientSelect);
    phoneNumberInput.addEventListener('change', handleRecipientSelect);

    addEventListeners();
    calculateTotals();

    // Form submission validation and data submission
    invoiceForm.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        let formIsValid = true;

        // Validate all mandatory fields
        const mandatoryFields = [
            document.getElementById('invoiceDate'),
            customerNameInput,
            phoneNumberInput,
            customerEmailInput,
            billingAddress1,
            cityInput,
            stateInput,
            pincodeInput
        ];

        mandatoryFields.forEach(field => {
            if (!validateField(field, `${field.previousElementSibling.textContent} is required.`)) {
                formIsValid = false;
            }
        });

        // Validate select fields
        if (!validateField(paymentStatusSelect, 'Payment status is required.', true)) {
            formIsValid = false;
        }

        // Only validate payment mode if it's visible
        if (paymentModeGroup.style.display !== 'none') {
            if (!validateField(paymentModeSelect, 'Please select a payment mode.', true)) {
                formIsValid = false;
            }
        }

        // Validate invoice items
        if (!validateInvoiceItems()) {
            formIsValid = false;
        }

        if (formIsValid) {
            // Collect all form data
            const invoiceItems = [];
            document.querySelectorAll('.invoice-item-row').forEach(row => {
                const item = {
                    book_id: row.querySelector('.item-select').value,
                    quantity: row.querySelector('.item-quantity').value,
                    price: row.querySelector('.item-price').value,
                    total: row.querySelector('.item-total').value,
                };
                invoiceItems.push(item);
            });

            // Re-calculate the final totals and ensure they are rounded for the data payload
            let rawTotalAmount = 0;
            invoiceItems.forEach(item => {
                rawTotalAmount += parseFloat(item.total);
            });
            const rawDiscountAmount = rawTotalAmount * (parseFloat(discountInput.value) / 100 || 0);
            const rawSubTotal = rawTotalAmount - rawDiscountAmount;

            const invoiceData = {
                invoice_date: document.getElementById('invoiceDate').value,
                invoice_number: document.getElementById('invoiceNumber').value,
                invoice_for: invoiceForSelect.value,
                customer_name: customerNameInput.value,
                phone_number: phoneNumberInput.value,
                customer_email: customerEmailInput.value,
                billing_address1: billingAddress1.value,
                billing_address2: billingAddress2.value,
                city: cityInput.value,
                state: stateInput.value,
                pincode: pincodeInput.value,
                payment_status: paymentStatusSelect.value,
                payment_mode: paymentStatusSelect.value !== 'Unpaid' ? paymentModeSelect.value : null,
                discount_percentage: discountInput.value,
                total_amount: Math.round(rawTotalAmount),
                discount_amount: Math.round(rawDiscountAmount),
                sub_total: Math.round(rawSubTotal),
                items: invoiceItems
            };

            // Send the data to the server
            fetch(`${baseUrl}dashboard/save_invoice`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(invoiceData)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        showToast('Invoice generated and saved successfully!', 'success');
                        window.location.href = `${baseUrl}invoice-list`;
                    } else {
                        showToast(result.message || 'Failed to generate invoice.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error submitting invoice:', error);
                    showToast('An error occurred. Please try again.', 'error');
                });
        } else {
            // Form is not valid, show a general error message
            showToast('Please fill out all mandatory fields and correct any errors.', 'error');
        }
    });
});