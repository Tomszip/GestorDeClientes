import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { Client, Conversation, Message } from '../../models/crm.models';
import { ConversacionService } from '../../services/conversacion.service';
import { ClienteService } from '../../services/cliente.service';

@Component({
  selector: 'app-conversaciones',
  standalone: false,
  templateUrl: './conversaciones.html',
  styleUrl: './conversaciones.css',
})
export class ConversacionesComponent implements OnInit {
  busqueda = '';
  nuevoMensaje = '';
  conversacionSeleccionada: Conversation | null = null;

  conversaciones: Conversation[] = [];
  mensajes: Message[] = [];
  cargando = false;
  cargandoMensajes = false;
  enviando = false;
  error = '';

  mostrandoSelectorCliente = false;
  clientesDisponibles: Client[] = [];
  clienteSeleccionadoId: number | null = null;

  constructor(
    private conversacionService: ConversacionService,
    private clienteService: ClienteService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.cargarConversaciones();
  }

  cargarConversaciones() {
    this.cargando = true;
    this.conversacionService.listar().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.conversaciones = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar las conversaciones.';
      },
    });
  }

  get conversacionesFiltradas() {
    const t = this.busqueda.toLowerCase();
    if (!t) return this.conversaciones;
    return this.conversaciones.filter(c => c.nombre.toLowerCase().includes(t));
  }

  seleccionar(conv: Conversation) {
    this.conversacionSeleccionada = conv;
    this.cargandoMensajes = true;
    this.conversacionService.mensajes(conv.id).pipe(
      finalize(() => {
        this.cargandoMensajes = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.mensajes = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar los mensajes.';
      },
    });
  }

  enviarMensaje() {
    if (!this.nuevoMensaje.trim() || !this.conversacionSeleccionada) {
      return;
    }

    const texto = this.nuevoMensaje.trim();
    const conversacionId = this.conversacionSeleccionada.id;

    this.enviando = true;
    this.conversacionService.enviarMensaje(conversacionId, texto).pipe(
      finalize(() => {
        this.enviando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.mensajes.push(res);
        const conv = this.conversaciones.find(c => c.id === conversacionId);
        if (conv) {
          conv.ultimoMensaje = texto;
        }
        this.nuevoMensaje = '';
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al enviar el mensaje.';
      },
    });
  }

  cambiarEstado(estado: 'en-curso' | 'cerrado') {
    if (!this.conversacionSeleccionada) {
      return;
    }
    const conversacionId = this.conversacionSeleccionada.id;

    this.conversacionService.cambiarEstado(conversacionId, estado).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.conversacionSeleccionada!.estado = estado;
        const conv = this.conversaciones.find(c => c.id === conversacionId);
        if (conv) {
          conv.estado = estado;
        }
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al cambiar el estado.';
      },
    });
  }

  abrirSelectorCliente() {
    this.mostrandoSelectorCliente = true;
    this.clienteSeleccionadoId = null;
    this.clienteService.listar().pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        this.clientesDisponibles = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar los clientes.';
      },
    });
  }

  cerrarSelectorCliente() {
    this.mostrandoSelectorCliente = false;
  }

  iniciarConversacion() {
    if (!this.clienteSeleccionadoId) {
      return;
    }

    this.conversacionService.crear(this.clienteSeleccionadoId).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        const existente = this.conversaciones.find(c => c.id === res.id);
        if (!existente) {
          this.conversaciones.unshift(res);
        }
        this.mostrandoSelectorCliente = false;
        this.seleccionar(existente || res);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al iniciar la conversación.';
      },
    });
  }

  badgeClass(estado: string): string {
    const map: Record<string, string> = {
      'nuevo':    'badge-nuevo',
      'en-curso': 'badge-en-curso',
      'cerrado':  'badge-cerrado',
    };
    return map[estado] || '';
  }

  badgeLabel(estado: string): string {
    const map: Record<string, string> = {
      'nuevo':'Nuevo', 'en-curso':'En curso', 'cerrado':'Cerrado'
    };
    return map[estado] || estado;
  }

  inicial(nombre: string) { return nombre.charAt(0).toUpperCase(); }
}
