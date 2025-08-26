import { Component, OnInit, OnDestroy } from '@angular/core';
import { ProductServiceTsService } from 'src/app/services/product.service.ts.service';
import { Product } from 'src/app/models/product.model';
import { Subscription } from 'rxjs';
import { Router } from '@angular/router';

@Component({
  selector: 'app-international-orders',
  templateUrl: './international-orders.component.html',
  styleUrls: ['./international-orders.component.css']
})
export class InternationalOrdersComponent implements OnInit, OnDestroy {
  filteredProducts: Product[] = [];
  private productsSubscription: Subscription | undefined;

  constructor(
    private productService: ProductServiceTsService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.fetchProducts();
  }

  fetchProducts(): void {
    this.productsSubscription = this.productService.getInternationalProducts().subscribe({
      next: (products) => {
        this.filteredProducts = products;
      },
      error: (err) => {
        console.error('Failed to fetch international products:', err);
      }
    });
  }

  getStarArray(rating: number): boolean[] {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      stars.push(i <= Math.floor(rating));
    }
    return stars;
  }

  getDiscountPercentage(mrp: string, special: string): number {
    const mrpNum = parseFloat(mrp);
    const specialNum = parseFloat(special);

    if (mrpNum > 0 && mrpNum !== specialNum) {
      return ((1 - (specialNum / mrpNum)) * 100);
    }
    return 0;
  }

  // Updated method to navigate to the detail page using the product slug
  viewProductDetails(product: Product): void {
    this.router.navigate(['/international-orders', this.slugify(product.name)]);
  }

  ngOnDestroy(): void {
    if (this.productsSubscription) {
      this.productsSubscription.unsubscribe();
    }
  }

  slugify(text: string): string {
    if (!text) return '';
    return text.toString().toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^\w\-]+/g, '')
      .replace(/\-\-+/g, '-')
      .replace(/^-+/, '')
      .replace(/-+$/, '');
  }
}
