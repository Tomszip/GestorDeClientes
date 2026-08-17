import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs';
import { Tema } from '../../models/crm.models';
import { ConfiguracionService } from '../../services/configuracion.service';

@Component({
  selector: 'app-configuracion',
  standalone: false,
  templateUrl: './configuracion.html',
  styleUrl: './configuracion.css',
})
export class ConfiguracionComponent implements OnInit {
  nombreSitio = '';
  temaActivoId: number | null = null;
  temas: Tema[] = [];

  cargando = false;
  guardando = false;
  error = '';
  mensajeExito = '';

  constructor(private configuracionService: ConfiguracionService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.cargando = true;
    this.configuracionService.listarTemas().pipe(
      finalize(() => {
        this.cargando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: (res) => {
        this.temas = res;
        const activo = res.find(t => t.activo);
        this.temaActivoId = activo ? activo.id : null;
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'No se pudieron cargar los temas.';
      },
    });

    this.configuracionService.configuracion$.subscribe(config => {
      if (config) {
        this.nombreSitio = config.nombreSitio;
        this.cdr.detectChanges();
      }
    });
  }

  // Solo para mostrar un círculo de color junto a cada opción; los
  // colores reales viven en los archivos CSS de public/temas/. Para
  // "Claro"/"Oscuro" se muestra el FONDO (blanco/negro) en vez del
  // acento, porque lo que los distingue a simple vista es eso, no el
  // color de los botones. Para "Corporativo" se muestra el acento
  // (azul), que es lo que lo distingue de "Claro".
  colorDeVista(nombre: string): string {
    const map: Record<string, string> = {
      'Claro': '#FFFFFF',
      'Oscuro': '#0F172A',
      'Corporativo': '#1E3A8A',
    };
    return map[nombre] || '#0F172A';
  }

  guardar() {
    this.error = '';
    this.mensajeExito = '';

    if (!this.nombreSitio.trim()) {
      this.error = 'El nombre del sitio es obligatorio.';
      return;
    }
    if (!this.temaActivoId) {
      this.error = 'Elegí un tema.';
      return;
    }

    this.guardando = true;
    this.configuracionService.actualizar(this.nombreSitio.trim(), this.temaActivoId).pipe(
      finalize(() => {
        this.guardando = false;
        this.cdr.detectChanges();
      })
    ).subscribe({
      next: () => {
        this.mensajeExito = 'Cambios guardados correctamente. Recargando para aplicar el tema...';
        this.temas = this.temas.map(t => ({ ...t, activo: t.id === this.temaActivoId }));
        // El navegador no siempre repinta al instante los estilos con
        // !important de una hoja de estilos cambiada dinámicamente.
        // Recargar es la forma simple y confiable de garantizar que el
        // tema nuevo se vea en toda la app, no solo en esta pantalla.
        setTimeout(() => window.location.reload(), 800);
      },
      error: (err) => {
        this.error = err.error?.mensaje || 'Error al guardar la configuración.';
      },
    });
  }
}
