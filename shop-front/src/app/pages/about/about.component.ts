import { Component } from '@angular/core';
import {OneColumnComponent} from '../../layouts/one-column/one-column.component';

@Component({
  selector: 'app-about',
  imports: [
    OneColumnComponent
  ],
  templateUrl: './about.component.html',
  styleUrl: './about.component.scss'
})
export class AboutComponent {

}
