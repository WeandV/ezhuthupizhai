import { Component, OnInit, OnDestroy, NgZone} from '@angular/core';
import { FormBuilder, FormGroup, Validators, AbstractControl } from '@angular/forms';
import { Router } from '@angular/router';
import { Observable, Subscription, combineLatest, of } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { take, tap, switchMap, debounceTime, distinctUntilChanged, filter, } from 'rxjs/operators';
import { CartService } from 'src/app/services/cart.service';
import { map } from 'rxjs/operators';
import { CartItem } from 'src/app/models/cart-item.model';
import { AppliedCoupon } from 'src/app/models/applied-coupon.model';
import { UserService, User, Address as UserAddress } from '../../services/user.service';
import { environment } from 'src/app/environments/environment';
import { firstValueFrom } from 'rxjs';

declare var google: any;
declare var Razorpay: any;

@Component({
    selector: 'app-checkout',
    templateUrl: './checkout.component.html',
    styleUrls: ['./checkout.component.css'],
})

export class CheckoutComponent implements OnInit, OnDestroy {
    showLoader: boolean = false;
    checkoutForm!: FormGroup;
    loginForm!: FormGroup;
    cartItems$: Observable<CartItem[]>;
    enabledCouriers: any[] = [];
    couriersLoading: boolean = false;
    courierErrorMessage: string | null = null;
    selectedCourier: any = null;
    cartItems: CartItem[] = [];
    totalCouponDiscount$: Observable<number>;
    deliveryCharge$: Observable<number>;
    subTotalAfterDiscount$: Observable<number>;
    finalTotal$: Observable<number>;
    currentlyAppliedCoupon$: Observable<AppliedCoupon | null>;
    loggedInUserId: number | null = null;
    loginEmail: string = '';
    otpSent: boolean = false;
    otpVerified: boolean = false;
    userAddresses: UserAddress[] = [];
    selectedAddressId: number | null = null;
    activeAddressMode: 'new' | 'saved' = 'new';
    selectedPaymentMethod: string = 'Razorpay';
    agreedToTerms: boolean = false;
    private subscriptions: Subscription = new Subscription();
    showErrorMessage: boolean = false;
    errorMessage: string = '';
    messageType: 'success' | 'error' | 'warning' | 'info' = 'info';

    constructor(
        private router: Router,
        public cartService: CartService,
        private http: HttpClient,
        private fb: FormBuilder,
        private userService: UserService,
        private zone: NgZone
    ) {
        this.cartItems$ = this.cartService.cartItems$;
        this.totalCouponDiscount$ = this.cartService.totalCouponDiscount$;
        this.deliveryCharge$ = this.cartService.deliveryCharge$;
        this.subTotalAfterDiscount$ = this.cartService.subTotalAfterDiscount$;
        this.finalTotal$ = this.cartService.finalTotal$;
        this.currentlyAppliedCoupon$ = this.cartService.getCurrentlyAppliedCoupon();
        this.initForms();
    }

    ngOnInit(): void {
        this.subscriptions.add(
            this.cartItems$.subscribe((items) => {
                this.cartItems = items;
                if (items.length === 0) {
                    this.displayMessage(
                        'Your cart is empty. Please add items before proceeding to checkout.',
                        'warning'
                    );
                } else {
                    this.showErrorMessage = false;
                    this.errorMessage = '';
                }
            })
        );
        this.subscriptions.add(
            this.userService.currentUser$.subscribe((user) => {
                if (user) {
                    this.loggedInUserId = user.id;
                    this.loginEmail = user.email;
                    this.otpVerified = true;
                    this.otpSent = true;
                    this.loginForm.patchValue({ email: this.loginEmail });
                    this.loginForm.get('email')?.disable();
                    this.fetchUserAddresses(this.loggedInUserId);
                } else {
                    this.logoutLocalState();
                    this.loginForm.get('email')?.enable();
                }
                this.updateLoginFormState();
            })
        );
        this.updateCheckoutFormAccessibility();
        this.subscriptions.add(
            this.checkoutForm
                .get('zipCode')
                ?.valueChanges.pipe(
                    debounceTime(500),
                    distinctUntilChanged(),
                    filter((zipCode) => !!zipCode && /^\d{6}$/.test(zipCode)),
                    tap(() => {
                        this.displayMessage(
                            'Calculating delivery charge and fetching couriers...',
                            'info',
                            0
                        );
                        this.fetchEnabledCouriers();
                    }),
                )
                .subscribe((response) => {
                    if (response.status === 'success') {
                        this.displayMessage('Delivery charge calculated.', 'success', 3000);
                    } else {
                        this.displayMessage(
                            response.message || 'Could not calculate delivery charge.',
                            'error'
                        );
                    }
                })
        );
        const initialZipCode = this.checkoutForm.get('zipCode')?.value;
        if (initialZipCode && /^\d{6}$/.test(initialZipCode)) {
            this.fetchEnabledCouriers();
        }
    }

