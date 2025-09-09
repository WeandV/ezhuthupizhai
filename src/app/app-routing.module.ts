import { NgModule } from '@angular/core';
import { RouterModule, Routes, ExtraOptions } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { ShopComponent } from './pages/shop/shop.component';
import { ProductDetailsComponent } from './pages/product-details/product-details.component';
import { CartComponent } from './pages/cart/cart.component';
import { CheckoutComponent } from './pages/checkout/checkout.component';
import { OrderConfirmationComponent } from './pages/order-confirmation/order-confirmation.component';
import { ByobComponent } from './pages/byob/byob.component';
import { ShopInStoreComponent } from './pages/shop-in-store/shop-in-store.component';
import { LoginComponent } from './pages/login/login.component';
import { ForgotPasswordComponent } from './pages/forgot-password/forgot-password.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { InternationalOrdersComponent } from './pages/international-orders/international-orders.component';
import { TermsAndConditionsComponent } from './terms-and-policies/terms-and-conditions/terms-and-conditions.component';
import { PrivacyPolicyComponent } from './terms-and-policies/privacy-policy/privacy-policy.component';
import { RefundPolicyComponent } from './terms-and-policies/refund-policy/refund-policy.component';
import { ShippingAndDeliveryComponent } from './terms-and-policies/shipping-and-delivery/shipping-and-delivery.component';
import { InternationalProductDetailComponent } from './pages/international-product-detail/international-product-detail.component';
import { ContactComponent } from './pages/contact/contact.component';
import { GalleryComponent } from './pages/gallery/gallery.component';

const routes: Routes = [
  { path: '', redirectTo: '/home', pathMatch: 'full' },
  {
    path: 'home',
    loadChildren: () =>
      import('../app/pages/home/home-routing.module').then(m => m.HomeRoutingModule)
  },

  { path: 'shop', component: ShopComponent },
  { path: 'products/:slug', component: ProductDetailsComponent },
  // { path: 'cart', component: CartComponent },
  // { path: 'checkout', component: CheckoutComponent },
  {
    path: 'checkout',
    loadChildren: () => import('../app/pages/checkout/checkout-routing.module').then(m => m.CheckoutRoutingModule)
  },
  {
    path: 'cart',
    loadChildren: () => import('../app/pages/cart/cart-routing.module').then(m => m.CartRoutingModule)
  },
  { path: 'international-orders', component: InternationalOrdersComponent },
  { path: 'international-orders/:slug', component: InternationalProductDetailComponent },
  { path: 'order-confirmation/:orderId', component: OrderConfirmationComponent },
  { path: 'byob', component: ByobComponent },
  { path: 'shop-in-store', component: ShopInStoreComponent },
  { path: 'login', component: LoginComponent },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'forgot-password', component: ForgotPasswordComponent },
  { path: 'terms-and-conditions', component: TermsAndConditionsComponent },
  { path: 'privacy-policy', component: PrivacyPolicyComponent },
  { path: 'shipping-and-delivery', component: ShippingAndDeliveryComponent },
  { path: 'refund-policy', component: RefundPolicyComponent },
  { path: 'contact', component: ContactComponent },
  { path: 'gallery', component: GalleryComponent }
];

const routerOptions: ExtraOptions = {
  scrollPositionRestoration: 'enabled',
  anchorScrolling: 'enabled',
  scrollOffset: [0, 0],
};


@NgModule({
  imports: [RouterModule.forRoot(routes, routerOptions)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
