import { NgModule } from '@angular/core';
import { BrowserModule, provideClientHydration } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { HeaderComponent } from './header/header.component';
import { FooterComponent } from './footer/footer.component';

import { HomeComponent } from './pages/home/home.component';
import { HomeRoutingModule } from './pages/home/home-routing.module';

import { ProductsComponent } from './widgets/products/products.component';
import { ShopComponent } from './pages/shop/shop.component';
import { ProductDetailsComponent } from './pages/product-details/product-details.component';
import { CartComponent } from './pages/cart/cart.component';
import { CheckoutComponent } from './pages/checkout/checkout.component';

import { OrderConfirmationComponent } from './pages/order-confirmation/order-confirmation.component';
import { ByobComponent } from './pages/byob/byob.component';
import { ShopInStoreComponent } from './pages/shop-in-store/shop-in-store.component';
import { LoginComponent } from './pages/login/login.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { ForgotPasswordComponent } from './pages/forgot-password/forgot-password.component';
import { InternationalOrdersComponent } from './pages/international-orders/international-orders.component';
import { TermsAndConditionsComponent } from './terms-and-policies/terms-and-conditions/terms-and-conditions.component';
import { PrivacyPolicyComponent } from './terms-and-policies/privacy-policy/privacy-policy.component';
import { ShippingAndDeliveryComponent } from './terms-and-policies/shipping-and-delivery/shipping-and-delivery.component';
import { RefundPolicyComponent } from './terms-and-policies/refund-policy/refund-policy.component';
import { NgxMasonryModule } from 'ngx-masonry';
import { InternationalProductDetailComponent } from './pages/international-product-detail/international-product-detail.component';
import { ContactComponent } from './pages/contact/contact.component';
import { GalleryComponent } from './pages/gallery/gallery.component';

@NgModule({
  declarations: [
    AppComponent,
    HeaderComponent,
    FooterComponent,
    HomeComponent,
    ProductsComponent,
    ShopComponent,
    ProductDetailsComponent,
    CartComponent,
    CheckoutComponent,
    OrderConfirmationComponent,
    ByobComponent,
    ShopInStoreComponent,
    LoginComponent,
    DashboardComponent,
    ForgotPasswordComponent,
    InternationalOrdersComponent,
    TermsAndConditionsComponent,
    PrivacyPolicyComponent,
    ShippingAndDeliveryComponent,
    RefundPolicyComponent,
    InternationalProductDetailComponent,
    ContactComponent,
    GalleryComponent,
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    FormsModule,
    ReactiveFormsModule,
    NgxMasonryModule,
    HomeRoutingModule,
    
  ],
  providers: [
    provideClientHydration()
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
