import {ChangeDetectionStrategy, Component, inject, OnInit} from '@angular/core';
import {CategoriesService} from './services/categories.service';
import {AsyncPipe} from '@angular/common';


@Component({
  selector: 'app-category-list',
  standalone: true,
  imports: [
    AsyncPipe
  ],
  templateUrl: './category-list.component.html',
  styleUrl: './category-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class CategoryListComponent implements OnInit {
  private _categoryService = inject(CategoriesService);
  categories$ = this._categoryService.categories$;
  selectedCategoryIds = this._categoryService.selectedCategoryIds$;

  ngOnInit(): void {
    this._categoryService.getCategories();
  }

  toggleCategory(categoryId: number) {
    this._categoryService.toggleCategory(categoryId);
  }
}
