import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { Categoria, Contenido, ContenidoCompartido, UsuarioBusqueda } from '../../models/crm.models';
import { ContenidoService } from '../../services/contenido.service';
import { CategoriaService } from '../../services/categoria.service';
import { UsuarioService } from '../../services/usuario.service';

const TAMANO_MAXIMO_ARCHIVO = 10 * 1024 * 1024; // 10 MB, igual que en crear.php

@Component({
  selector: 'app-contenidos',
  standalone: false,
  templateUrl: './contenidos.html',
  styleUrl: './contenidos.css',
})
export class ContenidosComponent implements OnInit {
  contenidos: Contenido[] = [];
  contenidosCompartidos: ContenidoCompartido[] = [];
  cargando = false;
  subiendo = false;
  error = '';

  titulo = '';
  texto = '';
  tipo: 'texto' | 'imagen' | 'archivo' = 'texto';
  archivoSeleccionado: File | null = null;
  categoriaId: number | null = null;
  categorias: Categoria[] = [];

  // Estado del panel de "compartir" de cada contenido
  compartiendoId: number | null = null;
  busquedaUsuarios = '';
  resultadosBusqueda: UsuarioBusqueda[] = [];
  usuariosSeleccionados = new Set<number>();

  constructor(
    private contenidoService: ContenidoService,
    private categoriaService: CategoriaService,
    private usuarioService: UsuarioService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.cargarContenidos();
    this.cargarCompartidosConmigo();
    this.cargarCategorias();
  }

  cargarCategorias() {
    this.categoriaService.listar().pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        this.categorias = res;
      },
      error: () => {
        // No interrumpe la pantalla: el formulario simplemente queda sin opciones de categoría.
      },
    });
  }

  cargarContenidos() {
    this.cargando = true;
    this.contenidoService.listar().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.contenidos = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar los contenidos.';
      },
    });
  }

  onArchivoSeleccionado(event: Event) {
    const input = event.target as HTMLInputElement;
    this.archivoSeleccionado = input.files?.[0] ?? null;
  }

  subirContenido() {
    this.error = '';

    if (!this.titulo.trim()) {
      this.error = 'El título es obligatorio.';
      return;
    }
    if (this.tipo === 'texto' && !this.texto.trim()) {
      this.error = 'Escribí el contenido de texto.';
      return;
    }
    if (this.tipo !== 'texto' && !this.archivoSeleccionado) {
      this.error = 'Seleccioná un archivo.';
      return;
    }
    if (this.archivoSeleccionado && this.archivoSeleccionado.size > TAMANO_MAXIMO_ARCHIVO) {
      this.error = 'El archivo supera el tamaño máximo permitido (10 MB).';
      return;
    }

    const formData = new FormData();
    formData.append('titulo', this.titulo.trim());
    formData.append('texto', this.texto.trim());
    formData.append('tipo', this.tipo);
    if (this.categoriaId) {
      formData.append('categoriaId', this.categoriaId.toString());
    }
    if (this.archivoSeleccionado) {
      formData.append('archivo', this.archivoSeleccionado);
    }

    this.subiendo = true;
    this.contenidoService.crear(formData).pipe(
      finalize(() => {
        this.subiendo = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.contenidos.unshift(res);
        this.limpiarFormulario();
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al subir el contenido.';
      },
    });
  }

  private limpiarFormulario() {
    this.titulo = '';
    this.texto = '';
    this.tipo = 'texto';
    this.archivoSeleccionado = null;
    this.categoriaId = null;
  }

  eliminar(id: number) {
    if (!confirm('¿Estás seguro de eliminar este contenido?')) {
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

  cargarCompartidosConmigo() {
    this.contenidoService.compartidosConmigo().pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        this.contenidosCompartidos = res;
      },
      error: () => {
        // No interrumpe la pantalla si esto falla, "Mis publicaciones" ya se ve igual.
      },
    });
  }

  abrirCompartir(contenido: Contenido) {
    this.compartiendoId = contenido.id;
    this.busquedaUsuarios = '';
    this.resultadosBusqueda = [];
    this.usuariosSeleccionados = new Set<number>();
  }

  cerrarCompartir() {
    this.compartiendoId = null;
  }

  buscarUsuarios() {
    const termino = this.busquedaUsuarios.trim();
    if (termino.length < 2) {
      this.resultadosBusqueda = [];
      return;
    }

    this.usuarioService.buscar(termino).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: (res) => {
        this.resultadosBusqueda = res;
      },
      error: () => {
        this.resultadosBusqueda = [];
      },
    });
  }

  estaSeleccionado(id: number): boolean {
    return this.usuariosSeleccionados.has(id);
  }

  toggleSeleccionUsuario(id: number) {
    if (this.usuariosSeleccionados.has(id)) {
      this.usuariosSeleccionados.delete(id);
    } else {
      this.usuariosSeleccionados.add(id);
    }
  }

  enviarCompartir(contenidoId: number) {
    if (this.usuariosSeleccionados.size === 0) {
      return;
    }

    this.contenidoService.compartir(contenidoId, Array.from(this.usuariosSeleccionados)).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.cerrarCompartir();
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al compartir el contenido.';
      },
    });
  }

  urlArchivo(contenido: Contenido): string {
    return this.contenidoService.urlArchivo(contenido.rutaArchivo!);
  }

  iconoPorTipo(tipo: string): string {
    const map: Record<string, string> = {
      texto: 'bi-file-text',
      imagen: 'bi-file-image',
      archivo: 'bi-file-earmark',
    };
    return map[tipo] || 'bi-file-earmark';
  }
}