    private fetchEnabledCouriers(): void {
        this.couriersLoading = true;
        this.courierErrorMessage = null;
        // Reset selected courier and delivery charge while fetching
        this.selectedCourier = null;
        this.cartService.setDeliveryCharge(0);

        const pickupPincode = '600020';
        const deliveryPincode = this.checkoutForm.get('zipCode')?.value;
        const cartWeight = this.calculateCartWeight();
        const cod = this.selectedPaymentMethod === 'COD' ? 1 : 0;

        if (!deliveryPincode) {
            this.couriersLoading = false;
            this.displayMessage('Please enter your delivery pincode.', 'error');
            return;
        }

        const requestData = {
            pickup_pincode: pickupPincode,
            delivery_pincode: deliveryPincode,
            weight: cartWeight,
            cod,
        };

        console.log('Request Data for Shiprocket:', requestData);

        this.cartService.getEnabledCouriers(requestData).subscribe({
            next: (response) => {
                this.couriersLoading = false;
                // First, check if the response contains any couriers
                if (response.status === 'success' && response.enabled_couriers?.length > 0) {
                    // If couriers are available, map and sort them
                    this.enabledCouriers = response.enabled_couriers.map((c: any) => ({
                        courier_id: c.courier_id,
                        courier_name: c.courier_name,
                        delivery_charge: Number(c.delivery_charge) || 0,
                    }));
                    this.enabledCouriers.sort(
                        (a, b) => (a.delivery_charge > 0 ? a.delivery_charge : Number.MAX_VALUE) -
                            (b.delivery_charge > 0 ? b.delivery_charge : Number.MAX_VALUE)
                    );

                    // Set the first available courier with a charge > 0 as selected
                    const firstAvailable = this.enabledCouriers.find(c => c.delivery_charge > 0);
                    if (firstAvailable) {
                        this.selectedCourier = firstAvailable;
                        this.cartService.setDeliveryCharge(firstAvailable.delivery_charge);
                        this.displayMessage('Courier list updated.', 'success', 3000);
                    } else {
                        // No valid couriers found even if the array is not empty
                        this.selectedCourier = null;
                        this.enabledCouriers = []; // Clear the array to be explicit
                        this.courierErrorMessage = 'No enabled couriers with valid charges available for this route.';
                        this.displayMessage(this.courierErrorMessage, 'error');
                    }
                } else {
                    // Handle the "no couriers" case directly
                    this.enabledCouriers = [];
                    this.selectedCourier = null;
                    this.courierErrorMessage = response.message || 'No enabled couriers available for this route.';
                    this.displayMessage(this.courierErrorMessage!, 'error');
                }
            },
            error: (err) => {
                console.error('Error fetching couriers:', err);
                this.couriersLoading = false;
                this.enabledCouriers = [];
                this.selectedCourier = null;
                this.courierErrorMessage = 'An error occurred while fetching couriers. Please try again.';
                this.displayMessage(this.courierErrorMessage, 'error');
            },
        });
    }
    onCourierSelected(courier: any): void {
        this.selectedCourier = courier;
        const deliveryCharge = courier?.delivery_charge || 0;
        this.cartService.setDeliveryCharge(deliveryCharge);
        console.log(
            `Selected courier: ${courier?.courier_name}. Delivery charge: ₹${deliveryCharge}`
        );
    }

    ngOnDestroy(): void {
        this.subscriptions.unsubscribe();
    }

