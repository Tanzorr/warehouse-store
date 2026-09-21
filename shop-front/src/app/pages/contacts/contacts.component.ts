import { Component } from '@angular/core';
import {OneColumnComponent} from '../../layouts/one-column/one-column.component';

@Component({
  selector: 'app-contacts',
  imports: [
    OneColumnComponent
  ],
  templateUrl: './contacts.component.html',
  styleUrl: './contacts.component.scss'
})
export class ContactsComponent {

}
