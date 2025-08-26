import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.component.html',
  styleUrls: ['./forgot-password.component.css']
})
export class ForgotPasswordComponent implements OnInit {
  // State variables for the form
  email: string = '';
  otp: string = '';
  newPassword: string = '';
  confirmPassword: string = '';

  // UI state
  currentStep: 'request_otp' | 'reset_password' = 'request_otp'; // Control which form is shown
  message: string = '';
  isSuccess: boolean = false;
  isLoading: boolean = false; // For loading indicators

  // API URLs from environment
  private readonly SEND_OTP_API_URL = `${environment.apiUrl}index.php/Auth/send_otp`;
  private readonly RESET_PASSWORD_API_URL = `${environment.apiUrl}index.php/Auth/reset_password`;

  constructor(private http: HttpClient, private router: Router) { }

  ngOnInit(): void {
    // Log initial step for debugging
    console.log('ForgotPasswordComponent initialized. Current step:', this.currentStep);
  }

  /**
   * Handles the request to send an OTP to the user's email.
   */
  onRequestOtp(): void {
    this.message = '';
    this.isSuccess = false;
    this.isLoading = true;

    if (!this.email) {
      this.message = 'Please enter your email address.';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    console.log('Attempting to send OTP for email:', this.email);

    this.http.post<any>(this.SEND_OTP_API_URL, { email: this.email }).subscribe({
      next: (response) => {
        this.isLoading = false;
        console.log('OTP send response:', response); // Log the full response
        if (response.success) {
          this.message = response.message || 'OTP sent to your email. Please check your inbox.';
          this.isSuccess = true;
          this.currentStep = 'reset_password'; // Change the step here
          console.log('OTP sent successfully. Changing step to:', this.currentStep); // Confirm step change
        } else {
          this.message = response.message || 'Failed to send OTP. Please try again.';
          this.isSuccess = false;
          console.error('OTP send failed:', response.message);
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.message = error.error?.message || 'An error occurred. Please try again later.';
        this.isSuccess = false;
        console.error('Error sending OTP (HTTP error):', error);
      }
    });
  }

  /**
   * Handles the password reset request after OTP verification.
   */
  onResetPassword(): void {
    this.message = '';
    this.isSuccess = false;
    this.isLoading = true;

    if (!this.otp || !this.newPassword || !this.confirmPassword) {
      this.message = 'Please fill in all fields (OTP, New Password, Confirm Password).';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    if (this.newPassword !== this.confirmPassword) {
      this.message = 'New password and confirm password do not match.';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    if (this.newPassword.length < 6) {
      this.message = 'Password must be at least 6 characters long.';
      this.isSuccess = false;
      this.isLoading = false;
      return;
    }

    const resetData = {
      email: this.email,
      otp: this.otp,
      new_password: this.newPassword
    };

    console.log('Attempting to reset password with data:', resetData);

    this.http.post<any>(this.RESET_PASSWORD_API_URL, resetData).subscribe({
      next: (response) => {
        this.isLoading = false;
        console.log('Password reset response:', response); // Log the full response
        if (response.success) {
          this.message = response.message || 'Password has been reset successfully! You can now log in.';
          this.isSuccess = true;
          setTimeout(() => {
            this.router.navigate(['/login']);
          }, 3000);
        } else {
          this.message = response.message || 'Failed to reset password. Invalid OTP or other error.';
          this.isSuccess = false;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.message = error.error?.message || 'An error occurred during password reset. Please try again later.';
        this.isSuccess = false;
        console.error('Error resetting password (HTTP error):', error);
      }
    });
  }

  /**
   * Allows the user to go back to the OTP request step.
   */
  backToRequestOtp(): void {
    this.currentStep = 'request_otp';
    this.message = '';
    this.otp = ''; // Clear OTP field
    this.newPassword = '';
    this.confirmPassword = '';
    console.log('Going back to request OTP step. Current step:', this.currentStep);
  }
}
