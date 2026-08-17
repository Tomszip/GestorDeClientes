import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ConfiguracionService } from '../../services/configuracion.service';

@Component({
  selector: 'app-registro',
  standalone: false,
  templateUrl: './registro.component.html',
})
export class RegistroComponent implements OnInit {
  nombre = '';
  email = '';
  password = '';
  confirmar = '';
  error = '';
  loading = false;
  nombreSitio = 'CRM Pro';

  constructor(
    private authService: AuthService,
    private router: Router,
    private configuracionService: ConfiguracionService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.configuracionService.configuracion$.subscribe(config => {
      if (config) {
        this.nombreSitio = config.nombreSitio;
        this.cdr.detectChanges();
      }
    });
  }

  registrar() {
    this.error = '';
    if (!this.nombre || !this.email || !this.password || !this.confirmar) {
      this.error = 'Completá todos los campos.';
      return;
    }
    if (this.password !== this.confirmar) {
      this.error = 'Las contraseñas no coinciden.';
      return;
    }
    if (this.password.length < 6) {
      this.error = 'La contraseña debe tener al menos 6 caracteres.';
      return;
    }
    this.loading = true;
    this.authService.registrar(this.nombre, this.email, this.password).subscribe({
      next: (res) => {
        this.authService.guardarSesion(res);
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al registrarse. Intentá de nuevo.';
        this.loading = false;
      }
    });
  }
}
