import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';
import { tap } from 'rxjs/operators'; // Import tap operator

export interface GalleryImage {
  id: number;
  gallery_image: string;
  product: string;
}

export interface GalleryProduct {
  product: string;
}

@Injectable({
  providedIn: 'root'
})
export class GalleryService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  getGalleryFilters(): Observable<any> {
    return this.http.get(`${this.apiUrl}api/get_filters`);
  }

  getGalleryImages(productName: string): Observable<any> {
    let params = new HttpParams();
    if (productName && productName !== 'All') {
      params = params.set('product', productName);
    }
    return this.http.get(`${this.apiUrl}api/get_images`, { params: params });
  }

}
