import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { UserService, User } from '../../services/user.service'; // Adjust path if needed
import { Subscription } from 'rxjs';
import { take } from 'rxjs/operators'; // Import take operator

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, OnDestroy {
  currentUser: User | null = null;
  totalOrders: number = 0; // Property for total orders
  pendingOrders: number = 0; // Property for pending orders
  private userSubscription: Subscription = new Subscription();

  constructor(private router: Router, private userService: UserService) { }

  ngOnInit(): void {
    this.userSubscription.add(
      this.userService.currentUser$.subscribe(user => {
        this.currentUser = user;
        if (!user) {
          this.router.navigate(['/login']);
        } else {
          this.fetchOrderSummaries(user.id);
        }
      })
    );
  }

  /**
   * 
   * Fetches the total and pending order counts for the current user.
   * @param userId The ID of the logged-in user.
   */
  fetchOrderSummaries(userId: number): void {
    // Call UserService method to get total orders
    this.userService.getTotalOrders(userId).pipe(take(1)).subscribe({
      next: (response) => {
        if (response.success && response.totalOrders !== undefined) {
          this.totalOrders = response.totalOrders;
        } else {
          console.warn('Failed to fetch total orders:', response.message);
          this.totalOrders = 0; // Default to 0 on failure
        }
      },
      error: (error) => {
        console.error('Error fetching total orders:', error);
        this.totalOrders = 0; // Default to 0 on error
      }
    });

    // Call UserService method to get pending orders
    this.userService.getPendingOrders(userId).pipe(take(1)).subscribe({
      next: (response) => {
        if (response.success && response.pendingOrders !== undefined) {
          this.pendingOrders = response.pendingOrders;
        } else {
          console.warn('Failed to fetch pending orders:', response.message);
          this.pendingOrders = 0; // Default to 0 on failure
        }
      },
      error: (error) => {
        console.error('Error fetching pending orders:', error);
        this.pendingOrders = 0; // Default to 0 on error
      }
    });
  }

  onLogout(): void {
    this.userService.clearUser(); // Use UserService to clear user state
    // The subscription in ngOnInit will handle the navigation to /login
  }

  ngOnDestroy(): void {
    if (this.userSubscription) {
      this.userSubscription.unsubscribe(); // Unsubscribe to prevent memory leaks
    }
  }
}
