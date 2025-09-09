import { Component, OnInit } from '@angular/core';
import { GalleryService } from 'src/app/services/gallery.service';
import { GalleryImage } from 'src/app/models/gallery-image.model';

@Component({
  selector: 'app-gallery',
  templateUrl: './gallery.component.html',
  styleUrls: ['./gallery.component.css']
})
export class GalleryComponent implements OnInit {

  productFilters: string[] = [];
  filteredImages: GalleryImage[] = [];
  activeFilter: string = '';
  loading: boolean = false;

  constructor(private galleryService: GalleryService) { }

  ngOnInit(): void {
    this.fetchFilters();
  }

  fetchFilters(): void {
    this.loading = true;
    this.galleryService.getGalleryFilters().subscribe({
      next: (data: any) => {
        this.productFilters = data.data;
        if (this.productFilters.length > 0) {
          this.activeFilter = this.productFilters[0];
          this.filterImages(this.activeFilter);
        }
      },
      error: (err) => {
        console.error('Error fetching filters:', err);
        this.loading = false;
      }
    });
  }

  filterImages(product: string): void {
    this.loading = true;
    this.activeFilter = product;

    this.galleryService.getGalleryImages(product).subscribe({
      next: (data: any) => {
        this.filteredImages = data.data;
        this.loading = false;
      },
      error: (err) => {
        this.filteredImages = [];
        this.loading = false;
      }
    });
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
