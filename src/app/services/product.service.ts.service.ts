import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpErrorResponse } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { Product } from '../models/product.model';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ProductServiceTsService {

  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  getProducts(): Observable<Product[]> {
    return this.http.get<any>(`${this.apiUrl}api/products`).pipe(
      map(response => {
        if (response.status === 'success' && response.data) {
          return response.data.map((product: any) => ({
            ...product,
            mrp_price: product.mrp_price,
            special_price: product.special_price,
            videos: product.videos || []
          })) as Product[];
        } else {
          return [];
        }
      })
    );
  }

  getInternationalProducts(): Observable<Product[]> {
    return this.http.get<any>(`${this.apiUrl}api/international_products`).pipe(
      map(response => {
        if (response.status === 'success' && response.data) {
          return response.data;
        } else {
          return [];
        }
      }),
      catchError((error: HttpErrorResponse) => {
        console.error('Error fetching international products:', error);
        return of([]);
      })
    );
  }

  getProductDetail(productId: number): Observable<Product | null> {
    return this.http.get<any>(`${this.apiUrl}api/product_detail/${productId}`).pipe(
      map(response => {
        if (response.status === 'success' && response.data) {
          return {
            ...response.data,
            videos: response.data.videos || []   // ✅ Map videos
          } as Product;
        } else {
          return null;
        }
      })
    );
  }

    getVideosByProductId(productId: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/api/products/${productId}/videos`);
  }

  submitInternationalOrder(orderData: any): Observable<any> {
    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
    });
    return this.http.post(`${this.apiUrl}api/international_order`, orderData, { headers });
  }

  getCategories(): Observable<string[]> {
    return this.http.get<any>(`${this.apiUrl}api/categories`).pipe(
      map(response => {
        if (response.status === 'success' && response.data) {
          return response.data;
        } else {
          return [];
        }
      })
    );
  }

  getProductBySlug(slug: string): Observable<Product | null> {
    return this.http.get<Product>(`${this.apiUrl}api/slug/${slug}`);
  }

}