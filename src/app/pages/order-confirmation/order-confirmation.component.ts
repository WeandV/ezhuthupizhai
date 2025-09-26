import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router'; // Import Router
import { CartService } from '../../services/cart.service';
import { Order } from 'src/app/models/order.model';

@Component({
  selector: 'app-order-confirmation',
  templateUrl: './order-confirmation.component.html',
  styleUrls: ['./order-confirmation.component.css']
})
export class OrderConfirmationComponent implements OnInit {
  orderId: string | null = null;
  orderDetails: Order | null = null;
  isLoading: boolean = true;
  hasError: boolean = false;
  errorMessage: string = '';
  private readonly ORDER_CONFIRMED_KEY = 'orderConfirmed_';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cartService: CartService
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      this.orderId = params.get('orderId');

      if (!this.orderId) {
        this.handleErrorAndRedirect('No Order ID provided for confirmation.', true);
        return;
      }

      const storageKey = this.ORDER_CONFIRMED_KEY + this.orderId;

      if (sessionStorage.getItem(storageKey) === 'true') {
        this.handleErrorAndRedirect('Order confirmation already viewed.', true);
        return;
      }
      this.isLoading = true;
      this.hasError = false;
      this.errorMessage = '';

      this.cartService.getOrderDetails(this.orderId).subscribe({
        next: (response) => {
          if (response.success && response.order_details) {
            this.orderDetails = response.order_details;
            sessionStorage.setItem(storageKey, 'true');
            this.isLoading = false;
          } else {
            const message = response.message || 'Failed to load order details.';
            this.handleErrorAndRedirect(message, true);
          }
        },
        error: (err) => {
          const message = 'An error occurred while fetching order details. Please try again.';
          this.handleErrorAndRedirect(message, true);
        }
      });
    });
  }
  private handleErrorAndRedirect(message: string, redirect: boolean): void {
    this.hasError = true;
    this.errorMessage = message;
    this.isLoading = false;

    if (redirect) {
      setTimeout(() => {
        this.router.navigate(['/']);
      }, 100);
    }
  }
}