import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { Categoria } from '../../models/crm.models';
import { CategoriaService } from '../../services/categoria.service';

@Component({
  selector: 'app-categorias',
  standalone: false,
  templateUrl: './categorias.html',
  styleUrl: './categorias.css',
})
export class CategoriasComponent implements OnInit {
  categorias: Categoria[] = [];
  cargando = false;
  error = '';

  mostrandoFormulario = false;
  categoriaEditando: Categoria | null = null;
  guardando = false;

  nombre = '';
  descripcion = '';

  constructor(private categoriaService: CategoriaService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.cargarCategorias();
  }

  cargarCategorias() {
    this.cargando = true;
    this.categoriaService.listar().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.categorias = res;
      },
      error: () => {
        this.error = 'No se pudieron cargar las categorías.';
      },
    });
  }

  abrirFormularioNuevo() {
    this.categoriaEditando = null;
    this.nombre = '';
    this.descripcion = '';
    this.mostrandoFormulario = true;
  }

  abrirFormularioEditar(categoria: Categoria) {
    this.categoriaEditando = categoria;
    this.nombre = categoria.nombre;
    this.descripcion = categoria.descripcion || '';
    this.mostrandoFormulario = true;
  }

  cerrarFormulario() {
    this.mostrandoFormulario = false;
    this.categoriaEditando = null;
  }

  guardarCategoria() {
    this.error = '';

    if (!this.nombre.trim()) {
      this.error = 'El nombre es obligatorio.';
      return;
    }

    const datos = {
      nombre: this.nombre.trim(),
      descripcion: this.descripcion.trim(),
    };

    const peticion = this.categoriaEditando
      ? this.categoriaService.actualizar(this.categoriaEditando.id, datos)
      : this.categoriaService.crear(datos);

    this.guardando = true;
    peticion.pipe(
      finalize(() => {
        this.guardando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        if (this.categoriaEditando) {
          const index = this.categorias.findIndex(c => c.id === res.id);
          if (index !== -1) {
            this.categorias[index] = res;
          }
        } else {
          this.categorias.push(res);
        }
        this.cerrarFormulario();
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al guardar la categoría.';
      },
    });
  }

  eliminar(id: number) {
    if (!confirm('¿Estás seguro de eliminar esta categoría? Los contenidos que la usaban quedarán sin categoría.')) {
      return;
    }

    this.categoriaService.eliminar(id).pipe(
      finalize(() => this.cdr.detectChanges())
    ).subscribe({
      next: () => {
        this.categorias = this.categorias.filter(c => c.id !== id);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al eliminar la categoría.';
      },
    });
  }
}
