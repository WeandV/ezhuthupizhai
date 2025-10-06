import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, Observable, of, combineLatest, Subscription } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { delay, map, catchError, tap, take, filter, switchMap } from 'rxjs/operators';
import { Product } from '../models/product.model';
import { CartItem } from '../models/cart-item.model';
import { AppliedCoupon } from '../models/applied-coupon.model';
import { environment } from '../environments/environment';

declare const bootstrap: any;

@Injectable({
    providedIn: 'root',
})

export class CartService implements OnDestroy {
    getDiscountAmount(): string | number {
        throw new Error('Method not implemented.');
    }
    private apiUrl = environment.apiUrl + 'api/get_all_coupons';
    private checkoutApiUrl = environment.apiUrl + 'checkout/';
    private enabledCouriersApiUrl =
        environment.apiUrl + 'checkout/get_enabled_couriers';
    private cartItemsSubject = new BehaviorSubject<CartItem[]>([]);
    public cartItems$: Observable<CartItem[]> =
        this.cartItemsSubject.asObservable();

    private appliedCouponsSubject = new BehaviorSubject<AppliedCoupon[]>([]);
    public appliedCoupons$: Observable<AppliedCoupon[]> =
        this.appliedCouponsSubject.asObservable();

    private _allCouponsFromDatabase: AppliedCoupon[] = [];
    private couponsLoaded = new BehaviorSubject<boolean>(false);

    private _currentCustomerId: BehaviorSubject<number | null> =
        new BehaviorSubject<number | null>(101);
    public currentCustomerId$: Observable<number | null> =
        this._currentCustomerId.asObservable();

    private _manualOverrideCouponCode = new BehaviorSubject<string | null>(null);
    public manualOverrideCouponCode$: Observable<string | null> =
        this._manualOverrideCouponCode.asObservable();

    private _suppressAutoApply = new BehaviorSubject<boolean>(false);
    public suppressAutoApply$: Observable<boolean> =
        this._suppressAutoApply.asObservable();

    private _deliveryChargeSubject = new BehaviorSubject<number>(0);
    public deliveryCharge$ = this._deliveryChargeSubject.asObservable();

    public updateCurrentCustomerId(id: number | null): void {
        if (this._currentCustomerId.value !== id) {
            this._currentCustomerId.next(id);
        }
    }

    private getEffectiveProductPrice(product: Product): number {
        const specialPriceNum = parseFloat(product.special_price as any);
        const mrpPriceNum = parseFloat(product.mrp_price as any);
        if (!isNaN(specialPriceNum) && specialPriceNum > 0) {
            return specialPriceNum;
        } else if (!isNaN(mrpPriceNum)) {
            return mrpPriceNum;
        }
        return 0;
    }

    public readonly itemsTotal$: Observable<number> = this.cartItemsSubject
        .asObservable()
        .pipe(
            map((items) => {
                return items.reduce(
                    (sum, item) => sum + item.effectivePrice * item.quantity,
                    0
                );
            })
        );

    public readonly cartTotal$: Observable<number> = this.itemsTotal$;

