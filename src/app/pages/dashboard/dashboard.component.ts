import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { UserService, User, Order } from 'src/app/services/user.service';
import { Subscription, forkJoin } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, OnDestroy {
  currentUser: User | null = null;
  totalOrders: number = 0;
  pendingOrders: number = 0;
  orders: Order[] = [];
  private userSubscription: Subscription = new Subscription();
  loading: boolean = true; // Start in loading state

  constructor(private router: Router, private userService: UserService) { }

  ngOnInit(): void {
    this.userSubscription.add(
      this.userService.currentUser$.subscribe(user => {
        this.currentUser = user;
        if (!user) {
          // If no user is logged in, navigate to the login page
          this.router.navigate(['/login']);
        } else {
          // If a user is logged in, fetch all dashboard data
          this.loadDashboardData(user.id);
        }
      })
    );
  }

  /**
   * Fetches all necessary data for the dashboard in parallel.
   * @param userId The ID of the logged-in user.
   */
  loadDashboardData(userId: number): void {
    this.loading = true;

    this.userSubscription.add(
      forkJoin({
        total: this.userService.getTotalOrders(userId),
        pending: this.userService.getPendingOrders(userId),
        orderList: this.userService.getOrders(userId)
      })
        .pipe(
          // This will run when the forkJoin completes, either successfully or with an error.
          finalize(() => this.loading = false)
        )
        .subscribe({
          next: (responses) => {
            if (responses.total.success) {
              this.totalOrders = responses.total.totalOrders ?? 0;
            }
            if (responses.pending.success) {
              this.pendingOrders = responses.pending.pendingOrders ?? 0;
            }

            console.log('API Response:', responses.orderList.orders); // Is this an array of 4?
            if (responses.orderList.success) {
              this.orders = responses.orderList.orders.map((order: any) => {
                return {
                  id: order.id,
                  date: new Date(order.created_at),
                  status: order.status,
                  total: order.final_total,
                  payment_method: order.payment_method,
                  order_status: order.status
                };
              });
            }
          },
          error: (error: HttpErrorResponse) => {
            console.error('Error fetching dashboard data:', error);
            // Optionally, show a toast notification or an error message to the user
          }
        })
    );
  }

  onLogout(): void {
    this.userService.clearUser();
  }

  ngOnDestroy(): void {
    if (this.userSubscription) {
      this.userSubscription.unsubscribe();
    }
  }
}