    private initForms(): void {
        this.loginForm = this.fb.group({
            email: ['', [Validators.required, Validators.email]],
            otp: ['', [Validators.required, Validators.pattern(/^\d{6}$/)]],
        });
        this.checkoutForm = this.fb.group({
            firstName: ['', Validators.required],
            lastName: [''],
            phone: ['', [Validators.required, Validators.pattern(/^\d{10}$/)]],
            email: ['', [Validators.required, Validators.email]],
            address1: ['', Validators.required],
            address2: [''],
            city: ['', Validators.required],
            state: ['', Validators.required],
            zipCode: ['', [Validators.required, Validators.pattern(/^\d{6}$/)]],
            country: ['India', [Validators.required, this.validateIndiaCountry]],
            createAccount: [true],
            orderNotes: [''],
        });
    }

    private validateIndiaCountry(control: AbstractControl): { [key: string]: any } | null {
        const country = control.value;
        if (country && country.toLowerCase() !== 'india') {
            return { 'invalidCountry': true };
        }
        return null;
    }

    private updateLoginFormState(): void {
        const otpControl = this.loginForm.get('otp');
        const emailControl = this.loginForm.get('email');
        if (this.otpSent) {
            otpControl?.enable();
        } else {
            otpControl?.disable();
        }
        if (this.otpVerified && this.loggedInUserId) {
            emailControl?.disable();
            otpControl?.disable();
        } else {
            emailControl?.enable();
        }
    }

    private updateCheckoutFormAccessibility(): void {
        if (this.activeAddressMode === 'saved' && this.selectedAddressId !== null) {
            this.checkoutForm.controls['firstName'].disable();
            this.checkoutForm.controls['lastName'].disable();
            this.checkoutForm.controls['phone'].disable();
            this.checkoutForm.controls['email'].disable();
            this.checkoutForm.controls['address1'].disable();
            this.checkoutForm.controls['address2'].disable();
            this.checkoutForm.controls['city'].disable();
            this.checkoutForm.controls['state'].disable();
            this.checkoutForm.controls['zipCode'].disable();
            this.checkoutForm.controls['country'].disable();
            this.checkoutForm.controls['createAccount'].disable();
        } else {
            this.checkoutForm.controls['firstName'].enable();
            this.checkoutForm.controls['lastName'].enable();
            this.checkoutForm.controls['phone'].enable();
            this.checkoutForm.controls['email'].enable();
            this.checkoutForm.controls['address1'].enable();
            this.checkoutForm.controls['address2'].enable();
            this.checkoutForm.controls['city'].enable();
            this.checkoutForm.controls['state'].enable();
            this.checkoutForm.controls['zipCode'].enable();
            this.checkoutForm.controls['country'].enable();
            if (!this.loggedInUserId) {
                this.checkoutForm.controls['createAccount'].enable();
            } else {
                this.checkoutForm.controls['createAccount'].disable();
                this.checkoutForm.patchValue({ createAccount: false });
            }
        }
        if (this.loggedInUserId && this.loginEmail) {
            this.checkoutForm.patchValue({ email: this.loginEmail });
            this.checkoutForm.get('email')?.disable();
        } else {
            this.checkoutForm.get('email')?.enable();
        }
    }

    public parseFloat(value: string | number | null | undefined): number {
        if (value == null || value === 0 || isNaN(Number(value))) {
            return 0;
        }
        return Number(value);
    }

    private displayMessage(
        message: string,
        type: 'success' | 'error' | 'warning' | 'info' = 'info',
        duration: number = 5000
    ): void {
        this.errorMessage = message;
        this.messageType = type;
        this.showErrorMessage = true;
        if (duration > 0) {
            setTimeout(() => {
                this.showErrorMessage = false;
                this.errorMessage = '';
            }, duration);
        }
    }

    private logoutLocalState(): void {
        this.loggedInUserId = null;
        this.otpVerified = false;
        this.otpSent = false;
        this.loginEmail = '';
        this.userAddresses = [];
        this.selectedAddressId = null;
        this.activeAddressMode = 'new';
        this.resetCheckoutForm();
        this.loginForm.reset();
        this.updateLoginFormState();
        this.updateCheckoutFormAccessibility();
    }

