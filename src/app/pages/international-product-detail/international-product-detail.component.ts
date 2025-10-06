import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ProductServiceTsService } from 'src/app/services/product.service.ts.service';
import { Product } from 'src/app/models/product.model';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-international-product-detail',
  templateUrl: './international-product-detail.component.html',
  styleUrls: ['./international-product-detail.component.css']
})
export class InternationalProductDetailComponent implements OnInit, OnDestroy {
  product: Product | undefined;
  orderForm: FormGroup;
  isSubmitting = false;
  submissionSuccess = false;
  private productSubscription: Subscription | undefined;
  private routeSubscription: Subscription | undefined;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private productService: ProductServiceTsService,
    private fb: FormBuilder
  ) {
    this.orderForm = this.fb.group({
      name: ['', Validators.required],
      phoneNumber: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      address: ['', Validators.required],
      quantity: [1, [Validators.required, Validators.min(1)]]
    });
  }

  ngOnInit(): void {
    this.routeSubscription = this.route.paramMap.subscribe(params => {
      const productId = params.get('id');
      if (productId) {
        this.fetchProductDetail(+productId);
      }
    });
  }

  fetchProductDetail(id: number): void {
    this.productSubscription = this.productService.getProductDetail(id).subscribe({
      next: (product) => {
        if (product) {
          this.product = product;
        } else {
          // Redirect if product is not found
          this.router.navigate(['/international-orders']);
        }
      },
      error: (err) => {
        console.error('Failed to fetch product details:', err);
        this.router.navigate(['/international-orders']);
      }
    });
  }

  onSubmit(): void {
    if (this.orderForm.valid) {
      this.isSubmitting = true;
      const formData = {
        ...this.orderForm.value,
        productId: this.product?.id,
        productName: this.product?.name,
      };

      this.productService.submitInternationalOrder(formData).subscribe({
        next: (response) => {
          this.isSubmitting = false;
          if (response.status === 'success') {
            this.submissionSuccess = true;
          } else {
            alert('Order submission failed. Please try again.');
          }
        },
        error: (err) => {
          this.isSubmitting = false;
          console.error('Order submission error:', err);
          alert('An error occurred. Please try again later.');
        }
      });
    } else {
      this.orderForm.markAllAsTouched();
    }
  }

  ngOnDestroy(): void {
    if (this.productSubscription) {
      this.productSubscription.unsubscribe();
    }
    if (this.routeSubscription) {
      this.routeSubscription.unsubscribe();
    }
  }
}
