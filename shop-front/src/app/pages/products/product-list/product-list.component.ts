import {Component,  inject, OnInit} from '@angular/core';
import {ProductsService} from './services/products.service';
import {Observable} from 'rxjs';
import {ProductsResponse} from '../../../models/products';
import {AsyncPipe} from '@angular/common';
import {ProductComponent} from '../../../components/product/product.component';
import {PaginationComponent} from '../../../components/pagination/pagination.component';
import {SearchComponent} from '../../../components/search/search.component';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [
    AsyncPipe,
    ProductComponent,
    PaginationComponent,
    SearchComponent
  ],
  templateUrl: './product-list.component.html',
  styleUrl: './product-list.component.scss'
})
export class ProductListComponent implements OnInit {
  private _productsService = inject(ProductsService);
  productsResponse$: Observable<ProductsResponse | null> = this._productsService.productsResponse$;

  ngOnInit(): void {
    this._productsService.getProducts({});
  }

  changePage(url: string | null): void {

    this._productsService.changePage(url);
  }

  getSearchValue($event: string | null):void {
    this._productsService.searchValue($event)
  }
}