    sendOtp(): void {
        this.displayMessage('', 'info', 0);
        if (this.loginForm.get('email')?.invalid) {
            this.displayMessage(
                'Please enter a valid email address to send OTP.',
                'warning'
            );
            return;
        }
        const emailToSend = this.loginForm.get('email')?.value;
        this.userService.sendOtp(emailToSend).subscribe({
            next: (response: any) => {
                if (response.success) {
                    this.otpSent = true;
                    this.displayMessage(
                        'OTP sent to your email! Please check your inbox.',
                        'success',
                        8000
                    );
                    this.loginEmail = emailToSend;
                    this.updateLoginFormState();
                } else {
                    this.displayMessage(
                        'Failed to send OTP: ' + (response.message || 'Unknown error.'),
                        'error'
                    );
                }
            },
            error: (error) => {
                this.displayMessage(
                    'An error occurred while sending OTP. Please try again.',
                    'error'
                );
            },
        });
    }

    verifyOtpAndFetchAddresses(): void {
        this.displayMessage('', 'info', 0);
        if (this.loginForm.invalid) {
            this.displayMessage('Please enter a valid email and OTP.', 'warning');
            return;
        }
        const emailToVerify = this.loginForm.get('email')?.value;
        const otpToVerify = this.loginForm.get('otp')?.value;
        this.userService.verifyOtp(emailToVerify, otpToVerify).subscribe({
            next: (response: any) => {
                if (response.success) {
                    const user: User = {
                        id: response.user_id,
                        first_name: response.first_name || '',
                        last_name: response.last_name || null,
                        email: emailToVerify,
                        phone: response.phone || '',
                        token: response.token || undefined,
                    };
                    this.userService.setUser(user);
                    this.displayMessage(
                        'OTP verified. You are now logged in!',
                        'success',
                        3000
                    );
                } else {
                    this.displayMessage(
                        'OTP verification failed: ' + (response.message || 'Invalid OTP.'),
                        'error'
                    );
                    this.otpVerified = false;
                    this.loginForm.get('otp')?.reset();
                    this.updateLoginFormState();
                }
            },
            error: (error) => {
                this.displayMessage(
                    'An error occurred during OTP verification. Please try again.',
                    'error'
                );
                this.otpVerified = false;
                this.loginForm.get('otp')?.reset();
                this.updateLoginFormState();
            },
        });
    }

    private fetchUserAddresses(userId: number): void {
        if (!userId) {
            return;
        }
        this.displayMessage(
            'Attempting to retrieve your saved addresses...',
            'info',
            2000
        );
        this.userService.getUserAddresses(userId).subscribe({
            next: (response) => {
                if (response.success) {
                    this.userAddresses = response.addresses || [];
                    if (this.userAddresses.length > 0) {
                        this.activeAddressMode = 'saved';
                        const defaultShippingAddress = this.userAddresses.find(
                            (addr) => addr.is_default_shipping === 1
                        );
                        this.selectedAddressId =
                            defaultShippingAddress?.id ?? this.userAddresses[0]?.id ?? null;
                        if (this.selectedAddressId !== null) {
                            this.selectSavedAddress(this.selectedAddressId);
                        } else {
                            this.activeAddressMode = 'new';
                            this.displayMessage(
                                'No valid default or saved addresses found. Please add a new one.',
                                'warning'
                            );
                            this.resetCheckoutForm();
                            this.checkoutForm.patchValue({ email: this.loginEmail });
                        }
                        this.displayMessage(
                            'Welcome back! Your addresses have been loaded.',
                            'success',
                            3000
                        );
                    } else {
                        this.activeAddressMode = 'new';
                        this.selectedAddressId = null;
                        this.resetCheckoutForm();
                        this.checkoutForm.patchValue({ email: this.loginEmail });
                        this.displayMessage(
                            'Welcome back! No saved addresses found. Please enter your details.',
                            'info',
                            5000
                        );
                    }
                } else {
                    this.displayMessage(
                        'Failed to load saved addresses: ' +
                        (response.message || 'Unknown error.'),
                        'error'
                    );
                    this.userService.clearUser();
                }
                this.updateCheckoutFormAccessibility();
            },
            error: (error) => {
                this.displayMessage(
                    'An error occurred while loading your addresses. Please log in again.',
                    'error'
                );
                this.userService.clearUser();
                this.updateCheckoutFormAccessibility();
            },
        });
    }

