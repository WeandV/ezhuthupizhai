// src/app/services/order.service.ts
import { Injectable } from '@angular/core';
import { Order } from '../models/order.model';

@Injectable({
  providedIn: 'root',
})
export class OrderService {
  private lastOrder: Order | null = null;

  setOrder(order: Order): void {
    this.lastOrder = order;
  }

  getOrder(): Order | null {
    return this.lastOrder;
  }

  clearOrder(): void {
    this.lastOrder = null;
  }
}
