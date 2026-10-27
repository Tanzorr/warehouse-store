import {ChangeDetectionStrategy, Component, EventEmitter, Input, Output, inject} from '@angular/core';
import {FormBuilder, ReactiveFormsModule} from '@angular/forms';
import {debounceTime, distinctUntilChanged} from 'rxjs';
import {NgClass} from '@angular/common';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';

@Component({
  selector: 'app-search',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    ReactiveFormsModule,
    NgClass
  ],
  templateUrl: './search.component.html',
  styleUrl: './search.component.scss'
})
export class SearchComponent {
  private readonly fb = inject(FormBuilder);

  searchForm = this.fb.group({ query: [''] });

  @Input() customClass: string = '';
  @Output() search = new EventEmitter<string>();

  constructor() {
    this.searchForm.get('query')?.valueChanges.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      takeUntilDestroyed()
    ).subscribe((query: string | null) => {
      this.search.emit((query ?? '').trim());
    });
  }

  clearSearch(): void {
    this.searchForm.reset();
    this.search.emit('');
  }
}