    logout(): void {
        this.userService.clearUser();
        this.displayMessage('You have been logged out.', 'info', 3000);
    }

    toggleAddressMode(mode: 'saved' | 'new'): void {
        this.activeAddressMode = mode;
        this.displayMessage('', 'info', 0);
        if (mode === 'new') {
            this.selectedAddressId = null;
            this.resetCheckoutForm();
            if (this.otpVerified) {
                this.checkoutForm.patchValue({ email: this.loginEmail });
            }
            this.displayMessage(
                'Enter details for your new delivery address.',
                'info',
                3000
            );
        } else if (mode === 'saved') {
            if (this.userAddresses.length > 0) {
                const defaultShippingAddress = this.userAddresses.find(
                    (addr) => addr.is_default_shipping === 1
                );
                this.selectedAddressId =
                    defaultShippingAddress?.id ?? this.userAddresses[0]?.id ?? null;
                if (this.selectedAddressId !== null) {
                    this.selectSavedAddress(this.selectedAddressId);
                    this.displayMessage('Choose a saved address.', 'info', 3000);
                } else {
                    this.activeAddressMode = 'new';
                    this.displayMessage(
                        'No valid saved addresses available. Please add a new one.',
                        'warning'
                    );
                    this.resetCheckoutForm();
                    if (this.otpVerified) {
                        this.checkoutForm.patchValue({ email: this.loginEmail });
                    }
                }
            } else {
                this.activeAddressMode = 'new';
                this.displayMessage(
                    'You have no saved addresses. Please add a new one.',
                    'warning'
                );
                if (this.otpVerified) {
                    this.checkoutForm.patchValue({ email: this.loginEmail });
                }
            }
        }
        this.updateCheckoutFormAccessibility();
    }

    selectSavedAddress(addressId: number | undefined | null): void {
        if (addressId === undefined || addressId === null) {
            this.selectedAddressId = null;
            this.resetCheckoutForm();
            this.updateCheckoutFormAccessibility();
            return;
        }
        this.selectedAddressId = addressId;
        const selectedAddress = this.userAddresses.find(
            (addr) => addr.id === addressId
        );
        if (selectedAddress) {
            this.checkoutForm.patchValue({
                firstName: selectedAddress.first_name,
                lastName: selectedAddress.last_name,
                phone: selectedAddress.phone,
                email: selectedAddress.email,
                address1: selectedAddress.address1,
                address2: selectedAddress.address2,
                city: selectedAddress.city,
                state: selectedAddress.state,
                zipCode: selectedAddress.zip_code,
                country: selectedAddress.country,
            });
            this.checkoutForm.get('createAccount')?.patchValue(false);
            this.checkoutForm.markAsPristine();
            this.checkoutForm.markAsUntouched();
            this.displayMessage(
                'Address selected: ' + selectedAddress.address1,
                'info',
                2000
            );
        } else {
            this.selectedAddressId = null;
            this.activeAddressMode = 'new';
            this.resetCheckoutForm();
            this.displayMessage(
                'Selected address is no longer available. Please enter a new address.',
                'error'
            );
        }
        this.updateCheckoutFormAccessibility();
    }

    editAddress(address: UserAddress): void {
        this.activeAddressMode = 'new';
        this.selectedAddressId = address.id ?? null;
        this.checkoutForm.patchValue({
            firstName: address.first_name,
            lastName: address.last_name,
            phone: address.phone,
            email: address.email,
            address1: address.address1,
            address2: address.address2,
            city: address.city,
            state: address.state,
            zipCode: address.zip_code,
            country: address.country,
        });
        this.checkoutForm.get('createAccount')?.patchValue(false);
        this.displayMessage(
            'You are now editing this address. Make changes and place order.',
            'info',
            5000
        );
        this.updateCheckoutFormAccessibility();
    }

