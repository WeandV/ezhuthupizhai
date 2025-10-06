import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InternationalProductDetailComponent } from './international-product-detail.component';

describe('InternationalProductDetailComponent', () => {
  let component: InternationalProductDetailComponent;
  let fixture: ComponentFixture<InternationalProductDetailComponent>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [InternationalProductDetailComponent]
    });
    fixture = TestBed.createComponent(InternationalProductDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
