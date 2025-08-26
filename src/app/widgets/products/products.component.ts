import { Component, OnInit, OnDestroy } from '@angular/core';
import { ProductServiceTsService } from 'src/app/services/product.service.ts.service';
import { CartService } from 'src/app/services/cart.service.ts.service';

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

  constructor(
    private productService: ProductServiceTsService,
    private cartService: CartService,
  ) { }

  ngOnInit(): void {
    this.fetchCategories();
    this.fetchProducts();
  }

  fetchProducts(): void {
    this.productsSubscription = this.productService.getProducts().subscribe({
      next: (products) => {
        this.allProducts = products;
        this.filterProducts(this.activeFilter);
        this.allProducts.forEach(product => {
          this.quickViewQuantities[product.id] = 1;
        });
      },
      error: (err) => {
        // Error handling for fetching products
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

  filterProducts(category: string): void {
    this.activeFilter = category;
    let baseFilteredProducts = this.allProducts.filter(product =>
      !product.categories.includes('byob') // <--- Changed 'byod' to 'byob' here
    );

    if (category === 'ALL') {
      this.filteredProducts = [...baseFilteredProducts];
    } else {
      this.filteredProducts = baseFilteredProducts.filter(product =>
        product.categories.includes(category)
      );
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
}
