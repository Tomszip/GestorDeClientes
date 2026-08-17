import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-navbar',
  standalone: false,
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class NavbarComponent implements OnInit {
  userName = 'Usuario';
  userInitials = 'U';

  constructor(private authService: AuthService) {}

  ngOnInit() {
    const usuario = this.authService.getUsuarioActual();
    if (usuario) {
      this.userName = usuario.nombre || 'Usuario';
      this.userInitials = this.userName.charAt(0).toUpperCase();
    }
  }
}
