import {ChangeDetectionStrategy, Component, Input, inject, OnInit} from '@angular/core';
import {ProductService} from './services/product.service';
import {AsyncPipe} from '@angular/common';


@Component({
  selector: 'app-product',
  standalone: true,
  imports: [
    AsyncPipe
  ],
  templateUrl: './product.component.html',
  styleUrl: './product.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class ProductComponent implements OnInit{
  private _productService = inject(ProductService);
  product$ = this._productService.product$;

  @Input() id!: string;

  ngOnInit(): void {
    this._productService.getProduct(Number(this.id));
  }
}