    public readonly totalCouponDiscount$: Observable<number> = combineLatest([
        this.appliedCouponsSubject.asObservable(),
        this.couponsLoaded.asObservable(),
        this.cartItemsSubject.asObservable(),
        this.currentCustomerId$,
    ]).pipe(
        map(([appliedCoupons, loaded, cartItems, currentCustomerId]) => {
            if (!loaded || appliedCoupons.length === 0) {
                return 0;
            }

            const applied = appliedCoupons[0];
            const couponDefinition = this._allCouponsFromDatabase.find(
                (c) => c.coupon_code === applied.coupon_code
            );

            if (!couponDefinition) {
                return 0;
            }

            const subtotalForCoupon = this.getCartTotalForCoupon(couponDefinition);

            const isExpired =
                couponDefinition.expiry_date &&
                new Date() > new Date(couponDefinition.expiry_date);
            if (isExpired) {
                return 0;
            }

            const meetsMinOrder =
                subtotalForCoupon >= (couponDefinition.min_order_value || 0);
            if (!meetsMinOrder) {
                return 0;
            }

            let isCustomerAllowed = true;
            if (couponDefinition.visibility === 'specific_customer') {
                const allowedIds: number[] =
                    typeof couponDefinition.allowed_customer_ids === 'string'
                        ? couponDefinition.allowed_customer_ids
                            .split(',')
                            .map((id: string) => parseInt(id.trim(), 10))
                            .filter((id: number) => !isNaN(id))
                        : Array.isArray(couponDefinition.allowed_customer_ids)
                            ? couponDefinition.allowed_customer_ids
                            : [];

                if (
                    currentCustomerId === null ||
                    !allowedIds.includes(currentCustomerId as number)
                ) {
                    isCustomerAllowed = false;
                }
            }
            if (!isCustomerAllowed) {
                return 0;
            }

            let discount = 0;
            if (couponDefinition.discount_type === 'fixed') {
                discount = couponDefinition.discount_value;
            } else if (couponDefinition.discount_type === 'percentage') {
                discount = subtotalForCoupon * (couponDefinition.discount_value / 100);
            }
            return Math.min(discount, subtotalForCoupon);
        }),
        catchError((err) => {
            this.appliedCouponsSubject.next([]);
            this._manualOverrideCouponCode.next(null);
            this._suppressAutoApply.next(false);
            this.saveCouponsToLocalStorage();
            return of(0);
        }),
        tap((calculatedDiscount) => {
            const currentAppliedCoupon =
                this.appliedCouponsSubject.value.length > 0
                    ? this.appliedCouponsSubject.value[0]
                    : null;
            if (calculatedDiscount === 0 && currentAppliedCoupon) {
                const couponDef = this._allCouponsFromDatabase.find(
                    (c) => c.coupon_code === currentAppliedCoupon.coupon_code
                );
                const subtotalForCoupon = this.getCartTotalForCoupon(couponDef);
                const currentCustomerId = this._currentCustomerId.value;
                const isInvalidNow =
                    !couponDef ||
                    (couponDef.expiry_date &&
                        new Date() > new Date(couponDef.expiry_date)) ||
                    subtotalForCoupon < (couponDef.min_order_value || 0) ||
                    (couponDef.visibility === 'specific_customer' &&
                        (currentCustomerId === null ||
                            !(typeof couponDef.allowed_customer_ids === 'string'
                                ? couponDef.allowed_customer_ids
                                    .split(',')
                                    .map((id: string) => parseInt(id.trim(), 10))
                                    .filter((id: number) => !isNaN(id))
                                : Array.isArray(couponDef.allowed_customer_ids)
                                    ? couponDef.allowed_customer_ids.includes(
                                        currentCustomerId as number
                                    )
                                    : false)));
                if (isInvalidNow) {
                    this.appliedCouponsSubject.next([]);
                    this._manualOverrideCouponCode.next(null);
                    this._suppressAutoApply.next(false);
                    this.saveCouponsToLocalStorage();
                }
            }
        })
    );

    public readonly subTotalAfterDiscount$: Observable<number> = combineLatest([
        this.itemsTotal$,
        this.totalCouponDiscount$,
    ]).pipe(map(([itemsTotal, discount]) => Math.max(0, itemsTotal - discount)));

    public readonly finalTotal$: Observable<number> = combineLatest([
        this.subTotalAfterDiscount$,
        this.deliveryCharge$,
    ]).pipe(map(([subTotal, delivery]) => subTotal + delivery));

    private _autoApplySubscription: Subscription | null = null;
    private _initSubscription: Subscription | null = null;

    constructor(private http: HttpClient) {
        this._initSubscription = this.fetchCouponsFromBackend()
            .pipe(
                tap(() => this.couponsLoaded.next(true)),
                tap(() => this.loadCartFromLocalStorage()),
                tap(() => this.loadCouponsFromLocalStorage()),
                take(1)
            )
            .subscribe(
                () => {
                    this.applyBestAvailableCoupon();
                    this.setupAutoApplyListeners();
                },
                (error) => console.error(error)
            );
    }

