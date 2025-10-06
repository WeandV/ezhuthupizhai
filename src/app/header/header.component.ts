import { Component, OnInit, OnDestroy, NgZone } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription, Observable, firstValueFrom } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { CartService } from '../services/cart.service';
import { UserService } from '../services/user.service';
import { CartItem } from 'src/app/models/cart-item.model';
import { AppliedCoupon } from 'src/app/models/applied-coupon.model';
import { environment } from '../environments/environment';

declare var Razorpay: any;

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit, OnDestroy {
  cartItems: CartItem[] = [];
  cartItemCount: number = 0;
  cartTotal: number = 0;
  discountAmount: number = 0;
  deliveryCharge: number = 0;
  finalTotal: number = 0;
  freeShippingThreshold: number = 500;
  isLoggedIn: boolean = false;
  availableCoupons: AppliedCoupon[] = [];
  appliedCoupon: AppliedCoupon | null = null;
  currentlyAppliedCoupon$: Observable<AppliedCoupon | null>;

  // Courier/pincode
  pincode: string = '';
  enabledCouriers: any[] = [];
  selectedCourier: any = null;
  couriersLoading: boolean = false;
  courierErrorMessage: string | null = null;

  private subscriptions: Subscription = new Subscription();

  constructor(
    private cartService: CartService,
    private userService: UserService,
    private router: Router,
    private http: HttpClient,
    private zone: NgZone
  ) {
    this.currentlyAppliedCoupon$ = this.cartService.getCurrentlyAppliedCoupon();
  }

  ngOnInit(): void {
    this.subscriptions.add(
      this.cartService.cartItems$.subscribe(items => {
        this.cartItems = items;
        this.cartItemCount = items.reduce((total, item) => total + item.quantity, 0);
        this.calculateFinalTotal();
      })
    );

    this.subscriptions.add(
      this.cartService.cartTotal$.subscribe(total => {
        this.cartTotal = total;
        this.calculateFinalTotal();
      })
    );

    this.subscriptions.add(
      this.cartService.totalCouponDiscount$.subscribe(discount => {
        this.discountAmount = discount;
        this.calculateFinalTotal();
      })
    );

    this.subscriptions.add(
      this.cartService.deliveryCharge$.subscribe(charge => {
        this.deliveryCharge = this.cartItems.length > 0 ? charge : 0;
        this.calculateFinalTotal();
      })
    );

    this.subscriptions.add(
      this.cartService.getCurrentlyAppliedCoupon().subscribe(coupon => {
        this.appliedCoupon = coupon;
        this.calculateFinalTotal();
      })
    );

    this.subscriptions.add(
      this.cartService.getVisibleCoupons().subscribe(coupons => {
        this.availableCoupons = coupons;
      })
    );

    this.subscriptions.add(
      this.userService.currentUser$.subscribe(user => this.isLoggedIn = !!user)
    );
  }

  onPincodeChange(): void {
    this.fetchDeliveryChargeAndCouriers();
  }

  private fetchDeliveryChargeAndCouriers(): void {
    if (!this.pincode || !/^\d{6}$/.test(this.pincode)) {
      this.courierErrorMessage = 'Please enter a valid 6-digit pincode.';
      this.selectedCourier = null;
      this.cartService.setDeliveryCharge(0);
      return;
    }

    const pickupPincode = '600020';
    const cartWeight = this.calculateCartWeight();
    const cod = 0;

    const requestData = {
      pickup_pincode: pickupPincode,
      delivery_pincode: this.pincode,
      weight: cartWeight,
      cod
    };

    this.couriersLoading = true;
    this.cartService.getEnabledCouriers(requestData).subscribe({
      next: (response: any) => {
        this.couriersLoading = false;
        if (response.status === 'success' && response.enabled_couriers?.length > 0) {
          const validCouriers = response.enabled_couriers
            .map((c: any) => ({
              courier_id: c.courier_id,
              courier_name: c.courier_name,
              delivery_charge: Number(c.delivery_charge) || 0
            }))
            .filter((c: any) => c.delivery_charge > 0);

          if (validCouriers.length > 0) {
            const cheapest = validCouriers
              .sort((a: { delivery_charge: number }, b: { delivery_charge: number }) => a.delivery_charge - b.delivery_charge)[0];
            this.selectedCourier = cheapest;
            this.cartService.setDeliveryCharge(cheapest.delivery_charge);
          } else {
            this.selectedCourier = null;
            this.cartService.setDeliveryCharge(0);
            this.courierErrorMessage = 'No couriers available for this pincode.';
          }

          this.enabledCouriers = validCouriers;
        } else {
          this.selectedCourier = null;
          this.cartService.setDeliveryCharge(0);
          this.enabledCouriers = [];
          this.courierErrorMessage = response.message || 'No couriers available.';
        }
        this.calculateFinalTotal();
      },
      error: (err) => {
        console.error(err);
        this.couriersLoading = false;
        this.selectedCourier = null;
        this.cartService.setDeliveryCharge(0);
        this.enabledCouriers = [];
        this.courierErrorMessage = 'Error fetching delivery charges. Try again.';
        this.calculateFinalTotal();
      }
    });
  }

  onCourierSelected(courier: any): void {
    this.selectedCourier = courier;
    this.cartService.setDeliveryCharge(courier?.delivery_charge || 0);
    this.calculateFinalTotal();
  }

  private calculateCartWeight(): number {
    const defaultWeightKg = 0.5;
    let totalActualWeight = 0;
    let totalVolumetricWeight = 0;

    this.cartItems.forEach(item => {
      let itemWeight = this.parseFloat(item.product.weight_kg);
      if (itemWeight <= 0) itemWeight = defaultWeightKg;
      totalActualWeight += itemWeight * item.quantity;

      const length = this.parseFloat(item.product.length_cm);
      const width = this.parseFloat(item.product.breadth_cm);
      const height = this.parseFloat(item.product.height_cm);
      const volumetricWeight = length && width && height ? (length * width * height) / 5000 : defaultWeightKg;
      totalVolumetricWeight += volumetricWeight * item.quantity;
    });

    return Math.max(totalActualWeight, totalVolumetricWeight, 0.1);
  }

  private parseFloat(value: string | number | null | undefined): number {
    if (value == null || value === 0 || isNaN(Number(value))) return 0;
    return Number(value);
  }

  onApplyCoupon(coupon: AppliedCoupon): void {
    const result = this.cartService.applyCouponByCode(coupon.coupon_code);
    if (result.success) {
      this.appliedCoupon = coupon;
    }
    this.calculateFinalTotal();
  }

  removeFromCart(productId: number): void {
    this.cartService.removeFromCart(productId);
  }

  updateQuantity(productId: number, event: Event): void {
    const input = event.target as HTMLInputElement;
    const quantity = Number(input.value);
    if (!isNaN(quantity) && quantity >= 0) {
      this.cartService.updateQuantity(productId, quantity);
    }
  }

  incrementQuantity(productId: number, currentQuantity: number): void {
    this.cartService.updateQuantity(productId, currentQuantity + 1);
  }

  decrementQuantity(productId: number, currentQuantity: number): void {
    if (currentQuantity > 1) {
      this.cartService.updateQuantity(productId, currentQuantity - 1);
    } else {
      this.cartService.removeFromCart(productId);
    }
  }

  private calculateFinalTotal(): void {
    let subtotal = this.cartTotal;
    this.discountAmount = 0;

    if (this.appliedCoupon) {
      if (this.appliedCoupon.discount_type === 'fixed') {
        this.discountAmount = this.appliedCoupon.discount_value;
      } else if (this.appliedCoupon.discount_type === 'percentage') {
        this.discountAmount = subtotal * (this.appliedCoupon.discount_value / 100);
      } else if (this.appliedCoupon.discount_type === 'delivery_free') {
        this.discountAmount = this.deliveryCharge;
      }
    }

    const effectiveDelivery = this.cartItems.length > 0 ? this.deliveryCharge : 0;
    this.finalTotal = Math.max(0, subtotal - this.discountAmount + effectiveDelivery);
  }

  getShippingProgress(): number {
    if (this.freeShippingThreshold === 0) return 100;
    return Math.min((this.cartTotal / this.freeShippingThreshold) * 100, 100);
  }

  onLogout(): void {
    this.userService.clearUser();
    this.router.navigate(['/']);
  }

  onLoginRegister(): void {
    this.router.navigate(['/login']);
  }

  ngOnDestroy(): void {
    this.subscriptions.unsubscribe();
  }

  public paymentMessage: string | null = null;
  public isPaymentError: boolean = false;
  public isVerifyingPayment: boolean = false;

  async startMagicCheckout(): Promise<void> {
    if (!this.cartItems || this.cartItems.length === 0) {
      alert('Your cart is empty.');
      return;
    }

    if (!this.selectedCourier) {
      alert('Please enter a valid pincode and confirm delivery serviceability before checking out.');
      return;
    }

    const shippingDetails = {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      address1: 'N/A',
      city: 'N/A',
      state: 'N/A',
      zipCode: '000000',
      country: 'IN'
    };

    const subtotalBeforeCoupon = Number(this.cartTotal);
    const couponDiscount = Number(this.discountAmount);
    const deliveryCharge = Number(this.deliveryCharge);
    const finalTotal = Number(this.finalTotal);
    const subtotalAfterDiscount = subtotalBeforeCoupon - couponDiscount;
    const cleanedCartItems = this.cartItems.map(item => ({
      quantity: Number(item.quantity),
      effectivePrice: Number(item.effectivePrice), // 🔥 force number
      mrpPriceNumeric: Number(item.product.mrp_price) || Number(item.effectivePrice),
      product: {
        id: item.product.id,
        name: item.product.name,
        tamil_name: item.product.tamil_name,
        sku: item.product.sku,
        thumbnail_image: item.product.thumbnail_image,
        weight_kg: item.product.weight_kg,
        length_cm: item.product.length_cm,
        breadth_cm: item.product.breadth_cm,
        height_cm: item.product.height_cm
      }
    }));

    const payload = {
      shipping_details: shippingDetails,
      order_summary: {
        subtotal: Number(subtotalBeforeCoupon),
        coupon_discount: Number(couponDiscount),
        subtotal_after_discount: Number(subtotalAfterDiscount),
        delivery_charge: Number(deliveryCharge),
        final_total: Number(finalTotal)
      },
      cart_items: cleanedCartItems,
      pincode: this.pincode,
      courier_id: this.selectedCourier.courier_id
    };

    try {
      const response: any = await firstValueFrom(
        this.http.post(environment.apiUrl + 'checkout/place_order_Magic_razorpay', payload)
      );

      if (!response.success) {
        alert(response.message || 'Failed to create Razorpay order');
        return;
      }
      const options: any = {
        key: response.key,
        amount: response.amount,
        currency: response.currency,
        name: response.name,
        description: response.description,
        order_id: response.razorpayOrderId,
        prefill: response.prefill,
        theme: { color: '#0D6EFD' },
        one_click_checkout: true,
        handler: (razorpayResponse: any) => {
          this.zone.run(() => {
            const offcanvasElement = document.getElementById('offcanvasRight');
            if (offcanvasElement) {
              const bsOffcanvas = (window as any).bootstrap.Offcanvas.getInstance(offcanvasElement);
              if (bsOffcanvas) {
                bsOffcanvas.hide();
              } else {
                const newBsOffcanvas = new (window as any).bootstrap.Offcanvas(offcanvasElement);
                newBsOffcanvas.hide();
              }
            }
            // 🚀 Start Loader
            this.isVerifyingPayment = true;
            this.paymentMessage = null;
            this.isPaymentError = false;
          });

          this.http.post(environment.apiUrl + 'checkout/verify_magic_payment', {
            razorpay_payment_id: razorpayResponse.razorpay_payment_id,
            razorpay_order_id: response.razorpayOrderId,
            razorpay_signature: razorpayResponse.razorpay_signature
          })
            .subscribe({
              next: (res: any) => {
                // 🛑 All UI/State changes must be inside zone.run
                this.zone.run(() => {
                  this.isVerifyingPayment = false; // Stop Loader immediately

                  if (res.success) {
                    this.cartService.clearCart();
                    // 🚀 Navigation to confirmation page
                    this.router.navigate(['/order-confirmation', res.order_id]);

                    // ❌ The incorrect setTimeout has been removed ❌

                  } else {
                    this.isPaymentError = true;
                    this.paymentMessage = 'Payment verification failed: ' + res.message;
                  }
                });
              },
              error: (err) => {
                this.zone.run(() => {
                  this.isVerifyingPayment = false; // Stop Loader on error
                  console.error('Verification API Error:', err);
                  this.isPaymentError = true;
                  this.paymentMessage = 'An error occurred during payment verification. Please try again.';
                });
              }
            });
        },
        modal: {
          ondismiss: () => {
            this.zone.run(() => {
              this.isPaymentError = false;
              this.paymentMessage = 'Payment was cancelled. You can try again.';
              setTimeout(() => {
                this.zone.run(() => {
                  this.paymentMessage = null;
                });
              }, 5000);
            });
          }
        }
      };

      const rzp = new Razorpay(options);
      rzp.open();

    } catch (err) {
      console.error('Error creating Razorpay order:', err);
      alert('An error occurred. Please try again.');
    }
  }

}