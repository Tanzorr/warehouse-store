import {ChangeDetectionStrategy, Component} from '@angular/core';
import {RouterLink, RouterLinkActive} from '@angular/router';
import {OneColumnComponent} from '../../layouts/one-column/one-column.component';

interface NavLink {
  label: string;
  url: string;
  authRequired: boolean;
  isAdmin?: boolean;
}

@Component({
  selector: 'app-navigation',
  imports: [
    RouterLink,
    RouterLinkActive
  ],
  templateUrl: './navigation.components.html',
  styleUrl: './navigation.components.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class NavigationComponents {

  navLinks: NavLink[] = [
    { label: 'Products', url: '/products', authRequired: true },
    { label: 'About Us', url: '/about', authRequired: false },
    { label: 'Contacts', url: '/components', authRequired: false },
    { label: 'Log In', url: '/login', authRequired: false }
  ];

  mobileMenuToggle() {

  }
}