    ngOnDestroy(): void {
        if (this._autoApplySubscription) {
            this._autoApplySubscription.unsubscribe();
        }
        if (this._initSubscription) {
            this._initSubscription.unsubscribe();
        }
    }

    private setupAutoApplyListeners(): void {
        if (this._autoApplySubscription) {
            this._autoApplySubscription.unsubscribe();
        }
        this._autoApplySubscription = combineLatest([
            this.cartItems$,
            this._currentCustomerId.asObservable(),
            this._manualOverrideCouponCode.asObservable(),
        ])
            .pipe(
                filter(() => this.couponsLoaded.value),
                delay(0),
                tap(() => {
                    this.applyBestAvailableCoupon();
                })
            )
            .subscribe();
    }

    private fetchCouponsFromBackend(): Observable<AppliedCoupon[]> {
        return this.http.get<AppliedCoupon[]>(this.apiUrl).pipe(
            map((coupons) => {
                return coupons.map((coupon) => ({
                    ...coupon,
                    expiry_date: coupon.expiry_date
                        ? new Date(coupon.expiry_date)
                        : undefined,
                    allowed_customer_ids:
                        typeof coupon.allowed_customer_ids === 'string'
                            ? coupon.allowed_customer_ids
                                .split(',')
                                .map((id: string) => parseInt(id.trim(), 10))
                                .filter((id: number) => !isNaN(id))
                            : Array.isArray(coupon.allowed_customer_ids)
                                ? coupon.allowed_customer_ids
                                : null,
                    // Check for null here to differentiate from an empty list
                    allowed_product_ids:
                        coupon.allowed_product_ids === null
                            ? null // Explicitly keep null if it's null from the API
                            : typeof coupon.allowed_product_ids === 'string'
                                ? coupon.allowed_product_ids
                                    .split(',')
                                    .map((id: string) => parseInt(id.trim(), 10))
                                    .filter((id: number) => !isNaN(id))
                                : Array.isArray(coupon.allowed_product_ids)
                                    ? coupon.allowed_product_ids
                                    : null,
                }));
            }),
            tap((processedCoupons) => {
                this._allCouponsFromDatabase = processedCoupons;
            }),
            catchError((error) => {
                this._allCouponsFromDatabase = [];
                return of([]);
            })
        );
    }

    public getVisibleCoupons(): Observable<AppliedCoupon[]> {
        return combineLatest([
            this.couponsLoaded.pipe(filter((loaded) => loaded)),
            this.currentCustomerId$,
            this.cartItems$,
        ]).pipe(
            map(([loaded, currentCustomerId, cartItems]) => {
                return this._allCouponsFromDatabase.filter((coupon) => {
                    // ❌ If no product restriction defined, skip this coupon
                    if (coupon.allowed_product_ids === null) {
                        return false;
                    }

                    const isExpired = coupon.expiry_date && new Date() > new Date(coupon.expiry_date);
                    if (isExpired) return false;

                    const subtotalForCoupon = this.getCartTotalForCoupon(coupon);
                    if (subtotalForCoupon <= 0) return false;
                    if (subtotalForCoupon < (coupon.min_order_value || 0)) return false;

                    if (coupon.visibility === 'public') {
                        return true;
                    }

                    if (coupon.visibility === 'specific_customer') {
                        if (currentCustomerId === null) return false;
                        const allowedIds: number[] = Array.isArray(coupon.allowed_customer_ids)
                            ? coupon.allowed_customer_ids
                            : typeof coupon.allowed_customer_ids === 'string'
                                ? coupon.allowed_customer_ids
                                    .split(',')
                                    .map((id: string) => parseInt(id.trim(), 10))
                                    .filter((id: number) => !isNaN(id))
                                : [];
                        return allowedIds.includes(currentCustomerId as number);
                    }
                    return false;
                });
            })
        );
    }


