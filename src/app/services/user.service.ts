import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { environment } from '../environments/environment';

export interface Address {
  id?: number;
  user_id?: number;
  first_name: string;
  last_name?: string | null;
  phone: string;
  email: string;
  address1: string;
  address2?: string | null;
  city: string;
  state: string;
  zip_code: string;
  country: string;
  type?: string;
  is_default_billing?: number;
  is_default_shipping?: number;
  is_active?: number;
  created_at?: string;
  updated_at?: string;
}

export interface User {
  id: number;
  first_name?: string;
  last_name?: string | null;
  email: string;
  phone?: string;
  address1?: string | null;
  address2?: string | null;
  city?: string;
  state?: string;
  zip_code?: string;
  country?: string;
  token?: string;
}

export interface Order {
  id: string;
  date: Date;
  status: string;
  total: number;
  order_status: string; // <-- CORRECTED: Changed from number to string
  payment_method: string;
}

@Injectable({
  providedIn: 'root'
})
export class UserService {
  // Initialize BehaviorSubject with the user loaded from localStorage
  private currentUserSubject = new BehaviorSubject<User | null>(this.loadUserFromLocalStorageSync());
  currentUser$: Observable<User | null> = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
    // The user is already loaded in the BehaviorSubject's initialization,
    // so no need to call loadUserFromLocalStorage() again here.
  }

  // Renamed to `setCurrentUser` for consistency with common patterns,
  // but it performs the same function as your original `setUser`.
  setUser(user: User): void { // <--- THIS IS THE METHOD THE COMPILER IS LOOKING FOR
    this.currentUserSubject.next(user);
    localStorage.setItem('currentUser', JSON.stringify(user));
  }

  clearUser(): void {
    this.currentUserSubject.next(null);
    localStorage.removeItem('currentUser');
  }

  // Changed to synchronous to be used during BehaviorSubject initialization
  private loadUserFromLocalStorageSync(): User | null {
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
      try {
        const user: User = JSON.parse(storedUser);
        return user;
      } catch (e) {
        console.error('Error parsing user from localStorage', e);
        localStorage.removeItem('currentUser'); // Clear invalid data
        return null;
      }
    }
    return null;
  }

  sendOtp(email: string): Observable<any> {
    return this.http.post(`${environment.apiUrl}auth/send_otp`, { email });
  }

  verifyOtp(email: string, otp: string): Observable<any> {
    return this.http.post(`${environment.apiUrl}auth/verify_otp_and_get_addresses`, { email, otp });
  }

  getCustomerDetails(userId: string): Observable<any> {
    return this.http.get(`${environment.apiUrl}customer/details/${userId}`);
  }

  getUserAddresses(userId: number): Observable<{ success: boolean, addresses: Address[], message?: string }> {
    return this.http.post<{ success: boolean, addresses: Address[], message?: string }>(`${environment.apiUrl}auth/get_addresses_by_user_id`, { user_id: userId });
  }

  getTotalOrders(userId: number): Observable<{ success: boolean, totalOrders?: number, message?: string }> {
    return this.http.get<{ success: boolean, totalOrders?: number, message?: string }>(`${environment.apiUrl}orders/total/${userId}`);
  }

  getPendingOrders(userId: number): Observable<{ success: boolean, pendingOrders?: number, message?: string }> {
    return this.http.get<{ success: boolean, pendingOrders?: number, message?: string }>(`${environment.apiUrl}orders/pending/${userId}`);
  }

  getOrders(userId: number): Observable<{ success: boolean, orders: any[], message?: string }> {
    return this.http.post<{ success: boolean, orders: any[], message?: string }>(`${environment.apiUrl}api/get_user_orders`, { userId });
  }

}
