import { Component, OnInit } from '@angular/core';
import { ByobService } from 'src/app/services/byob.service';
import { Router } from '@angular/router';
import { CartService } from 'src/app/services/cart.service';
import { Product } from 'src/app/models/product.model';

@Component({
  selector: 'app-byob',
  templateUrl: './byob.component.html',
  styleUrls: ['./byob.component.css']
})
export class ByobComponent implements OnInit {
  isLoading: boolean = false;
  selectableBooks: any[] = [];
  selectableItems: any[] = [];

  selectedBooks: any[] = [];
  selectedItems: any[] = [];

  byobBoxId: number | null = null;
  currentByobBox: any;

  boxMessage: string = '';
  showSuccessMessage: boolean = false;

  constructor(
    private byobService: ByobService,
    private router: Router,
    private cartService: CartService
  ) { }

  ngOnInit(): void {
    // Crucial change: Load items first, then manage the box lifecycle
    this.loadAvailableItems();
  }

  loadAvailableItems(): void {
    this.isLoading = true;
    this.byobService.getAvailableItems().subscribe({
      next: (res) => {
        if (res.status && res.data) {
          this.selectableBooks = res.data.filter((item: any) => parseFloat(item.mrp_price) >= 300);
          this.selectableItems = res.data.filter((item: any) => parseFloat(item.mrp_price) < 300);

          // Ensure all selectable items are initially not selected
          this.selectableBooks.forEach(book => book.selected = false);
          this.selectableItems.forEach(item => item.selected = false);

          // After loading available items, then decide whether to create or load a box
          this.createOrLoadByobBox();

        } else {
          this.boxMessage = res.message || 'Failed to load available items.';
          this.showSuccessMessage = false;
          this.isLoading = false; // Ensure loading state is turned off even on error
        }
      },
      error: (err) => {
        this.boxMessage = 'Error loading available items. Please try again later.';
        this.showSuccessMessage = false;
        this.isLoading = false;
      }
    });
  }

  createOrLoadByobBox(): void {
    const savedBoxId = localStorage.getItem('byob_box_id');

    // If there's NO saved ID, or it's marked as 'invalid', create a new box
    if (!savedBoxId || savedBoxId === 'invalid') {
      this.createNewByobBox();
    } else {
      // Attempt to load the existing box
      this.byobBoxId = +savedBoxId;
      this.byobService.getByobBox(this.byobBoxId).subscribe({
        next: (res) => {
          if (res.status && res.data) {
            // Check if the loaded box is "empty" on the backend or completed
            // You might need a property on your `currentByobBox` like `is_completed` or check if `res.data.items.length === 0`
            // If the loaded box is essentially an old, completed box, treat it as invalid
            if (res.data.items && res.data.items.length === 0 && res.data.status === 'completed') { // <-- Adjust 'status' check based on your backend
              console.log('Loaded an empty or completed box, creating new one.');
              localStorage.removeItem('byob_box_id'); // Clear it
              this.createNewByobBox();
            } else {
              this.currentByobBox = res.data;
              // Repopulate selected items only if selectable items are already loaded
              if (this.selectableBooks.length > 0 || this.selectableItems.length > 0) {
                this.repopulateSelectedItems(res.data.items);
              }
              this.boxMessage = 'Loaded your existing custom box.';
              this.showSuccessMessage = true;
            }
          } else {
            // Backend says the box doesn't exist or is invalid for some reason
            localStorage.removeItem('byob_box_id'); // Clear local storage of bad ID
            this.createNewByobBox();
          }
          this.isLoading = false; // Turn off loading after box operation
        },
        error: (err) => {
          // Error fetching box (e.g., 404), assume it's gone.
          localStorage.removeItem('byob_box_id'); // Clear local storage of bad ID
          this.createNewByobBox();
          this.isLoading = false; // Turn off loading after box operation
        }
      });
    }
  }

  createNewByobBox(): void {
    this.isLoading = true; // Set loading state for the creation process
    this.byobService.createByobBox().subscribe({
      next: (res) => {
        if (res.status && res.data) {
          this.currentByobBox = res.data;
          this.byobBoxId = res.data.id;
          localStorage.setItem('byob_box_id', this.byobBoxId!.toString());
          // Clear any potentially selected items from a previous state if this is a fresh new box
          this.selectedBooks = [];
          this.selectedItems = [];
          this.selectableBooks.forEach(book => book.selected = false);
          this.selectableItems.forEach(item => item.selected = false);

          this.boxMessage = 'Your custom gift box is ready to be built!';
          this.showSuccessMessage = true;
        } else {
          this.boxMessage = res.message || 'Failed to create BYOB box.';
          this.showSuccessMessage = false;
        }
        this.isLoading = false;
      },
      error: (err) => {
        this.boxMessage = 'Error creating BYOB box. Please try again.';
        this.showSuccessMessage = false;
        this.isLoading = false;
      }
    });
  }