    // In src/app/services/cart.service.ts
    public isCouponEligible(coupon: AppliedCoupon): Observable<boolean> {
        return combineLatest([
            this.currentCustomerId$,
            this.cartItems$, // <-- Add this to make it reactive to cart changes
        ]).pipe(
            map(([currentCustomerId, cartItems]) => {
                const isExpired =
                    coupon.expiry_date && new Date() > new Date(coupon.expiry_date);
                if (isExpired) return false;

                const subtotalForCoupon = this.getCartTotalForCoupon(coupon);
                if (subtotalForCoupon <= 0) return false;

                const meetsMinOrder =
                    subtotalForCoupon >= (coupon.min_order_value || 0);
                if (!meetsMinOrder) return false;

                if (coupon.visibility === 'specific_customer') {
                    if (currentCustomerId === null) return false;
                    const allowedIds: number[] =
                        typeof coupon.allowed_customer_ids === 'string'
                            ? coupon.allowed_customer_ids
                                .split(',')
                                .map((id: string) => parseInt(id.trim(), 10))
                                .filter((id: number) => !isNaN(id))
                            : Array.isArray(coupon.allowed_customer_ids)
                                ? coupon.allowed_customer_ids
                                : [];
                    if (!allowedIds.includes(currentCustomerId as number)) return false;
                }
                return true;
            })
        );
    }
    private saveCartToLocalStorage(): void {
        localStorage.setItem(
            'shopping_cart',
            JSON.stringify(this.cartItemsSubject.value)
        );
    }

    private loadCartFromLocalStorage(): void {
        const storedCart = localStorage.getItem('shopping_cart');
        if (storedCart) {
            try {
                const cartItems: CartItem[] = JSON.parse(storedCart);
                const validCartItems = cartItems.filter((item) => {
                    const isValid =
                        item.product &&
                        item.product.id !== undefined &&
                        item.quantity !== undefined;
                    if (isValid && item.effectivePrice === undefined) {
                        item.effectivePrice = this.getEffectiveProductPrice(item.product);
                    }
                    return isValid;
                });
                this.cartItemsSubject.next(validCartItems);
            } catch (e) {
                localStorage.removeItem('shopping_cart');
                this.cartItemsSubject.next([]);
            }
        }
    }

    private saveCouponsToLocalStorage(): void {
        const dataToStore = {
            applied: this.appliedCouponsSubject.value.map((coupon) => ({
                ...coupon,
                expiry_date:
                    coupon.expiry_date instanceof Date
                        ? coupon.expiry_date.toISOString()
                        : undefined,
                allowed_customer_ids: Array.isArray(coupon.allowed_customer_ids)
                    ? coupon.allowed_customer_ids
                    : coupon.allowed_customer_ids || null,
                allowed_product_ids: Array.isArray(coupon.allowed_product_ids)
                    ? coupon.allowed_product_ids
                    : coupon.allowed_product_ids || null,
            })),
            manualOverride: this._manualOverrideCouponCode.value,
            suppressAutoApply: this._suppressAutoApply.value,
        };
        localStorage.setItem('applied_coupons_data', JSON.stringify(dataToStore));
    }

