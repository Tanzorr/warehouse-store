import {ChangeDetectionStrategy, Component} from '@angular/core';

@Component({
  selector: 'app-two-column',
  standalone: true,
  imports: [],
  templateUrl: './two-column-component.html',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class TwoColumnComponents {

}
