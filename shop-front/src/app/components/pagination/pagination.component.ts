import {Component, EventEmitter, Input, Output} from '@angular/core';
import {PaginationLink} from '../../models/media';
import {NgClass} from '@angular/common';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [
    NgClass
  ],
  templateUrl: './pagination.component.html'
})
export class PaginationComponent {
  @Input() paginationLinks: PaginationLink[] = [];
  @Output() pageChange = new EventEmitter<string | null>();

  changePage(url: string | null): void {
    this.pageChange.emit(url);
  }
}