    private loadCouponsFromLocalStorage(): void {
        const storedData = localStorage.getItem('applied_coupons_data');
        if (storedData) {
            try {
                const parsedData = JSON.parse(storedData);
                const parsedCoupons: AppliedCoupon[] = parsedData.applied.map(
                    (coupon: any) => ({
                        ...coupon,
                        expiry_date: coupon.expiry_date
                            ? new Date(coupon.expiry_date)
                            : undefined,
                        allowed_customer_ids:
                            typeof coupon.allowed_customer_ids === 'string'
                                ? coupon.allowed_customer_ids
                                    .split(',')
                                    .map((id: string) => parseInt(id.trim(), 10))
                                    .filter((id: number) => !isNaN(id))
                                : Array.isArray(coupon.allowed_customer_ids)
                                    ? coupon.allowed_customer_ids
                                    : null,
                        allowed_product_ids:
                            typeof coupon.allowed_product_ids === 'string'
                                ? coupon.allowed_product_ids
                                    .split(',')
                                    .map((id: string) => parseInt(id.trim(), 10))
                                    .filter((id: number) => !isNaN(id))
                                : Array.isArray(coupon.allowed_product_ids)
                                    ? coupon.allowed_product_ids
                                    : null,
                    })
                );
                this._manualOverrideCouponCode.next(parsedData.manualOverride || null);
                if (parsedData.manualOverride) {
                    this._suppressAutoApply.next(false);
                } else {
                    this._suppressAutoApply.next(false);
                }
                if (parsedCoupons.length > 0) {
                    const storedCouponCode = parsedCoupons[0].coupon_code;
                    const couponDefinition = this._allCouponsFromDatabase.find(
                        (c) => c.coupon_code === storedCouponCode
                    );
                    if (couponDefinition) {
                        this.appliedCouponsSubject.next([couponDefinition]);
                    } else {
                        this.appliedCouponsSubject.next([]);
                        this.saveCouponsToLocalStorage();
                        this._manualOverrideCouponCode.next(null);
                        this._suppressAutoApply.next(false);
                    }
                } else {
                    this.appliedCouponsSubject.next([]);
                }
            } catch (e) {
                this.appliedCouponsSubject.next([]);
                this._manualOverrideCouponCode.next(null);
                this._suppressAutoApply.next(false);
            }
        } else {
            this.appliedCouponsSubject.next([]);
            this._manualOverrideCouponCode.next(null);
            this._suppressAutoApply.next(false);
        }
    }

    addToCart(product: Product, quantity: number = 1): void {
        const currentItems = [...this.cartItemsSubject.value];
        const existingItem = currentItems.find(
            (item) => item.product.id === product.id
        );
        const mrp_price = parseFloat(product.mrp_price) || 0;
        const special_price = parseFloat(product.special_price) || 0;
        const effectivePrice = this.getEffectiveProductPrice(product);
        if (existingItem) {
            existingItem.quantity += quantity;
            existingItem.effectivePrice = effectivePrice;
        } else {
            currentItems.push({ product, quantity, effectivePrice, mrp_price, special_price });
        }
        this.cartItemsSubject.next(currentItems);
        this._suppressAutoApply.next(false);
        this.saveCartToLocalStorage();
        this.openOffcanvasCart();
    }

    removeFromCart(productId: number): void {
        let currentItems = this.cartItemsSubject.value.filter(
            (item) => item.product.id !== productId
        );
        this.cartItemsSubject.next([...currentItems]);
        this._suppressAutoApply.next(false);
        this.saveCartToLocalStorage();
    }

    updateQuantity(productId: number, newQuantity: number): void {
        const currentItems = this.cartItemsSubject.value;
        const itemToUpdate = currentItems.find(
            (item) => item.product.id === productId
        );
        if (itemToUpdate) {
            if (newQuantity > 0) {
                itemToUpdate.quantity = newQuantity;
            } else {
                this.removeFromCart(productId);
                return;
            }
        }
        this.cartItemsSubject.next([...currentItems]);
        this._suppressAutoApply.next(false);
        this.saveCartToLocalStorage();
    }

    public getCartTotalBeforeCoupons(): number {
        return this.cartItemsSubject.value.reduce((sum, item) => {
            return sum + item.effectivePrice * item.quantity;
        }, 0);
    }

    // In src/app/services/cart.service.ts
    private getCartTotalForCoupon(coupon: AppliedCoupon | undefined): number {
        if (!coupon) return 0;

        const allowedProductIds = Array.isArray(coupon.allowed_product_ids)
            ? coupon.allowed_product_ids
            : typeof coupon.allowed_product_ids === 'string'
                ? coupon.allowed_product_ids
                    .split(',')
                    .map((id) => parseInt(id.trim(), 10))
                    .filter((id) => !isNaN(id))
                : null;

        // ❌ If null → coupon not applicable
        if (allowedProductIds === null) {
            return 0;
        }

        // ✅ If empty array → applies to full cart
        if (allowedProductIds.length === 0) {
            return this.getCartTotalBeforeCoupons();
        }

        return this.cartItemsSubject.value
            .filter((item) => allowedProductIds.includes(Number(item.product.id)))
            .reduce((sum, item) => sum + item.effectivePrice * item.quantity, 0);
    }


