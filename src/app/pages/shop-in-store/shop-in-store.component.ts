import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/app/environments/environment';

interface Vendor {
  id: number;
  logo: string;
  name: string;
  address_line1: string | null;
  address_line2: string | null;
  city: string | null;
  state: string | null;
  pincode: string | null;
  phone: string;
  email: string;
  flag: string;
  country: string;
}

@Component({
  selector: 'app-shop-in-store',
  templateUrl: './shop-in-store.component.html',
  styleUrls: ['./shop-in-store.component.css']
})
export class ShopInStoreComponent implements OnInit {
  email: string = 'mano@ezhuthupizhai.in';
  
  vendors: Vendor[] = [];
  private vendorApiUrl = environment.apiUrl + 'api/vendor';

  constructor(private http: HttpClient) { }

  ngOnInit(): void {
    this.fetchVendor();
  }

  fetchVendor(): void {
    this.http.get<Vendor[]>(this.vendorApiUrl).subscribe({
      next: (response: any) => {
        if (response && response.status === 'success' && response.data) {
          this.vendors = response.data;
        } else {
          console.error('Error: API response format is incorrect.', response);
        }
      },
      error: (err) => {
        console.error('Error fetching vendors:', err);
      }
    });
  }

  // Helper method to format the address
  getVendorAddress(vendor: Vendor): string {
    const addressParts: string[] = [];

    // Check each address part and push it to the array if it's not null or empty
    if (vendor.address_line1) {
      addressParts.push(vendor.address_line1);
    }
    if (vendor.address_line2) {
      addressParts.push(vendor.address_line2);
    }
    if (vendor.city) {
      addressParts.push(vendor.city);
    }
    if (vendor.state) {
      addressParts.push(vendor.state);
    }
    if (vendor.country) {
      addressParts.push(vendor.country);
    }
    if (vendor.pincode) {
      addressParts.push(vendor.pincode);
    }

    // If the array is empty, it means all address parts were null
    if (addressParts.length === 0) {
      return 'Not Available';
    } else {
      // Join the valid parts with a comma and space
      return addressParts.join(', ');
    }
  }
}