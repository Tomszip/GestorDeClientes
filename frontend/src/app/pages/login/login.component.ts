import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ConfiguracionService } from '../../services/configuracion.service';

@Component({
  selector: 'app-login',
  standalone: false,
  templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';
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

  login() {
    this.error = '';
    if (!this.email || !this.password) {
      this.error = 'Completá todos los campos.';
      return;
    }
    this.loading = true;
    this.authService.login(this.email, this.password).subscribe({
      next: (res) => {
        this.authService.guardarSesion(res);
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Email o contraseña incorrectos.';
        this.loading = false;
      }
    });
  }
}
