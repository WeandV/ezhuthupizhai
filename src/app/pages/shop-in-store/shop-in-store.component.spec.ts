import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShopInStoreComponent } from './shop-in-store.component';

describe('ShopInStoreComponent', () => {
  let component: ShopInStoreComponent;
  let fixture: ComponentFixture<ShopInStoreComponent>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [ShopInStoreComponent]
    });
    fixture = TestBed.createComponent(ShopInStoreComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