  repopulateSelectedItems(itemsInBox: any[]): void {
    if (!itemsInBox) return;

    this.selectedBooks = [];
    this.selectedItems = [];

    // Reset all selectable items' 'selected' status first
    this.selectableBooks.forEach(book => book.selected = false);
    this.selectableItems.forEach(item => item.selected = false);

    // Then, mark and add items from the current box
    itemsInBox.forEach(boxItem => {
      const foundBook = this.selectableBooks.find(book => boxItem.product_id === book.id);
      if (foundBook) {
        foundBook.selected = true;
        this.selectedBooks.push(foundBook);
      } else {
        const foundItem = this.selectableItems.find(item => boxItem.product_id === item.id);
        if (foundItem) {
          foundItem.selected = true;
          this.selectedItems.push(foundItem);
        }
      }
    });
  }

  toggleBookSelection(book: any): void {
    if (!this.byobBoxId) {
      this.boxMessage = 'Please wait, initializing your custom box...';
      this.showSuccessMessage = false;
      return;
    }

    const wasSelected = book.selected;
    book.selected = !book.selected;

    this.isLoading = true;
    this.boxMessage = '';

    if (book.selected) {
      this.byobService.addItemToBox(this.byobBoxId, book.id).subscribe({
        next: (res) => {
          if (res.status) {
            this.selectedBooks.push(book);
            this.boxMessage = `${book.name} added to your box.`;
            this.showSuccessMessage = true;
            this.updateCurrentByobBox();
          } else {
            book.selected = wasSelected;
            this.boxMessage = res.message || `Failed to add ${book.name}.`;
            this.showSuccessMessage = false;
          }
          this.isLoading = false;
        },
        error: (err) => {
          book.selected = wasSelected;
          this.boxMessage = `Error adding ${book.name}.`;
          this.showSuccessMessage = false;
          this.isLoading = false;
        }
      });
    } else {
      this.byobService.removeItemFromBox(this.byobBoxId, book.id).subscribe({
        next: (res) => {
          if (res.status) {
            this.selectedBooks = this.selectedBooks.filter(b => b.id !== book.id);
            this.boxMessage = `${book.name} removed from your box.`;
            this.showSuccessMessage = true;
            this.updateCurrentByobBox();
          } else {
            book.selected = wasSelected;
            this.boxMessage = res.message || `Failed to remove ${book.name}.`;
            this.showSuccessMessage = false;
          }
          this.isLoading = false;
        },
        error: (err) => {
          book.selected = wasSelected;
          this.boxMessage = `Error removing ${book.name}.`;
          this.showSuccessMessage = false;
          this.isLoading = false;
        }
      });
    }
  }

  toggleItemSelection(item: any): void {
    if (!this.byobBoxId) {
      this.boxMessage = 'Please wait, initializing your custom box...';
      this.showSuccessMessage = false;
      return;
    }

    const wasSelected = item.selected;
    item.selected = !item.selected;
    this.isLoading = true;
    this.boxMessage = '';

    if (item.selected) {
      this.byobService.addItemToBox(this.byobBoxId, item.id).subscribe({
        next: (res) => {
          if (res.status) {
            this.selectedItems.push(item);
            this.boxMessage = `${item.name} added to your box.`;
            this.showSuccessMessage = true;
            this.updateCurrentByobBox();
          } else {
            item.selected = wasSelected;
            this.boxMessage = res.message || `Failed to add ${item.name}.`;
            this.showSuccessMessage = false;
          }
          this.isLoading = false;
        },
        error: (err) => {
          item.selected = wasSelected;
          this.boxMessage = `Error adding ${item.name}.`;
          this.showSuccessMessage = false;
          this.isLoading = false;
        }
      });
    } else {
      this.byobService.removeItemFromBox(this.byobBoxId, item.id).subscribe({
        next: (res) => {
          if (res.status) {
            this.selectedItems = this.selectedItems.filter(i => i.id !== item.id);
            this.boxMessage = `${item.name} removed from your box.`;
            this.showSuccessMessage = true;
            this.updateCurrentByobBox();
          } else {
            item.selected = wasSelected;
            this.boxMessage = res.message || `Failed to remove ${item.name}.`;
            this.showSuccessMessage = false;
          }
          this.isLoading = false;
        },
        error: (err) => {
          item.selected = wasSelected;
          this.boxMessage = `Error removing ${item.name}.`;
          this.showSuccessMessage = false;
          this.isLoading = false;
        }
      });
    }
  }