    public isCouponActive(couponCode: string): boolean {
        const applied = this.appliedCouponsSubject.value;
        return (
            applied.length > 0 &&
            applied[0].coupon_code.toUpperCase() === couponCode.toUpperCase()
        );
    }

    public applyCouponByCode(couponCode: string): {
        success: boolean;
        message: string;
    } {
        const couponToApply = this._allCouponsFromDatabase.find(
            (c) => c.coupon_code.toUpperCase() === couponCode.toUpperCase()
        );
        if (!couponToApply) {
            this.appliedCouponsSubject.next([]);
            this._manualOverrideCouponCode.next(null);
            this._suppressAutoApply.next(false);
            this.saveCouponsToLocalStorage();
            return {
                success: false,
                message: `Coupon code "${couponCode}" is invalid.`,
            };
        }
        const baseTotalForCoupon = this.getCartTotalForCoupon(couponToApply);
        const currentCustomerId = this._currentCustomerId.value;
        if (
            couponToApply.expiry_date &&
            new Date() > new Date(couponToApply.expiry_date)
        ) {
            return {
                success: false,
                message: `Coupon "${couponToApply.coupon_code}" has expired.`,
            };
        }
        if (baseTotalForCoupon < (couponToApply.min_order_value || 0)) {
            return {
                success: false,
                message: `Coupon "${couponToApply.coupon_code
                    }" requires a minimum order of ₹${(
                        couponToApply.min_order_value || 0
                    ).toFixed(
                        2
                    )} of eligible products. Your current eligible total is ₹${baseTotalForCoupon.toFixed(
                        2
                    )}.`,
            };
        }
        if (couponToApply.visibility === 'specific_customer') {
            if (currentCustomerId === null) {
                return {
                    success: false,
                    message: `Coupon "${couponToApply.coupon_code}" requires a logged-in account.`,
                };
            }
            const allowedIds: number[] = Array.isArray(
                couponToApply.allowed_customer_ids
            )
                ? couponToApply.allowed_customer_ids
                : typeof couponToApply.allowed_customer_ids === 'string'
                    ? couponToApply.allowed_customer_ids
                        .split(',')
                        .map((id: string) => parseInt(id.trim(), 10))
                        .filter((id: number) => !isNaN(id))
                    : [];
            if (!allowedIds.includes(currentCustomerId as number)) {
                return {
                    success: false,
                    message: `Coupon "${couponToApply.coupon_code}" is not available for your account.`,
                };
            }
        }
        this.appliedCouponsSubject.next([couponToApply]);
        this._manualOverrideCouponCode.next(couponToApply.coupon_code);
        this._suppressAutoApply.next(false);
        this.saveCouponsToLocalStorage();
        return {
            success: true,
            message: `Coupon "${couponToApply.coupon_code}" applied successfully!`,
        };
    }

    public removeCoupon(couponCode: string): void {
        const currentApplied = this.appliedCouponsSubject.value;
        const isCurrentlyApplied =
            currentApplied.length > 0 &&
            currentApplied[0].coupon_code.toUpperCase() === couponCode.toUpperCase();
        if (isCurrentlyApplied) {
            this.appliedCouponsSubject.next([]);
            this._manualOverrideCouponCode.next(null);
            this._suppressAutoApply.next(true);
            this.saveCouponsToLocalStorage();
        }
    }

    public clearCart(): void {
        this.cartItemsSubject.next([]);
        this.appliedCouponsSubject.next([]);
        this._manualOverrideCouponCode.next(null);
        this._suppressAutoApply.next(false);
        this.saveCartToLocalStorage();
        this.saveCouponsToLocalStorage();
    }

