import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InternationalOrdersComponent } from './international-orders.component';

describe('InternationalOrdersComponent', () => {
  let component: InternationalOrdersComponent;
  let fixture: ComponentFixture<InternationalOrdersComponent>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [InternationalOrdersComponent]
    });
    fixture = TestBed.createComponent(InternationalOrdersComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
