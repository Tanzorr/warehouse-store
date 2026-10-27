import { Component } from '@angular/core';
import {TwoColumnComponents} from '../../layouts/two-colum/two-column-component';


@Component({
  selector: 'app-footer',
  standalone: true,
  imports: [
    TwoColumnComponents,
  ],
  templateUrl: './footer.component.html',
  styleUrls: ['./footer.component.scss'],
})
export class FooterComponent {

}
