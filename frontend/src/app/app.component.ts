import { Component, Inject, OnInit } from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { Title } from '@angular/platform-browser';
import { ConfiguracionService } from './services/configuracion.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  standalone: false,
  styleUrl: './app.css'
})
export class AppComponent implements OnInit {
  constructor(
    private configuracionService: ConfiguracionService,
    private titleService: Title,
    @Inject(DOCUMENT) private document: Document
  ) {}

  ngOnInit() {
    // Dispara la carga inicial. La reaccion (titulo + tema) va por la
    // suscripcion de abajo, no por el "next" de este subscribe, para que
    // tambien se vuelva a aplicar sola si algo mas (la pantalla de
    // Configuración) actualiza la configuracion despues.
    this.configuracionService.cargar().subscribe({
      error: () => {
        // Si todavia no hay configuracion inicial (sitio sin instalar),
        // se queda con el titulo y el tema por defecto del index.html.
      },
    });

    this.configuracionService.configuracion$.subscribe(config => {
      if (config) {
        this.titleService.setTitle(config.nombreSitio);
        this.aplicarTema(config.temaActivo.archivoCss);
      }
    });
  }

  private aplicarTema(archivoCss: string) {
    let link = this.document.getElementById('tema-activo') as HTMLLinkElement | null;
    if (!link) {
      link = this.document.createElement('link');
      link.id = 'tema-activo';
      link.rel = 'stylesheet';
      this.document.head.appendChild(link);
    }
    link.href = `temas/${archivoCss}`;
  }
}
