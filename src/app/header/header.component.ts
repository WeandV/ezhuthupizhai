import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, Subscription, firstValueFrom } from 'rxjs';
import { HttpClient } from '@angular/common/http';

import { CartService } from '../services/cart.service';
import { UserService } from '../services/user.service';
import { User } from '../services/user.service';

import { CartItem } from 'src/app/models/cart-item.model';
import { AppliedCoupon } from 'src/app/models/applied-coupon.model';

import { environment } from 'src/app/environments/environment';

declare var Razorpay: any;

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit, OnDestroy {
  cartItemCount: number = 0;
  cartItems: CartItem[] = [];

  cartSubtotal: number = 0;
  couponDiscount: number = 0;
  finalTotal: number = 0;
  cartTotal: number = 0;

  freeShippingThreshold: number = 500;

  currentlyAppliedCoupon$: Observable<AppliedCoupon | null>;
  availableCoupons$: Observable<AppliedCoupon[]>;

  isLoggedIn: boolean = false;
  private userSubscription: Subscription = new Subscription();

  private cartSubscription: Subscription | undefined;
  private totalSubscription: Subscription | undefined;

  constructor(
    private cartService: CartService,
    private userService: UserService,
    private router: Router,
    private http: HttpClient
  ) {
    this.currentlyAppliedCoupon$ = this.cartService.getCurrentlyAppliedCoupon();
    this.availableCoupons$ = this.cartService.getAllAvailableCoupons();
  }

  ngOnInit(): void {
    this.cartSubscription = this.cartService.cartItems$.subscribe(items => {
      this.cartItems = items;
      this.cartItemCount = items.reduce((totalCount, item) => totalCount + item.quantity, 0);
    });

    this.totalSubscription = this.cartService.cartTotal$.subscribe(() => {
      this.cartSubtotal = this.cartService.getCartSubtotal();
      this.finalTotal = this.cartService.getCartTotalWithDiscount();
      this.couponDiscount = this.cartSubtotal - this.finalTotal;
      this.cartTotal = this.finalTotal;
    });

    this.userSubscription.add(
      this.userService.currentUser$.subscribe(user => {
        this.isLoggedIn = user !== null;
      })
    );
  }

  removeFromCart(productId: number): void {
    this.cartService.removeFromCart(productId);
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

  onApplyCoupon(couponCode: string): void {
    const result = this.cartService.applyCouponByCode(couponCode);
    if (result && !result.success) {
      alert(result.message);
    }
  }

  onRemoveCoupon(couponCode: string): void {
    this.cartService.removeCoupon(couponCode);
  }

  onLogout(): void {
    this.userService.clearUser();
    this.router.navigate(['/']);
  }

  onLoginRegister(): void {
    this.router.navigate(['/login']);
  }

  async startMagicCheckout(): Promise<void> {
    if (!this.cartItems || this.cartItems.length === 0) {
      alert('Your cart is empty.');
      return;
    }

    const shippingDetails = {
      firstName: 'Guest',
      lastName: '',
      email: 'guest@example.com',
      phone: '9999999999',
      address1: 'N/A',
      city: 'N/A',
      state: 'N/A',
      zipCode: '000000',
      country: 'IN'
    };

    // 1. Calculate totals and explicitly round them to ensure they are integers for Razorpay
    const subtotal = Math.round(this.cartService.getCartSubtotal());
    const finalTotal = Math.round(this.cartService.getCartTotalWithDiscount());
    const couponDiscount = subtotal - finalTotal;

    // 2. Clean cart items for the payload (removing large short_description and keeping essential fields)
    const cleanedCartItems = this.cartItems.map(item => ({
      quantity: item.quantity,
      effectivePrice: item.effectivePrice,
      mrpPriceNumeric: item.mrpPriceNumeric,
      product: {
        id: item.product.id,
        name: item.product.name,
        tamil_name: item.product.tamil_name,
        sku: item.product.sku,
      }
    }));

    const payload = {
      shipping_details: shippingDetails,
      order_summary: {
        subtotal: subtotal,
        coupon_discount: couponDiscount,
        subtotal_after_discount: finalTotal, // Now a clean integer
        delivery_charge: 0,
        final_total: finalTotal // Now a clean integer
      },
      cart_items: cleanedCartItems // Use the optimized cart item list
    };

    try {
      const response: any = await firstValueFrom(
        this.http.post(environment.apiUrl + 'checkout/place_order_razorpay', payload)
      );

      if (response.success) {
        this.initiateRazorpayPayment(response);
      } else {
        alert(response.message || 'Failed to create Razorpay order');
      }
    } catch (err) {
      console.error('Error creating Razorpay order:', err);
      alert('An error occurred. Please try again.');
    }
  }


  private initiateRazorpayPayment(response: any): void {
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
        const finalPayload = {
          shipping_details: response.prefill,
          order_summary: {
            subtotal: response.amount / 100,
            subtotal_after_discount: response.amount / 100,
            delivery_charge: 0,
            final_total: response.amount / 100
          },
          cart_items: this.cartItems,
          razorpay_payment_id: razorpayResponse.razorpay_payment_id,
          razorpay_order_id: razorpayResponse.razorpay_order_id,
          razorpay_signature: razorpayResponse.razorpay_signature
        };

        this.http.post(environment.apiUrl + 'checkout/verify_payment', finalPayload)
          .subscribe({
            next: (verifyResponse: any) => {
              if (verifyResponse.success) {
                this.cartService.clearCart();
                this.router.navigate(['/order-confirmation', verifyResponse.order_id]);
              } else {
                alert('Payment verification failed.');
              }
            },
            error: (err) => {
              console.error('Verification error:', err);
              alert('Payment verification error.');
            }
          });
      },
      modal: {
        ondismiss: () => {
          alert('Payment was cancelled.');
        }
      }
    };

    const rzp = new Razorpay(options);
    rzp.open();
  }

  getShippingProgress(): number {
    if (this.freeShippingThreshold === 0) return 100;
    return Math.min((this.cartTotal / this.freeShippingThreshold) * 100, 100);
  }

  ngOnDestroy(): void {
    if (this.cartSubscription) {
      this.cartSubscription.unsubscribe();
    }
    if (this.totalSubscription) {
      this.totalSubscription.unsubscribe();
    }
    if (this.userSubscription) {
      this.userSubscription.unsubscribe();
    }
  }
}
