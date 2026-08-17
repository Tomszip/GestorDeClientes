import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ConfiguracionService } from '../../services/configuracion.service';

@Component({
  selector: 'app-sidebar',
  standalone: false,
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.css',
})
export class SidebarComponent implements OnInit {
  isCollapsed = false;
  nombreSitio = 'CRM Pro';

  @Output() collapseChange = new EventEmitter<boolean>();

  menuItems = [
    { label: 'Dashboard',       icon: 'bi-speedometer2',   route: '/dashboard' },
    { label: 'Conversaciones',  icon: 'bi-chat-dots',      route: '/conversaciones' },
    { label: 'Clientes',        icon: 'bi-people',         route: '/clientes' },
    { label: 'Mis Contenidos',  icon: 'bi-folder2-open',   route: '/contenidos' },
  ];

  constructor(
    private router: Router,
    private authService: AuthService,
    private configuracionService: ConfiguracionService
  ) {}

  ngOnInit() {
    this.configuracionService.configuracion$.subscribe(config => {
      if (config) {
        this.nombreSitio = config.nombreSitio;
      }
    });

    // "Usuarios" (ABM de la Parte 2) es exclusivo del admin. La proteccion
    // real esta en el backend (requerirAdmin() en cada endpoint); esto
    // solo evita que un usuario comun vea un ítem que le va a rechazar.
    const esAdmin = this.authService.getUsuarioActual()?.rol === 'Admin';
    if (esAdmin) {
      this.menuItems.push({ label: 'Usuarios', icon: 'bi-person-gear', route: '/usuarios' });
      this.menuItems.push({ label: 'Categorías', icon: 'bi-tags', route: '/categorias' });
      this.menuItems.push({ label: 'Moderación', icon: 'bi-shield-check', route: '/moderacion' });
      this.menuItems.push({ label: 'Configuración', icon: 'bi-gear', route: '/configuracion' });
    }
  }

  toggle() {
    this.isCollapsed = !this.isCollapsed;
    this.collapseChange.emit(this.isCollapsed);
  }

  isActive(route: string): boolean {
    return this.router.url === route;
  }

  navigate(route: string) {
    this.router.navigate([route]);
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}
