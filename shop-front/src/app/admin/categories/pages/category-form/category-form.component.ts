import { ChangeDetectionStrategy, Component, inject, Input, OnInit } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { createAdminCategory, getAdminCategory, updateAdminCategory } from '../../store/admin-categories-actions';
import { adminSelectedCategorySelector } from '../../store/admin-categories-selectors';

@Component({
  selector: 'app-admin-category-form',
  standalone: true,
  imports: [RouterLink, ReactiveFormsModule],
  templateUrl: './category-form.component.html',
  styleUrl: './category-form.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CategoryFormComponent implements OnInit {
  @Input() id?: string;

  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);
  private router = inject(Router);

  get isEditMode(): boolean {
    return this.id !== undefined && this.id !== 'new';
  }

  form = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(255)]],
    description: [''],
  });

  ngOnInit(): void {
    if (this.isEditMode) {
      this.store.dispatch(getAdminCategory(Number(this.id)));

      this.store.select(adminSelectedCategorySelector).subscribe((category) => {
        if (category) {
          this.form.patchValue({
            name: category.name,
            description: category.description ?? '',
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
    };

    if (this.isEditMode) {
      this.store.dispatch(updateAdminCategory(Number(this.id), payload));
    } else {
      this.store.dispatch(createAdminCategory(payload));
    }

    this.router.navigate(['/admin/categories']);
  }
}