    removeAddress(addressId: number): void {
        this.displayMessage('Removing address...', 'info', 0);
        this.http
            .post(environment.apiUrl + 'addresses/remove', {
                address_id: addressId,
                user_id: this.loggedInUserId,
            })
            .subscribe({
                next: (response: any) => {
                    if (response.success) {
                        this.userAddresses = this.userAddresses.filter(
                            (addr) => addr.id !== addressId
                        );
                        if (this.selectedAddressId === addressId) {
                            this.selectedAddressId = null;
                            this.activeAddressMode = 'new';
                            this.resetCheckoutForm();
                        }
                        this.displayMessage(
                            'Address removed successfully!',
                            'success',
                            3000
                        );
                        if (this.userAddresses.length === 0) {
                            this.activeAddressMode = 'new';
                            this.displayMessage(
                                'All saved addresses removed. Please add a new one.',
                                'info'
                            );
                        }
                        this.updateCheckoutFormAccessibility();
                    } else {
                        this.displayMessage(
                            response.message || 'Failed to remove address.',
                            'error'
                        );
                    }
                },
                error: (error) => {
                    this.displayMessage(
                        'An error occurred while removing address. Please try again.',
                        'error'
                    );
                },
            });
    }

    resetCheckoutForm(): void {
        this.checkoutForm.reset({
            firstName: '',
            lastName: '',
            phone: '',
            email: '',
            address1: '',
            address2: '',
            city: '',
            state: '',
            zipCode: '',
            country: 'India',
            createAccount: true,
            orderNotes: '',
        });
        this.checkoutForm.markAsPristine();
        this.checkoutForm.markAsUntouched();
        this.updateCheckoutFormAccessibility();
    }

    private calculateCartWeight(): number {
        const defaultWeightKg = 0.5; // default product weight
        let totalActualWeight = 0;
        let totalVolumetricWeight = 0;

        this.cartItems.forEach((item) => {
            // 1️⃣ Actual weight
            let itemWeight = this.parseFloat(item.product.weight_kg);
            if (itemWeight <= 0) itemWeight = defaultWeightKg;
            totalActualWeight += itemWeight * item.quantity;

            // 2️⃣ Volumetric weight
            const length = this.parseFloat(item.product.length_cm);
            const width = this.parseFloat(item.product.breadth_cm);
            const height = this.parseFloat(item.product.height_cm);
            const volumetricWeight =
                length && width && height
                    ? (length * width * height) / 5000
                    : defaultWeightKg;
            totalVolumetricWeight += volumetricWeight * item.quantity;
        });

        // 3️⃣ Shiprocket charges by higher weight
        const finalWeight = Math.max(totalActualWeight, totalVolumetricWeight);

        // Shiprocket requires weight > 0
        return finalWeight > 0 ? parseFloat(finalWeight.toFixed(2)) : 0.1;
    }

    placeOrder(): void {
        this.displayMessage('', 'info', 0);
        if (this.cartItems.length === 0) {
            this.displayMessage(
                'Your cart is empty. Please add items before placing an order.',
                'warning'
            );
            this.router.navigate(['/cart']);
            return;
        }
        if (!this.agreedToTerms) {
            this.displayMessage(
                'Please agree to the website terms and conditions.',
                'warning'
            );
            return;
        }

        if (
            this.activeAddressMode === 'new' &&
            this.checkoutForm.invalid &&
            !this.checkoutForm.get('email')?.disabled
        ) {
            this.displayMessage(
                'Please fill in all required shipping details correctly.',
                'warning'
            );
            this.checkoutForm.markAllAsTouched();
            return;
        }

        // Use a single pipeline to prepare the payload and place the order
        this.prepareOrderPayload().subscribe((payload) => {
            if (payload) {
                if (this.selectedPaymentMethod === 'COD') {
                    this.placeOrderCOD(payload);
                } else {
                    this.placeOrderRazorpay(payload);
                }
            } else {
                this.displayMessage(
                    'Could not prepare order. Please check your details and try again.',
                    'error'
                );
            }
        });
    }

    private placeOrderCOD(payload: any): void {
        this.http
            .post(environment.apiUrl + 'checkout/place_order_cod', payload)
            .subscribe({
                next: (response: any) => {
                    if (response.success) {
                        this.cartService.clearCart();
                        this.router.navigate(['/order-confirmation', response.order_id]);
                        this.displayMessage(
                            'Order placed successfully! Order ID: ' + response.order_id,
                            'success',
                            5000
                        );
                    } else {
                        this.displayMessage(
                            response.message || 'Order placement failed. Please try again.',
                            'error'
                        );
                    }
                },
                error: (error) => {
                    console.error('HTTP Error placing COD order:', error);
                    let errorMessage =
                        'An unexpected error occurred while placing your order. Please try again.';
                    if (error.error && error.error.message) {
                        errorMessage = error.error.message;
                    }
                    this.displayMessage(errorMessage, 'error');
                },
            });
    }