    private openOffcanvasCart(): void {
        const offcanvasElement = document.getElementById('offcanvasRight');
        if (offcanvasElement) {
            const bsOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
            bsOffcanvas.show();
        }
    }

    public applyBestAvailableCoupon(): void {
        if (this._suppressAutoApply.value) {
            return;
        }
        this.couponsLoaded
            .pipe(
                filter((loaded) => loaded),
                switchMap(() =>
                    combineLatest([
                        this.itemsTotal$,
                        this.allCouponsFromDatabase$,
                        this.currentCustomerId$,
                        this._manualOverrideCouponCode.asObservable(),
                    ])
                ),
                take(1)
            )
            .subscribe(
                ([itemsTotal, allCoupons, currentCustomerId, manualOverrideCode]) => {
                    const currentAppliedCoupon =
                        this.appliedCouponsSubject.value.length > 0
                            ? this.appliedCouponsSubject.value[0]
                            : null;
                    if (itemsTotal === 0) {
                        if (currentAppliedCoupon || manualOverrideCode) {
                            this.appliedCouponsSubject.next([]);
                            this._manualOverrideCouponCode.next(null);
                            this._suppressAutoApply.next(false);
                            this.saveCouponsToLocalStorage();
                        }
                        return;
                    }
                    if (manualOverrideCode) {
                        const manuallyAppliedCoupon = allCoupons?.find(
                            (c: AppliedCoupon) =>
                                c.coupon_code?.toUpperCase() ===
                                manualOverrideCode.toUpperCase()
                        );
                        if (manuallyAppliedCoupon) {
                            const subtotalForManualCoupon = this.getCartTotalForCoupon(
                                manuallyAppliedCoupon
                            );
                            const isExpired =
                                manuallyAppliedCoupon.expiry_date &&
                                new Date() > new Date(manuallyAppliedCoupon.expiry_date);
                            const meetsMinOrder =
                                subtotalForManualCoupon >=
                                (manuallyAppliedCoupon.min_order_value || 0);
                            let isCustomerAllowed = true;
                            if (manuallyAppliedCoupon.visibility === 'specific_customer') {
                                const allowedIds: number[] =
                                    typeof manuallyAppliedCoupon.allowed_customer_ids === 'string'
                                        ? manuallyAppliedCoupon.allowed_customer_ids
                                            .split(',')
                                            .map((id: string) => parseInt(id.trim(), 10))
                                            .filter((id: number) => !isNaN(id))
                                        : Array.isArray(manuallyAppliedCoupon.allowed_customer_ids)
                                            ? manuallyAppliedCoupon.allowed_customer_ids
                                            : [];
                                if (
                                    currentCustomerId === null ||
                                    !allowedIds.includes(currentCustomerId as number)
                                ) {
                                    isCustomerAllowed = false;
                                }
                            }
                            if (
                                isExpired ||
                                subtotalForManualCoupon <= 0 ||
                                !meetsMinOrder ||
                                !isCustomerAllowed
                            ) {
                                this.appliedCouponsSubject.next([]);
                                this._manualOverrideCouponCode.next(null);
                                this._suppressAutoApply.next(true);
                                this.saveCouponsToLocalStorage();
                            } else {
                                if (
                                    !currentAppliedCoupon ||
                                    currentAppliedCoupon.coupon_code !==
                                    manuallyAppliedCoupon.coupon_code
                                ) {
                                    this.appliedCouponsSubject.next([manuallyAppliedCoupon]);
                                    this.saveCouponsToLocalStorage();
                                }
                                return;
                            }
                        } else {
                            this.appliedCouponsSubject.next([]);
                            this._manualOverrideCouponCode.next(null);
                            this._suppressAutoApply.next(true);
                            this.saveCouponsToLocalStorage();
                        }
                    }
                    if (this._suppressAutoApply.value) {
                        return;
                    }
                    let bestAutoCoupon: AppliedCoupon | null = null;
                    let maxDiscount = 0;
                    const applicableCoupons =
                        allCoupons?.filter((coupon: AppliedCoupon) => {
                            const isExpired =
                                coupon.expiry_date && new Date() > new Date(coupon.expiry_date);
                            if (isExpired) return false;
                            const subtotalForCoupon = this.getCartTotalForCoupon(coupon);
                            if (subtotalForCoupon <= 0) return false;
                            const meetsMinOrder =
                                subtotalForCoupon >= (coupon.min_order_value || 0);
                            if (!meetsMinOrder) return false;
                            if (coupon.visibility === 'specific_customer') {
                                if (currentCustomerId === null) return false;
                                const allowedIds: number[] = Array.isArray(
                                    coupon.allowed_customer_ids
                                )
                                    ? coupon.allowed_customer_ids
                                    : typeof coupon.allowed_customer_ids === 'string'
                                        ? coupon.allowed_customer_ids
                                            .split(',')
                                            .map((id: string) => parseInt(id.trim(), 10))
                                            .filter((id: number) => !isNaN(id))
                                        : [];
                                if (!allowedIds.includes(currentCustomerId as number))
                                    return false;
                            }
                            return true;
                        }) || [];
                    for (const coupon of applicableCoupons) {
                        let currentCouponDiscount = 0;
                        const subtotalForCoupon = this.getCartTotalForCoupon(coupon);
                        if (coupon.discount_type === 'fixed') {
                            currentCouponDiscount = coupon.discount_value;
                        } else if (coupon.discount_type === 'percentage') {
                            currentCouponDiscount =
                                (subtotalForCoupon * coupon.discount_value) / 100;
                        }
                        currentCouponDiscount = Math.min(
                            currentCouponDiscount,
                            subtotalForCoupon
                        );
                        if (currentCouponDiscount > maxDiscount) {
                            maxDiscount = currentCouponDiscount;
                            bestAutoCoupon = coupon;
                        }
                    }
                    if (bestAutoCoupon) {
                        if (
                            !currentAppliedCoupon ||
                            currentAppliedCoupon.coupon_code !== bestAutoCoupon.coupon_code
                        ) {
                            this.appliedCouponsSubject.next([bestAutoCoupon]);
                            this.saveCouponsToLocalStorage();
                        }
                    } else if (currentAppliedCoupon) {
                        this.appliedCouponsSubject.next([]);
                        this.saveCouponsToLocalStorage();
                    }
                }
            );
    }

