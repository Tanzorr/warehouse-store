import {Component} from '@angular/core';
import {RouterOutlet} from '@angular/router';
import {FooterComponent} from './presentational/footer/footer.component';

import {NavigationComponents} from './presentational/navigation.components/navigation.components';
import {StoreRouterConnectingModule} from '@ngrx/router-store';
import {CustomSerializer} from './store/route/router-sate-serializer';


@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    RouterOutlet,
    FooterComponent,
    NavigationComponents
  ],
  templateUrl: './app.component.html'
})
export class AppComponent {
  protected title = 'shop-front';
}
