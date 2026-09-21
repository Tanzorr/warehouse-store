import { ChangeDetectionStrategy, Component, inject, Input, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  ValidatorFn,
  Validators,
} from '@angular/forms';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { createAdminProduct, getAdminProduct, updateAdminProduct } from '../../store/admin-products-actions';
import { adminSelectedProductSelector } from '../../store/admin-products-selectors';
import { getAdminCategories } from '../../../categories/store/admin-categories-actions';
import { adminCategoriesResponseSelector } from '../../../categories/store/admin-categories-selectors';

export const salePriceAboveCostValidator: ValidatorFn = (group: AbstractControl): ValidationErrors | null => {
  const purchasePrice = group.get('purchase_price')?.value;
  const salePrice = group.get('sale_price')?.value;

  if (purchasePrice == null || salePrice == null) {
    return null;
  }

  return Number(salePrice) > Number(purchasePrice) ? null : { salePriceTooLow: true };
};

@Component({
  selector: 'app-admin-product-form',
  standalone: true,
  imports: [AsyncPipe, RouterLink, ReactiveFormsModule],
  templateUrl: './product-form.component.html',
  styleUrl: './product-form.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductFormComponent implements OnInit {
  @Input() id?: string;

  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);
  private router = inject(Router);

  categoriesResponse$ = this.store.select(adminCategoriesResponseSelector);

  get isEditMode(): boolean {
    return this.id !== undefined && this.id !== 'new';
  }

  form = this.fb.group(
    {
      name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(255)]],
      description: [''],
      category_id: [null as number | null, Validators.required],
      purchase_price: [0, [Validators.required, Validators.min(0)]],
      sale_price: [0, [Validators.required, Validators.min(0.01)]],
      stock: [0, [Validators.required, Validators.min(0)]],
      sku: ['', [Validators.required, Validators.maxLength(50)]],
    },
    { validators: salePriceAboveCostValidator }
  );

  ngOnInit(): void {
    this.store.dispatch(getAdminCategories());

    if (this.isEditMode) {
      this.store.dispatch(getAdminProduct(Number(this.id)));

      this.store.select(adminSelectedProductSelector).subscribe((product) => {
        if (product) {
          this.form.patchValue({
            name: product.name,
            description: product.description ?? '',
            category_id: product.category_id,
            purchase_price: product.purchase_price,
            sale_price: product.sale_price,
            stock: product.stock,
            sku: product.sku,
          });
        }
      });
    }
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const value = this.form.getRawValue();
    const payload = {
      name: value.name!,
      description: value.description,
      category_id: value.category_id!,
      purchase_price: Number(value.purchase_price),
      sale_price: Number(value.sale_price),
      stock: Number(value.stock),
      sku: value.sku!,
    };

    if (this.isEditMode) {
      this.store.dispatch(updateAdminProduct(Number(this.id), payload));
    } else {
      this.store.dispatch(createAdminProduct(payload));
    }

    this.router.navigate(['/admin/products']);
  }
}