    private placeOrderRazorpay(payload: any): void {
        this.showLoader = true; // Start loader while creating Razorpay Order on backend
        this.http
            .post(environment.apiUrl + 'checkout/place_order_razorpay', payload)
            .subscribe({
                next: (response: any) => {
                    this.showLoader = false; // Hide loader after getting Razorpay ID
                    if (response.success) {
                        // 🚀 PASS THE INITIAL PAYLOAD HERE
                        this.initiateRazorpayPayment(response, payload);
                    } else {
                        this.displayMessage(
                            response.message ||
                            'Failed to create Razorpay order. Please try again.',
                            'error'
                        );
                    }
                },
                error: (error) => {
                    this.showLoader = false; // Hide loader on error
                    console.error('HTTP Error placing Razorpay order:', error);
                    this.displayMessage(
                        'An unexpected error occurred. Please try again.',
                        'error'
                    );
                },
            });
    }

    // 🚀 MODIFIED: Accepts the payload used for order creation
    private async initiateRazorpayPayment(response: any, initialPayload: any): Promise<void> {
        const options: any = {
            key: response.key,
            amount: response.amount,
            currency: response.currency,
            name: response.name,
            description: response.description,
            order_id: response.razorpayOrderId,
            prefill: response.prefill,
            theme: { color: '#0D6EFD' },
            // CRITICAL: Ensure Razorpay handler uses this method signature
            handler: async (razorpayResponse: any) => {
                
                // 🚀 START LOADER: Use zone.run for immediate UI update (if not in zone)
                // Assuming you have access to NgZone via `this.zone`
                // If this component is already running in Angular's zone, you can remove zone.run
                // For safety in async handlers, using zone.run/this.zone.run() is recommended.
                if (this.zone) {
                    this.zone.run(() => {
                        this.showLoader = true; 
                    });
                } else {
                    this.showLoader = true; 
                }

                try {
                    // 🚀 REMOVED REDUNDANT CALL: const payload = await firstValueFrom(this.prepareOrderPayload());
                    
                    const finalPayload = {
                        ...initialPayload, // 🚀 USE THE PAYLOAD PASSED FROM placeOrderRazorpay
                        razorpay_payment_id: razorpayResponse.razorpay_payment_id,
                        razorpay_order_id: razorpayResponse.razorpay_order_id,
                        razorpay_signature: razorpayResponse.razorpay_signature,
                    };

                    const verificationResponse: any = await firstValueFrom(
                        this.http.post(environment.apiUrl + 'checkout/verify_payment', finalPayload)
                    );

                    // 🛑 All UI updates must be inside zone.run if applicable
                    if (this.zone) this.zone.run(() => { this.showLoader = false; }); else this.showLoader = false;
                    
                    if (verificationResponse.success) {
                        this.cartService.clearCart();
                        this.router.navigate([
                            '/order-confirmation',
                            verificationResponse.order_id,
                        ]);
                    } else {
                        this.displayMessage(
                            verificationResponse.message || 'Payment verification failed.',
                            'error'
                        );
                    }
                } catch (error) {
                    console.error('Payment verification error:', error);
                    
                    // 🛑 All UI updates must be inside zone.run if applicable
                    if (this.zone) {
                         this.zone.run(() => {
                            this.displayMessage('An error occurred during payment verification. Please try again.', 'error');
                            this.showLoader = false;
                        });
                    } else {
                        this.displayMessage('An error occurred during payment verification. Please try again.', 'error');
                        this.showLoader = false;
                    }
                }
            },
            modal: {
                ondismiss: () => {
                    // 🛑 All UI updates must be inside zone.run if applicable
                    if (this.zone) {
                        this.zone.run(() => {
                            this.displayMessage('Payment was cancelled.', 'warning');
                        });
                    } else {
                        this.displayMessage('Payment was cancelled.', 'warning');
                    }
                },
            },
        };

        const rzp = new Razorpay(options);
        rzp.open();
    }


