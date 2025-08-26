import { Component, OnInit, AfterViewInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import Swiper from 'swiper';
import { Navigation, Pagination, Parallax } from 'swiper/modules';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/app/environments/environment';

interface ProductVideo {
  id: number;
  product_id: number;
  video_url: string;
}
interface Vendor {
  id: number;
  name: string;
  logo: string;
  address_line1: string;
  address_line2: string;
  city: string;
  state: string;
  pincode: string;
  phone: string;
  email: string;
}

interface Gallery {
  id: number;
  gallery_image: string;
}

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild('productSwiperContainer') productSwiperContainer: ElementRef | undefined;
  private productSwiper: any;

  videos: ProductVideo[] = [];
  private videosApiUrl = environment.apiUrl + 'api/videos';

  vendors: Vendor[] = [];
  private vendorApiUrl = environment.apiUrl + 'api/vendor';

  gallery: Gallery[] = [];
  private galleryApiUrl = environment.apiUrl + 'api/get_all_images';

  constructor(private http: HttpClient) { }

  ngOnInit(): void {
    this.fetchVideos();
    this.fetchVendor();
    this.fetchGallery();
  }

  ngAfterViewInit(): void {
    if (this.productSwiperContainer) {
      console.log('ViewChild Swiper element found:', this.productSwiperContainer.nativeElement);
      this.initProductSwiper();
    } else {
      console.error('Swiper container not found via ViewChild. This should not happen if element is always present.');
    }
  }

  initProductSwiper(): void {
    Swiper.use([Navigation, Pagination, Parallax]); // Register modules you need

    if (this.productSwiperContainer) { // No need for typeof Swiper !== 'undefined' anymore
      console.log('Attempting to initialize Swiper with dz.carousel.js options (NPM module method)...');

      this.productSwiper = new Swiper(this.productSwiperContainer.nativeElement, {
        speed: 1000,
        loop: true,
        parallax: true,
        slidesPerView: 3,
        spaceBetween: 15,
        pagination: {
          el: ".swiper-pagination-trading",
          clickable: true,
        },
        // If using navigation, uncomment and adjust:
        // navigation: {
        //   nextEl: '.swiper-button-next',
        //   prevEl: '.swiper-button-prev',
        // },
        breakpoints: {
          340: {
            slidesPerView: 1,
            centeredSlides: true,
            spaceBetween: 15,
          },
          575: {
            slidesPerView: 1,
            centeredSlides: false,
            spaceBetween: 15,
          },
          600: {
            slidesPerView: 1,
            spaceBetween: 15,
          },
          767: {
            slidesPerView: 1.5,
            spaceBetween: 15,
          },
          991: {
            slidesPerView: 2,
            spaceBetween: 15,
          },
          1024: {
            slidesPerView: 2,
            spaceBetween: 15,
          },
          1400: {
            slidesPerView: 1,
            spaceBetween: 15,
          },
        }
      });
      console.log('Swiper initialized (NPM module):', this.productSwiper);
      if (this.productSwiper && typeof this.productSwiper.update === 'function') {
        console.log('Swiper instance looks valid and working.');
      }
    } else {
      console.error('Swiper container not found. This indicates an issue with HTML rendering or ViewChild.');
    }
  }

  ngOnDestroy(): void {
    if (this.productSwiper) {
      this.productSwiper.destroy();
      console.log('Swiper destroyed.');
    }
  }

  fetchVideos(): void {
    this.http.get<ProductVideo[]>(this.videosApiUrl).subscribe({
      next: (response: any) => {
        // Assuming the CodeIgniter API returns an object with a 'data' key
        if (response && response.status === 'success' && response.data) {
          this.videos = response.data;
        } else {
          console.error('Error: API response format is incorrect.', response);
        }
      },
      error: (err) => {
        console.error('Error fetching videos:', err);
      }
    });
  }

  fetchVendor(): void {
    this.http.get<Vendor[]>(this.vendorApiUrl).subscribe({
      next: (response: any) => {
        if (response && response.status === 'success' && response.data) {
          // This line has been corrected
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

  fetchGallery(): void {
    this.http.get<Gallery[]>(this.galleryApiUrl).subscribe({
      next: (response: any) => {
        if (response && response.status === 'success' && response.data) {
          // CORRECTED: Assigning data to the `gallery` array
          this.gallery = response.data;
        } else {
          console.error('Error: API response format is incorrect.', response);
        }
      },
      error: (err) => {
        console.error('Error fetching gallery images:', err);
      }
    });
  }
}
