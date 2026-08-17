import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { User } from '../../models/crm.models';
import { AuthService } from '../../services/auth.service';
import { UsuarioService } from '../../services/usuario.service';

@Component({
  selector: 'app-usuarios',
  standalone: false,
  templateUrl: './usuarios.html',
  styleUrl: './usuarios.css',
})
export class UsuariosComponent implements OnInit {
  busqueda = '';
  filtroRol = '';
  filtroEstado = '';

  usuarios: User[] = [];
  cargando = false;
  error = '';

  mostrandoFormulario = false;
  usuarioEditando: User | null = null;
  guardando = false;

  nombre = '';
  email = '';
  password = '';
  rol: 'Admin' | 'Usuario' = 'Usuario';
  estado: 'Activo' | 'Inactivo' = 'Activo';

  miUsuarioId: number | null = null;

  constructor(
    private usuarioService: UsuarioService,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.miUsuarioId = this.authService.getUsuarioActual()?.id ?? null;
    this.cargarUsuarios();
  }

  cargarUsuarios() {
    this.cargando = true;
    this.usuarioService.listar().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.usuarios = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar los usuarios.';
      },
    });
  }

  get usuariosFiltrados() {
    return this.usuarios.filter(u => {
      const matchBusq = !this.busqueda ||
        u.nombre.toLowerCase().includes(this.busqueda.toLowerCase()) ||
        u.email.toLowerCase().includes(this.busqueda.toLowerCase());
      const matchRol = !this.filtroRol || u.rol === this.filtroRol;
      const matchEstado = !this.filtroEstado || u.estado === this.filtroEstado;
      return matchBusq && matchRol && matchEstado;
    });
  }

  get totalUsuarios()   { return this.usuarios.length; }
  get totalAdmins()     { return this.usuarios.filter(u => u.rol === 'Admin').length; }
  get totalActivos()    { return this.usuarios.filter(u => u.estado === 'Activo').length; }

  inicial(nombre: string) { return nombre.charAt(0).toUpperCase(); }

  abrirFormularioNuevo() {
    this.usuarioEditando = null;
    this.limpiarFormulario();
    this.mostrandoFormulario = true;
  }

  abrirFormularioEditar(usuario: User) {
    this.usuarioEditando = usuario;
    this.nombre = usuario.nombre;
    this.email = usuario.email;
    this.password = '';
    this.rol = usuario.rol;
    this.estado = usuario.estado;
    this.mostrandoFormulario = true;
  }

  cerrarFormulario() {
    this.mostrandoFormulario = false;
    this.usuarioEditando = null;
  }

  private limpiarFormulario() {
    this.nombre = '';
    this.email = '';
    this.password = '';
    this.rol = 'Usuario';
    this.estado = 'Activo';
  }

  guardarUsuario() {
    this.error = '';

    if (!this.nombre.trim() || !this.email.trim()) {
      this.error = 'Nombre y email son obligatorios.';
      return;
    }
    if (!this.usuarioEditando && this.password.trim().length < 6) {
      this.error = 'La contraseña inicial debe tener al menos 6 caracteres.';
      return;
    }

    this.guardando = true;

    const peticion = this.usuarioEditando
      ? this.usuarioService.actualizar(this.usuarioEditando.id, {
          nombre: this.nombre.trim(),
          email: this.email.trim(),
          rol: this.rol,
          estado: this.estado,
        })
      : this.usuarioService.crear({
          nombre: this.nombre.trim(),
          email: this.email.trim(),
          password: this.password,
          rol: this.rol,
        });

    const esEdicion = !!this.usuarioEditando;

    peticion.pipe(
      finalize(() => {
        this.guardando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        if (esEdicion) {
          const index = this.usuarios.findIndex(u => u.id === res.id);
          if (index !== -1) {
            this.usuarios[index] = { ...this.usuarios[index], ...res };
          }
        } else {
          this.usuarios.unshift(res);
        }
        this.cerrarFormulario();
      },
      error: (err) => {
        this.error = err.error?.mensaje || (esEdicion ? 'Error al guardar el usuario.' : 'Error al crear el usuario.');
      },
    });
  }

  eliminar(id: number) {
    if (id === this.miUsuarioId) {
      this.error = 'No podés eliminar tu propia cuenta.';
      return;
    }
    if (!confirm('¿Estás seguro de eliminar este usuario?')) {
      return;
    }

    this.usuarioService.eliminar(id).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.usuarios = this.usuarios.filter(u => u.id !== id);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al eliminar el usuario.';
      },
    });
  }
}
