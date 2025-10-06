import { Component, OnInit, AfterViewInit, OnDestroy, ElementRef, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ProductServiceTsService } from 'src/app/services/product.service.ts.service';
import { CartService } from 'src/app/services/cart.service';
import { Product } from 'src/app/models/product.model';
import { ProductImage } from 'src/app/models/product-image.model';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

declare var $: any;

@Component({
  selector: 'app-product-details',
  templateUrl: './product-details.component.html',
  styleUrls: ['./product-details.component.css']
})
export class ProductDetailsComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChild('elfsightWidgetContainer', { static: true }) elfsightWidgetContainer!: ElementRef;
  product: Product | undefined;
  videos: any[] = [];
  quickViewQuantities: { [productId: number]: number } = {};

  private destroy$ = new Subject<void>();

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private productService: ProductServiceTsService,
    private cartService: CartService,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.route.paramMap.pipe(takeUntil(this.destroy$)).subscribe(params => {
      const slug = params.get('slug');
      if (slug) {
        this.fetchProductBySlug(slug);
      } else {
        this.router.navigate(['/products']);
      }
    });
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.initializeMagnificPopup(), 100);
    const script = document.createElement('script');
    script.src = this.sanitizer.bypassSecurityTrustResourceUrl('https://static.elfsight.com/platform/platform.js') as string;
    script.async = true;
    const div = document.createElement('div');
    div.className = 'elfsight-app-856019a4-b468-4aa8-9d7c-ee22b1e0b532';
    div.setAttribute('data-elfsight-app-lazy', '');
    this.elfsightWidgetContainer.nativeElement.appendChild(script);
    this.elfsightWidgetContainer.nativeElement.appendChild(div);
  }


  fetchProductBySlug(slug: string): void {
    this.productService.getProductBySlug(slug).pipe(takeUntil(this.destroy$)).subscribe({
      next: (data: Product | null) => {
        if (data) {
          this.product = {
            ...data,
            images: data.images || [],
          };

          // Parse accordion from short_description (like products list)
          try {
            const parsed = typeof data.short_description === 'string'
              ? JSON.parse(data.short_description)
              : [];
            this.product.accordion = parsed.map((section: any) => ({
              title: section.title,
              description: section.description
            }));
          } catch {
            this.product.accordion = [];
          }

          this.productService.getVideosByProductId(this.product.id)
            .pipe(takeUntil(this.destroy$))
            .subscribe({
              next: (res: any) => {
                if (res.status === 'success') {
                  this.videos = res.data;
                }
              },
              error: (err) => console.error('Error fetching videos:', err)
            });
          this.quickViewQuantities[this.product.id] = 1;
          setTimeout(() => this.initializeMagnificPopup(), 100);
        } else {
          this.router.navigate(['/404']);
        }
      },
      error: (error) => {
        console.error('Error fetching product by slug:', error);
        this.router.navigate(['/404']);
      }
    });
  }
  private parseJsonField(field: any): any[] {
    if (typeof field === 'string') {
      try {
        return JSON.parse(field);
      } catch (e) {
        console.error('Invalid JSON field:', e);
        return [];
      }
    }
    return Array.isArray(field) ? field : [];
  }

  initializeMagnificPopup(): void {
    const $gallery = $('#lightgallery');
    if ($gallery.data('magnificPopup')) {
      $gallery.magnificPopup('destroy');
    }
    $gallery.magnificPopup({
      delegate: 'a.lg-item',
      type: 'image',
      gallery: { enabled: true }
    });
  }

  get hasImages(): boolean {
    return !!this.product && Array.isArray(this.product.images) && this.product.images.length > 0;
  }

  get thumbnailImages(): ProductImage[] {
    return this.product?.images?.filter(img => img.is_thumbnail) || [];
  }

  get additionalImages(): ProductImage[] {
    return this.product?.images?.filter(img => !img.is_thumbnail) || [];
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

  onAddToCart(product: Product): void {
    const quantityToAdd = this.getQuickViewQuantity(product.id);
    this.cartService.addToCart(product, quantityToAdd);
    this.quickViewQuantities[product.id] = 1;
  }

  getDiscountPercentage(mrp: string, special: string): number {
    const mrpNum = parseFloat(mrp);
    const specialNum = parseFloat(special);
    return mrpNum > specialNum ? ((1 - (specialNum / mrpNum)) * 100) : 0;
  }

  getTotalPrice(product: Product): number {
    const quantity = this.getQuickViewQuantity(product.id);
    const price = product.special_price
      ? parseFloat(product.special_price)
      : parseFloat(product.mrp_price);
    return quantity * price;
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    if ($('#lightgallery').data('magnificPopup')) {
      $('#lightgallery').magnificPopup('destroy');
    }
    const elfsightScript = this.elfsightWidgetContainer.nativeElement.querySelector('script[src*="elfsight.com/platform"]');
    if (elfsightScript) elfsightScript.remove();
    const elfsightDiv = this.elfsightWidgetContainer.nativeElement.querySelector('.elfsight-app-856019a4-b468-4aa8-9d7c-ee22b1e0b532');
    if (elfsightDiv) elfsightDiv.remove();
  }

  getSanitizedDescription(): SafeHtml {
    return this.sanitizer.bypassSecurityTrustHtml(this.product?.description || '');
  }
  getSanitizedOffers(html: string | null): SafeHtml {
    if (!html) {
      return '';
    }
    return this.sanitizer.bypassSecurityTrustHtml(html);
  }

}
