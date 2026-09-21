import {ChangeDetectionStrategy, Component, HostBinding, Input} from '@angular/core';
import {Product} from '../../models/products';
import {RouterLink} from '@angular/router';

@Component({
  selector: 'app-product',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterLink
  ],
  templateUrl: './product.component.html',
  styleUrl: './product.component.scss'
})
export class ProductComponent {
  @Input() product!:Product;
  @HostBinding() class ='col-lg-3 col-md-4 col-sm-6 col-xs-12 product';
}