    getOrderDetails(orderId: string): Observable<any> {
        const url = `${this.checkoutApiUrl}get_order_details/${orderId}`;
        return this.http.get<any>(url).pipe(
            catchError((error) => {
                return of({
                    success: false,
                    message: 'Failed to load order details.',
                    error: error,
                });
            })
        );
    }

    public getCurrentlyAppliedCoupon(): Observable<AppliedCoupon | null> {
        return this.appliedCoupons$.pipe(
            map((coupons) => (coupons.length > 0 ? coupons[0] : null))
        );
    }

    public get allCouponsFromDatabase$(): Observable<AppliedCoupon[]> {
        return this.couponsLoaded.pipe(
            filter((loaded) => loaded),
            map(() => this._allCouponsFromDatabase)
        );
    }
    public refreshCoupons(): void {
        this.fetchCouponsFromBackend()
            .pipe(take(1))
            .subscribe({
                next: () => {
                    this.couponsLoaded.next(true);
                    this.applyBestAvailableCoupon();
                },
                error: (err) => {
                    console.error('Failed to refresh coupons', err);
                },
            });
    }
    public getEnabledCouriers(data: any): Observable<any> {
        const headers = { 'Content-Type': 'application/json' };
        return this.http
            .post<any>(this.enabledCouriersApiUrl, data, { headers })
            .pipe(
                catchError((error) => {
                    console.error('API Error fetching enabled couriers:', error);
                    return of({
                        status: 'error',
                        message: 'An error occurred while fetching couriers.',
                    });
                })
            );
    }
    public setDeliveryCharge(charge: number): void {
        this._deliveryChargeSubject.next(charge);
    }
}