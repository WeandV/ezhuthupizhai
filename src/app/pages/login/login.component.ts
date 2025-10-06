// src/app/login/login.component.ts

import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';
import { UserService, User } from 'src/app/services/user.service';

@Component({
  selector: 'app-admin-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  // For password-based login
  user = {
    email: '',
    password: ''
  };

  // For OTP-based login
  otpEmail: string = '';
  otpCode: string = '';
  otpLoginStep: 'request_otp' | 'verify_otp' = 'request_otp'; // Controls OTP flow steps

  // General UI state
  loginMode: 'password' | 'otp' = 'password'; // Controls overall login mode
  message: string = '';
  isSuccess: boolean = false;
  isLoading: boolean = false;
  passwordVisible: boolean = false;
  passwordFieldType: string = 'password';

  // API URLs
  private readonly LOGIN_API_URL = `${environment.apiUrl}Auth/login`;
  private readonly SEND_OTP_API_URL = `${environment.apiUrl}Auth/send_otp`;
  private readonly VERIFY_OTP_API_URL = `${environment.apiUrl}Auth/verify_otp_and_get_addresses`;

  constructor(
    private http: HttpClient,
    private router: Router,
    private userService: UserService // Inject UserService
  ) { }

  ngOnInit(): void {
    // No change needed here
  }

  /**
   * Toggles between password login and OTP login modes.
   * @param mode 'password' or 'otp'
   */
  changeLoginMode(mode: 'password' | 'otp'): void {
    this.loginMode = mode;
    this.message = ''; // Clear messages when switching modes
    this.isSuccess = false;
    this.isLoading = false;
    // Reset OTP specific fields if switching to OTP mode
    if (mode === 'otp') {
      this.otpLoginStep = 'request_otp';
      this.otpCode = '';
      // Keep otpEmail if user typed it in password form, otherwise clear
      if (this.user.email) {
        this.otpEmail = this.user.email;
      } else {
        this.otpEmail = '';
      }
    } else { // If switching back to password mode
      this.user.email = this.otpEmail; // Pre-fill email from OTP form if available
    }
  }

  /**
   * Toggles the visibility of the password field and the eye icon.
   */
  togglePasswordVisibility(): void {
    this.passwordVisible = !this.passwordVisible;
    this.passwordFieldType = this.passwordVisible ? 'text' : 'password';
  }

  /**
   * Handles password-based login submission.
   */
  onLogin(): void {
    this.message = '';
    this.isSuccess = false;
    this.isLoading = true;

    if (!this.user.email || !this.user.password) {
        this.message = 'Please fill in both email and password fields.';
        this.isSuccess = false;
        this.isLoading = false;
        return;
    }

    this.http.post<any>(this.LOGIN_API_URL, this.user).subscribe({
      next: (response) => {
        this.isLoading = false;
        if (response.status === 'success') {
          this.message = response.message;
          this.isSuccess = true;
          console.log('Login successful:', response.user);
          // *** CORRECTED LINE: Use userService.setUser ***
          const loggedInUser: User = {
            id: response.user.id,
            email: response.user.email,
            first_name: response.user.first_name,
            last_name: response.user.last_name,
            // Add other properties if they exist in response.user and your User interface
          };
          this.userService.setUser(loggedInUser); // <--- CHANGED FROM setCurrentUser to setUser
          this.router.navigate(['/dashboard']);
        } else {
          this.message = response.message || 'Login failed. Please try again.';
          this.isSuccess = false;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.message = 'An error occurred during login. Please try again later.';
        this.isSuccess = false;
        console.error('HTTP Error:', error);
        if (error.status === 0) {
          this.message = 'Could not connect to the backend. Is the server running?';
        } else if (error.error && error.error.message) {
          this.message = `Error: ${error.error.message}`;
        }
      }
    });
  }

  /**
   * Handles the request to send an OTP for OTP-based login.
   */
  onRequestOtp(): void {
    this.message = '';
    this.isSuccess = false;
    this.isLoading = true;

    if (!this.otpEmail) {
      this.message = 'Please enter your email address.';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    this.http.post<any>(this.SEND_OTP_API_URL, { email: this.otpEmail }).subscribe({
      next: (response) => {
        this.isLoading = false;
        if (response.success) {
          this.message = response.message || 'OTP sent to your email. Please check your inbox.';
          this.isSuccess = true;
          this.otpLoginStep = 'verify_otp'; // Move to the OTP verification step
        } else {
          this.message = response.message || 'Failed to send OTP. Please try again.';
          this.isSuccess = false;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.message = error.error?.message || 'An error occurred. Please try again later.';
        this.isSuccess = false;
        console.error('Error sending OTP:', error);
      }
    });
  }

  /**
   * Handles the OTP verification for login.
   */
  onVerifyOtp(): void {
    this.message = '';
    this.isSuccess = false;
    this.isLoading = true;

    if (!this.otpEmail || !this.otpCode) {
      this.message = 'Please enter both email and OTP.';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    const verificationData = {
      email: this.otpEmail,
      otp: this.otpCode
    };

    this.http.post<any>(this.VERIFY_OTP_API_URL, verificationData).subscribe({
      next: (response) => {
        this.isLoading = false;
        if (response.success) {
          this.message = response.message || 'OTP verified successfully! Logging in...';
          this.isSuccess = true;
          // *** CORRECTED LINE: Use userService.setUser ***
          const loggedInUser: User = {
            id: response.user_id, // Assuming user_id is returned
            email: response.email,
            phone: response.phone,
            // Add other user properties that verify_otp_and_get_addresses might return
            // e.g., first_name, last_name if your backend provides them here
          };
          this.userService.setUser(loggedInUser); // <--- CHANGED FROM setCurrentUser to setUser
          this.router.navigate(['/dashboard']);
        } else {
          this.message = response.message || 'OTP verification failed. Invalid OTP or other error.';
          this.isSuccess = false;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.message = error.error?.message || 'An error occurred during OTP verification. Please try again later.';
        this.isSuccess = false;
        console.error('Error verifying OTP:', error);
      }
    });
  }

  /**
   * Allows the user to go back to the OTP request step within the OTP login flow.
   */
  backToOtpRequest(): void {
    this.otpLoginStep = 'request_otp';
    this.message = '';
    this.otpCode = ''; // Clear OTP field
  }
}