    private prepareOrderPayload(): Observable<any | null> {
        let shippingDetailsToSend: any;

        if (this.activeAddressMode === 'saved' && this.selectedAddressId !== null) {
            const selectedAddress = this.userAddresses.find(
                (addr) => addr.id === this.selectedAddressId
            );
            if (!selectedAddress) {
                this.displayMessage(
                    'Selected address not found. Please choose an address or enter new details.',
                    'error'
                );
                return of(null);
            }
            shippingDetailsToSend = {
                firstName: selectedAddress.first_name,
                lastName: selectedAddress.last_name,
                phone: selectedAddress.phone,
                email: selectedAddress.email,
                address1: selectedAddress.address1,
                address2: selectedAddress.address2,
                city: selectedAddress.city,
                state: selectedAddress.state,
                zipCode: selectedAddress.zip_code,
                country: selectedAddress.country,
                orderNotes: this.checkoutForm.get('orderNotes')?.value,
                address_id: selectedAddress.id,
                is_address_from_saved: true,
                createAccount: false,
            };
            if (shippingDetailsToSend.address2 === null)
                shippingDetailsToSend.address2 = '';
            if (shippingDetailsToSend.lastName === null)
                shippingDetailsToSend.lastName = '';
        } else {
            const formValue = this.checkoutForm.getRawValue();
            if (this.checkoutForm.invalid && this.activeAddressMode === 'new') {
                this.displayMessage(
                    'Please fill in all required shipping details correctly.',
                    'warning'
                );
                this.checkoutForm.markAllAsTouched();
                return of(null);
            }
            shippingDetailsToSend = {
                ...formValue,
                is_address_from_saved: false,
            };
            if (this.selectedAddressId !== null) {
                shippingDetailsToSend.address_id = this.selectedAddressId;
            }
        }
        const formattedCartItems = this.cartItems.map((item) => {
            const priceAtOrder =
                this.parseFloat(item.product.special_price) ||
                this.parseFloat(item.product.mrp_price);
            const baseItem = {
                product_id: item.product.id,
                product_name: item.product.name,
                quantity: item.quantity,
                price_at_order: priceAtOrder,
            };
            if (item.product.options?.['is_byob_box']) {
                const byob_list = (
                    (item.product.options['selected_item_names'] as string[]) || []
                )
                    .map((name, index) => `${index + 1}. ${name}`)
                    .join('\n');
                return {
                    ...baseItem,
                    byob_items_list: byob_list || null,
                };
            }
            return baseItem;
        });

        if (formattedCartItems.length === 0) {
            this.displayMessage(
                'There was an issue processing your cart items. Please try again.',
                'error'
            );
            return of(null);
        }

        return combineLatest([
            this.cartService.cartTotal$,
            this.cartService.totalCouponDiscount$,
            this.cartService.subTotalAfterDiscount$,
            this.cartService.deliveryCharge$,
            this.cartService.finalTotal$,
            this.userService.currentUser$,
        ]).pipe(
            take(1),
            map(
                ([
                    subtotal,
                    coupon_discount,
                    subtotal_after_discount,
                    delivery_charge,
                    final_total,
                    currentUser,
                ]) => {
                    return {
                        user_auth_context: {
                            email: currentUser?.email || this.loginEmail,
                            otp_verified: this.otpVerified,
                            user_id: currentUser?.id || this.loggedInUserId,
                        },
                        shipping_details: shippingDetailsToSend,
                        payment_method: this.selectedPaymentMethod,
                        agreed_to_terms: this.agreedToTerms,
                        cart_items: formattedCartItems,
                        order_summary: {
                            subtotal: this.parseFloat(subtotal),
                            coupon_discount: this.parseFloat(coupon_discount),
                            subtotal_after_discount: this.parseFloat(subtotal_after_discount),
                            delivery_charge: this.parseFloat(delivery_charge),
                            final_total: this.parseFloat(final_total),
                        },
                    };
                }
            )
        );
    }
    get availableCouriers(): any[] {
        return this.enabledCouriers.filter(
            (c) => c.delivery_charge && c.delivery_charge > 0
        );
        
    }
}