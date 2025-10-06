import { Component, OnInit, OnDestroy } from '@angular/core';
import { ProductServiceTsService } from 'src/app/services/product.service.ts.service';
import { CartService } from 'src/app/services/cart.service';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

import { Product } from 'src/app/models/product.model';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-products',
  templateUrl: './products.component.html',
  styleUrls: ['./products.component.css']
})
export class ProductsComponent implements OnInit, OnDestroy {

  allProducts: Product[] = [];
  filteredProducts: Product[] = [];
  categories: string[] = [];
  activeFilter: string = 'ALL';
  quickViewQuantities: { [productId: number]: number } = {};
  private productsSubscription: Subscription | undefined;
  private categoriesSubscription: Subscription | undefined;
  activeAccordionIndex: number = 0;
  sortOrder: 'newest' | 'oldest' = 'newest';

  constructor(
    private productService: ProductServiceTsService,
    private cartService: CartService,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.fetchCategories();
    this.fetchProducts();
  }

  fetchProducts(): void {
    this.productsSubscription = this.productService.getProducts().subscribe({
      next: (products) => {
        this.allProducts = products.map(product => {
          try {
            const parsed = typeof product.short_description === 'string'
              ? JSON.parse(product.short_description)
              : [];
            product.accordion = parsed.map((section: any) => ({
              title: section.title,
              description: section.description
            }));
          } catch {
            product.accordion = [];
          }
            product.hasCoupon = !!product.appliedCoupon || !!product.offers;

          return product;
        });
        this.sortProducts();

        this.filterProducts(this.activeFilter);
        this.allProducts.forEach(product => {
          this.quickViewQuantities[product.id] = 1;
        });
      },
      error: (err) => {
      }
    });
  }

  fetchCategories(): void {
    this.categoriesSubscription = this.productService.getCategories().subscribe({
      next: (categories) => {
        this.categories = ['ALL', ...categories.filter(cat => cat !== 'ALL' && cat !== 'byob')];
      },
    });
  }

  sortProducts(): void {
    // Sort by product ID. Newer products typically have a higher ID.
    if (this.sortOrder === 'newest') {
      this.allProducts.sort((a, b) => b.id - a.id);
    } else {
      this.allProducts.sort((a, b) => a.id - b.id);
    }
  }

  toggleSortOrder(): void {
    this.sortOrder = this.sortOrder === 'newest' ? 'oldest' : 'newest';
    this.sortProducts(); // Apply the new sort order
    this.filterProducts(this.activeFilter); // Re-apply the filter to the sorted list
  }


  filterProducts(category: string): void {
    this.activeFilter = category;
    let baseFilteredProducts = this.allProducts.filter(product =>
      !product.categories.includes('byob')
    );

    if (category === 'ALL') {
      this.filteredProducts = [...baseFilteredProducts];
    } else {
      this.filteredProducts = baseFilteredProducts.filter(product =>
        product.categories.includes(category)
      );
    }
  }

  toggleAccordion(index: number): void {
    // This prevents closing the currently open item by not changing the index if it's the same
    if (this.activeAccordionIndex !== index) {
      this.activeAccordionIndex = index;
    }
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

  getQuickViewQuantity(productId: number): number {
    if (!this.quickViewQuantities[productId] || this.quickViewQuantities[productId] < 1) {
      this.quickViewQuantities[productId] = 1;
    }
    return this.quickViewQuantities[productId];
  }

  onQuickViewQuantityChange(productId: number, event: Event): void {
    const inputElement = event.target as HTMLInputElement;
    let newQuantity = Number(inputElement.value);

    if (isNaN(newQuantity) || newQuantity < 1) {
      newQuantity = 1;
    }
    this.quickViewQuantities[productId] = newQuantity;
  }

  incrementQuickViewQuantity(product: Product): void {
    const currentQuantity = this.getQuickViewQuantity(product.id);
    this.quickViewQuantities[product.id] = currentQuantity + 1;
  }

  decrementQuickViewQuantity(product: Product): void {
    const currentQuantity = this.getQuickViewQuantity(product.id);
    if (currentQuantity > 1) {
      this.quickViewQuantities[product.id] = currentQuantity - 1;
    }
  }

  onAddToCart(product: Product, quantity: number = 1): void {
    const quantityToAdd = Math.max(1, quantity);
    this.cartService.addToCart(product, quantityToAdd);
    this.quickViewQuantities[product.id] = 1;
  }

  ngOnDestroy(): void {
    if (this.productsSubscription) {
      this.productsSubscription.unsubscribe();
    }
    if (this.categoriesSubscription) {
      this.categoriesSubscription.unsubscribe();
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

  getSanitizedOffers(html: string | null): SafeHtml {
    if (!html) {
      return '';
    }
    return this.sanitizer.bypassSecurityTrustHtml(html);
  }

}
