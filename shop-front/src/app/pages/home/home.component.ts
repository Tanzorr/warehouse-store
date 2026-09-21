import { Component } from '@angular/core';
import {OneColumnComponent} from '../../layouts/one-column/one-column.component';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [
    OneColumnComponent
  ],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent {

}
