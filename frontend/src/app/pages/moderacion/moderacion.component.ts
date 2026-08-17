import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { ContenidoModeracion } from '../../models/crm.models';
import { ContenidoService } from '../../services/contenido.service';

@Component({
  selector: 'app-moderacion',
  standalone: false,
  templateUrl: './moderacion.html',
  styleUrl: './moderacion.css',
})
export class ModeracionComponent implements OnInit {
  busqueda = '';
  contenidos: ContenidoModeracion[] = [];
  cargando = false;
  error = '';

  constructor(private contenidoService: ContenidoService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.cargarContenidos();
  }

  cargarContenidos() {
    this.cargando = true;
    this.contenidoService.listarTodos().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.contenidos = res;
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'No se pudieron cargar los contenidos.';
      },
    });
  }

  get contenidosFiltrados() {
    const t = this.busqueda.toLowerCase();
    if (!t) return this.contenidos;
    return this.contenidos.filter(c =>
      c.titulo.toLowerCase().includes(t) ||
      c.autor.toLowerCase().includes(t) ||
      c.autorEmail.toLowerCase().includes(t)
    );
  }

  toggleEstado(contenido: ContenidoModeracion) {
    const nuevoEstado = contenido.estado === 'Visible' ? 'oculto' : 'visible';

    this.contenidoService.cambiarEstado(contenido.id, nuevoEstado).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        contenido.estado = res.estado;
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al cambiar el estado.';
      },
    });
  }

  eliminar(id: number) {
    if (!confirm('¿Estás seguro de eliminar este contenido? Esta acción no se puede deshacer.')) {
      return;
    }

    this.contenidoService.eliminar(id).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.contenidos = this.contenidos.filter(c => c.id !== id);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al eliminar el contenido.';
      },
    });
  }

  iconoPorTipo(tipo: string): string {
    const map: Record<string, string> = {
      texto: 'bi-file-text',
      imagen: 'bi-file-image',
      archivo: 'bi-file-earmark',
    };
    return map[tipo] || 'bi-file-earmark';
  }

  inicial(nombre: string) {
    return nombre.charAt(0).toUpperCase();
  }
}
