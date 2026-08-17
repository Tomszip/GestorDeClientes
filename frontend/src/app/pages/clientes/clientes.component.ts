import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { Client } from '../../models/crm.models';
import { ClienteService } from '../../services/cliente.service';

@Component({
  selector: 'app-clientes',
  standalone: false,
  templateUrl: './clientes.html',
  styleUrl: './clientes.css',
})
export class ClientesComponent implements OnInit {
  busqueda = '';
  clientes: Client[] = [];
  cargando = false;
  error = '';

  mostrandoFormulario = false;
  clienteEditando: Client | null = null;
  guardando = false;

  nombre = '';
  email = '';
  telefono = '';
  empresa = '';
  ubicacion = '';

  constructor(private clienteService: ClienteService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.cargarClientes();
  }

  cargarClientes() {
    this.cargando = true;
    this.clienteService.listar().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.clientes = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar los clientes.';
      },
    });
  }

  get clientesFiltrados() {
    const t = this.busqueda.toLowerCase();
    if (!t) return this.clientes;
    return this.clientes.filter(c =>
      c.nombre.toLowerCase().includes(t) ||
      c.email.toLowerCase().includes(t) ||
      c.empresa.toLowerCase().includes(t)
    );
  }

  abrirFormularioNuevo() {
    this.clienteEditando = null;
    this.limpiarFormulario();
    this.mostrandoFormulario = true;
  }

  abrirFormularioEditar(cliente: Client) {
    this.clienteEditando = cliente;
    this.nombre = cliente.nombre;
    this.email = cliente.email;
    this.telefono = cliente.telefono;
    this.empresa = cliente.empresa;
    this.ubicacion = cliente.ubicacion || '';
    this.mostrandoFormulario = true;
  }

  cerrarFormulario() {
    this.mostrandoFormulario = false;
    this.clienteEditando = null;
  }

  private limpiarFormulario() {
    this.nombre = '';
    this.email = '';
    this.telefono = '';
    this.empresa = '';
    this.ubicacion = '';
  }

  guardarCliente() {
    this.error = '';

    if (!this.nombre.trim() || !this.email.trim() || !this.telefono.trim() || !this.empresa.trim()) {
      this.error = 'Nombre, email, teléfono y empresa son obligatorios.';
      return;
    }

    const datos = {
      nombre: this.nombre.trim(),
      email: this.email.trim(),
      telefono: this.telefono.trim(),
      empresa: this.empresa.trim(),
      ubicacion: this.ubicacion.trim(),
    };

    const peticion = this.clienteEditando
      ? this.clienteService.actualizar(this.clienteEditando.id, datos)
      : this.clienteService.crear(datos);

    this.guardando = true;
    peticion.pipe(
      finalize(() => {
        this.guardando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        if (this.clienteEditando) {
          const index = this.clientes.findIndex(c => c.id === res.id);
          if (index !== -1) {
            this.clientes[index] = { ...this.clientes[index], ...res };
          }
        } else {
          this.clientes.unshift(res);
        }
        this.cerrarFormulario();
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al guardar el cliente.';
      },
    });
  }

  eliminar(id: number) {
    if (!confirm('¿Estás seguro de eliminar este cliente?')) {
      return;
    }

    this.clienteService.eliminar(id).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.clientes = this.clientes.filter(c => c.id !== id);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al eliminar el cliente.';
      },
    });
  }

  badgeClass(estado: string): string {
    const map: Record<string, string> = {
      'Activo':   'badge-activo',
      'Nuevo':    'badge-nuevo',
      'Pendiente':'badge-pendiente',
      'Inactivo': 'badge-inactivo',
    };
    return map[estado] || '';
  }

  inicial(nombre: string): string {
    return nombre.charAt(0).toUpperCase();
  }
}