  removeSelectedItem(itemToRemove: any, type: 'book' | 'item'): void {
    if (!this.byobBoxId) return;

    this.isLoading = true;
    this.boxMessage = '';

    this.byobService.removeItemFromBox(this.byobBoxId, itemToRemove.id).subscribe({
      next: (res) => {
        if (res.status) {
          if (type === 'book') {
            this.selectedBooks = this.selectedBooks.filter(b => b.id !== itemToRemove.id);
            const bookInSelectable = this.selectableBooks.find(b => b.id === itemToRemove.id);
            if (bookInSelectable) bookInSelectable.selected = false;
          } else {
            this.selectedItems = this.selectedItems.filter(i => i.id !== itemToRemove.id);
            const itemInSelectable = this.selectableItems.find(i => i.id === itemToRemove.id);
            if (itemInSelectable) itemInSelectable.selected = false;
          }
          this.boxMessage = `${itemToRemove.name} removed from your box.`;
          this.showSuccessMessage = true;
          this.updateCurrentByobBox();
        } else {
          this.boxMessage = res.message || `Failed to remove ${itemToRemove.name}.`;
          this.showSuccessMessage = false;
        }
        this.isLoading = false;
      },
      error: (err) => {
        this.boxMessage = `Error removing ${itemToRemove.name}.`;
        this.showSuccessMessage = false;
        this.isLoading = false;
      }
    });
  }

  get totalPrice(): number {
    let total = 0;
    this.selectedBooks.forEach(book => total += parseFloat(book.special_price || book.mrp_price || '0'));
    this.selectedItems.forEach(item => total += parseFloat(item.special_price || item.mrp_price || '0'));
    return total;
  }

  updateCurrentByobBox(): void {
    if (this.byobBoxId) {
      this.byobService.getByobBox(this.byobBoxId).subscribe({
        next: (res) => {
          if (res.status && res.data) {
            this.currentByobBox = res.data;
          }
        },
        error: (err) => {
          console.error('Error updating current BYOB box details:', err);
          // If the box could not be updated, perhaps it was removed/invalidated by backend.
          // Consider clearing localStorage here too if this error implies the box is truly gone.
          // localStorage.removeItem('byob_box_id');
          // this.createNewByobBox();
        }
      });
    }
  }

  addToCart(): void {
    if ((this.selectedBooks.length === 0 && this.selectedItems.length === 0) || !this.byobBoxId) {
      this.boxMessage = 'Please select items to build your box before adding to cart.';
      this.showSuccessMessage = false;
      return;
    }

    this.isLoading = true;
    this.boxMessage = '';

    const byobBoxName = 'BYOB Box';

    const selectedItemNames: string[] = [];
    this.selectedBooks.forEach(book => {
      selectedItemNames.push(book.name);
    });
    this.selectedItems.forEach(item => {
      selectedItemNames.push(item.name);
    });

    let byobBoxDescription = '';
    let itemCounter = 1;

    if (this.selectedBooks.length > 0 || this.selectedItems.length > 0) {
      byobBoxDescription += '\nUnder Selected Items:\n';
      this.selectedBooks.forEach(book => {
        byobBoxDescription += `${itemCounter}. ${book.name}\n`;
        itemCounter++;
      });
      this.selectedItems.forEach(item => {
        byobBoxDescription += `${itemCounter}. ${item.name}\n`;
        itemCounter++;
      });
    }

    const boxMrpPrice = this.totalPrice.toFixed(2);
    const boxSpecialPrice = this.totalPrice.toFixed(2);

    const byobProduct: Product = {
      id: Date.now(), // Use a unique number as id
      name: byobBoxName,
      tamil_name: byobBoxName,
      short_description: ['A custom -your-own-box.'],
      description: byobBoxDescription,
      mrp_price: boxMrpPrice,
      special_price: boxSpecialPrice,
      offers: boxSpecialPrice,
      thumbnail_image: 'https://placehold.co/500?text=byob',
      categories: ['BYOB'],
      tag: 'custom_box',
      is_international: 0, 
      images: [],
      options: {
        is_byob_box: true,
        byob_box_id: this.byobBoxId,
        selected_item_ids: [...this.selectedBooks.map(b => b.id), ...this.selectedItems.map(i => i.id)],
        selected_item_names: selectedItemNames
      },
      sku: ''
    };

    this.cartService.addToCart(byobProduct, 1);

    this.boxMessage = 'Your custom BYOB Box has been added to the cart successfully!';
    this.showSuccessMessage = true;

    // --- MOST IMPORTANT CHANGE HERE ---
    // Invalidate the localStorage ID immediately and clear component state
    localStorage.setItem('byob_box_id', 'invalid'); // Mark as invalid instead of just removing
    this.byobBoxId = null;
    this.currentByobBox = null;
    this.selectedBooks = [];
    this.selectedItems = [];
    this.selectableBooks.forEach(b => b.selected = false);
    this.selectableItems.forEach(i => i.selected = false);

    // After clearing, immediately create a new box for the user to start fresh
    // This ensures a new box is ready for interaction without a refresh.
    // On subsequent refresh, ngOnInit will see 'invalid' and create a new one.
    this.createNewByobBox();

    this.isLoading = false;
  }
}